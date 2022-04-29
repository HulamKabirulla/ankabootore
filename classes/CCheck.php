<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    trait Check{
        function getUsersId($login,$password,$token)//token
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con,$login)."' AND password='".mysqli_real_escape_string($this->con,$password)."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                return $row;
            }
            return false;
        }
        function getUserById($id,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE id='".(int)$id."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                return $row;
            }
            return false;
        }
        function isInBlackList($idUserFrom,$idUserTo,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $resIsInBlackList=$this->con->query("SELECT* FROM blacklist WHERE id_from='".(int)$idUserFrom."' AND id_to='".(int)$idUserTo."'");
            if($resIsInBlackList->num_rows)
            {
                return true;
            }
            return false;
        }
        function getUsersProfileImg($id_user,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $flag=false;
            if((int)$id_user>0)
            {
                if(is_dir('storage/profileimg/'.(int)$id_user.'/'))
                {
                    if($handle = opendir('storage/profileimg/'.(int)$id_user.'/'))
                    {
                        while(false !== ($file = readdir($handle)))
                        {
                            if($file != "." && $file != "..") 
                            {
                                $fileName=$file;
                                closedir($handle);
                                return "storage/profileimg/".(int)$id_user."/".$fileName."";
                            }
                        } 
                        closedir($handle);
                    }
                }
            }
            return "anonym.png";
        }
    }
?>