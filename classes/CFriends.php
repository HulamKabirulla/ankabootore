<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    include_once("classes/CCheck.php");
    class Friends{
        use Check;
        private $con;
        function __construct($con) {
            $this->con=$con;
        }
        function getSubscribersCountById($idUser)
        {
            $userQuery=$this->con->query("SELECT id FROM friends WHERE user_to='".(int)$idUser."' AND action='1'");
            return $userQuery->num_rows;
        }
        function getSubscriptionsCountById($idUser)
        {
            $userQuery=$this->con->query("SELECT id FROM friends WHERE user_from='".(int)$idUser."' AND action='1'");
            return $userQuery->num_rows;
        }
        function getUsersProfileImg($id_user)
        {
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
        function getFollowersByName($idUser,$name,$limit)
        {
            $countRes=5;
                $limit = $limit>0 ? $limit-1 : 0;
                if($limit>0)
                {
                    $limit=$limit*($countRes);
                }
            $res=$this->con->query("SELECT users.id AS UId,name AS UName FROM users RIGHT JOIN friends ON user_from=users.id AND user_to='".(int)$idUser."' WHERE users.name LIKE '%".mysqli_real_escape_string($this->con,$name)."%' AND action='1' ORDER BY friends.id DESC LIMIT ".$limit.",".$countRes."");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['UId']);
                        $massive[$i]['PPath']=$profilePhoto;
            }
            return $massive;
        }
        function getFollowersById($idUser,$limit)
        {
            $countRes=5;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $res=$this->con->query("SELECT users.id AS UId,name AS UName FROM users RIGHT JOIN friends ON friends.user_from=users.id WHERE friends.user_to='".(int)$idUser."' AND action='1' ORDER BY friends.id DESC LIMIT ".$limit.",".$countRes."");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;$profilePhoto=$this->getUsersProfileImg($massive[$i]['UId']);
                        $massive[$i]['PPath']=$profilePhoto;
            }
            return $massive;
        }
        function getSubscribersByName($idUser,$name,$limit)
        {
            $countRes=5;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $res=$this->con->query("SELECT users.id AS UId,users.name AS UName FROM users LEFT JOIN friends ON friends.user_to=users.id WHERE friends.user_from='".(int)$idUser."' AND friends.action='1' AND users.name LIKE '%".mysqli_real_escape_string($this->con,$name)."%' ORDER BY friends.id DESC LIMIT ".$limit.",".$countRes."");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['UId']);
                        $massive[$i]['PPath']=$profilePhoto;
            }
            return $massive;
        }
        function getSubscribersById($idUser,$limit)
        {
            $countRes=5;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $res=$this->con->query("SELECT users.id AS UId,users.name AS UName FROM users LEFT JOIN friends ON friends.user_to=users.id WHERE friends.user_from='".(int)$idUser."' AND friends.action='1' ORDER BY friends.id DESC LIMIT ".$limit.",".$countRes."");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['UId']);
                        $massive[$i]['PPath']=$profilePhoto;
            }
            return $massive;
        }
        function IsFollowerOrNo($firstUserIdFollower,$secondUserId)
        {
            $res=$this->con->query("SELECT id FROM friends WHERE user_from='".(int)$firstUserIdFollower."' AND user_to='".(int)$secondUserId."' AND action='1'");
            return $res->num_rows;
        }
        function cancelFollowing($loginUser,$passwordUser,$idSubscriber,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $this->con->query("UPDATE friends SET action='2' WHERE user_from=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password='".mysqli_real_escape_string($this->con, $passwordUser)."' LIMIT 1) AND user_to='".(int)$idSubscriber."' AND action='1'");
            return true;
        }
        function startFollowing($loginUser,$passwordUser,$idSubscriber,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if($rowUser=$this->getUsersId($loginUser,$passwordUser,$token))
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $curAddableUser=$this->getUserById($idSubscriber,$token);
                if(!$curAddableUser||$curAddableUser['baned']||$rowUser['id']==(int)$idSubscriber)
                {
                    return "user_error";
                }
                else if($this->isInBlackList($rowUser['id'],$idSubscriber,$token)||$this->isInBlackList($idSubscriber,$rowUser['id'],$token))
                {
                    return "blackList_error";
                }
                $res=$this->con->query("SELECT* FROM friends WHERE user_from='".(int)$rowUser['id']."' AND user_to='".(int)$idSubscriber."'");
                if($row=$res->fetch_assoc())
                {
                    $this->con->query("UPDATE friends SET action=1 WHERE user_from='".(int)$rowUser['id']."' AND user_to='".(int)$idSubscriber."'");
                }
                else
                {
                    $this->con->query("INSERT INTO friends(user_from,user_to,action) VALUES('".(int)$rowUser['id']."','".(int)$idSubscriber."','1')");
                }
                return true;
            }
        }
    }
?>