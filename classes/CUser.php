<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    include_once("classes/CCheck.php");
    class User{
        use Check;
        private $con;
        function __construct($con) {
            $this->con=$con;
        }
        function CheckForHashUser($hash)
        {
            $res=$this->con->query("SELECT* FROM users WHERE hash='".mysqli_real_escape_string($this->con,$hash)."'");
            if($row=$res->fetch_assoc())
            {
                return true;
            }
            return false;
        }
        function CreateHash()
        {
            $hash="";
            if(!isset($_COOKIE['usersHash']))
            {
                $musor='gjrivnvbrweu';
                $hash=md5(mt_rand(1,1000000)."gjrivnvbrweu");
                while($this->CheckForHashUser($hash))
                {
                    $hash=md5(mt_rand(1,1000000));
                }
                $ip = $_SERVER['REMOTE_ADDR'];   
                $this->con->query("INSERT INTO users(hash,ip) VALUES('".mysqli_real_escape_string($this->con,$hash)."','".mysqli_real_escape_string($this->con,$ip)."')");
                setcookie("usersHash",$hash,time() + (10 * 365 * 24 * 60 * 60), '/');
            }
            else
            {
               $hash=$_COOKIE['usersHash'];
                if(!$this->CheckForHashUser($hash))
                {
                    $hash=md5(mt_rand(1,1000000));
                    while($this->CheckForHashUser($hash))
                    {
                       $hash=md5(mt_rand(1,1000000));
                    }
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $this->con->query("INSERT INTO users(hash,ip) VALUES('".mysqli_real_escape_string($this->con,$hash)."','".mysqli_real_escape_string($this->con,$ip)."')");
                    setcookie("usersHash",$hash,time() + (10 * 365 * 24 * 60 * 60), '/');   
                }   
            }
            return $hash;
        }
        function autorizationUser($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(strlen($login)>0)
            {
                $res=$this->con->query("SELECT *FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    if($row['password']==password_verify($password, $row['password']))
                    {
                    return $row;
                    }
                }   
            }
            return false;
        }
        function restoreUser($login)
        {
            if(strlen($login)>0)
            {
                $res=$this->con->query("SELECT id,login FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND verify='1'");
                if($row=$res->fetch_assoc())
                {
                    $header='ankabootore.com';
                    $subject='Ankaboot restore';
                    $checkSum=crypt(md5(mt_rand(1,1000000)."musor"),"hgfrebharambfgvju134bc2");
		     $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

</head>
<body>

<div>
    <a href ="http:/ankabootore.com/restoreacceess.php?userLogin='.$login.'&confirmCode='.$checkSum.'">Перейдите по ссылке для восстановления доступа</a>
<h1>
        Ваш код: '.$checkSum.'
    </h1>

</div>
</body>
</html>';
                    /*$message="Activate via link: <a href='http://ankabootore.com/restoreaccess.php?userLogin=".$login."&confirmCode=".$checkSum."'>Перейдите для восстановления пароля</a>";*/
$headers  = "From: Ankaboot\r\n";
//$headers .= "Reply-To: anoop@abc.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";//ISO-8859-1
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                   $this->con->query("INSERT INTO usersRestoreCode(id_user,code) VALUES('".(int)$row['id']."','".mysqli_real_escape_string($this->con, $checkSum)."')");
                    mail($login,$subject,$message,$headers);
                    return true; 
                }
            }
            return false;
        }
        function restoreAccess($userLogin,$userCode,$newPass,$repeatNewPass)
        {
            if(strlen($userLogin)>0&&$newPass==$repeatNewPass)
            {
                $res=$this->con->query("SELECT id_user FROM usersRestoreCode WHERE id_user=(SELECT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $userLogin)."' AND verify='1') AND code='".mysqli_real_escape_string($this->con, $userCode)."' ORDER BY id DESC");
                if($row=$res->fetch_assoc())
                {
                    $idUser=$row['id_user'];
                    $this->con->query("DELETE FROM usersRestoreCode WHERE id_user='".$row['id_user']."'");
                    $this->con->query("UPDATE users SET password='".mysqli_real_escape_string($this->con, password_hash($newPass, PASSWORD_DEFAULT))."' WHERE id='".(int)$idUser."'");
                    return true;
                }
                return false;
            }
            return false;
        }
        function issetRestoreCode($userLogin,$userCode)
        {            $res=$this->con->query("SELECT id_user FROM usersRestoreCode WHERE id_user=(SELECT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $userLogin)."') AND code='".mysqli_real_escape_string($this->con, $userCode)."' ORDER BY id DESC");
            if($row=$res->fetch_assoc())
            {
                $this->con->query("DELETE FROM usersRestoreCode WHERE id_user='".$row['id_user']."' AND code!='".mysqli_real_escape_string($this->con, $userCode)."'");
                return true;
            }
            return false;
        }
        function getUserByNotCryptedPassword($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(strlen($login)>0)
            {
                $res=$this->con->query("SELECT users.is_seller AS isSeller,users.verify AS UVerify,users.login AS ULogin,users.password AS UPassword,users.id AS UId,users.name AS UName FROM users WHERE users.login='".mysqli_real_escape_string($this->con, $login)."' AND users.password='".crypt(mysqli_real_escape_string($this->con, $password),"f54a009ge43mmkvabikr")."'");
                if($row=$res->fetch_assoc())
                {
                    $row['PPath']=$this->getUsersProfileImg($row['UId']);
                    return $row;
                }   
            }
            return false;
        }
        function getUser($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(strlen($login)>0)
            {
                $res=$this->con->query("SELECT users.is_seller AS isSeller,users.verify AS UVerify,users.login AS ULogin,users.password AS UPassword,users.id AS UId,users.realName AS URealName,users.name AS UName FROM users WHERE users.login='".mysqli_real_escape_string($this->con, $login)."' AND users.password='".mysqli_real_escape_string($this->con, $password)."'");
                if($row=$res->fetch_assoc())
                {
                    $row['PPath']=$this->getUsersProfileImg($row['UId']);
                    return $row;
                }   
            }
            return false;
        }
        function addToBlackList($loginUser,$passwordUser,$idUserTo,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $resUser=$this->con->query("SELECT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password ='".mysqli_real_escape_string($this->con, $passwordUser)."'");
            if($rowUser['id']==$idUserTo)
            {
                return "userId_error";
            }
            if($rowUser=$resUser->fetch_assoc())
            {
                $resIsInBlackList=$this->con->query("SELECT* FROM blacklist WHERE id_from='".(int)$rowUser['id']."' AND id_to='".(int)$idUserTo."'");
                if(!$rowIsInBlackList=$resIsInBlackList->fetch_assoc())
                {
                    $this->con->query("DELETE FROM friends WHERE user_from='".$idUserTo."' AND user_to='".$rowUser['id']."'");
                    $this->con->query("DELETE FROM friends WHERE user_from='".$rowUser['id']."' AND user_to='".$idUserTo."'");
                    $this->con->query("INSERT INTO blacklist(id_from,id_to) VALUES('".(int)$rowUser['id']."','".(int)$idUserTo."')");
                }
            }
        }
        function removeFromBlackList($loginUser,$passwordUser,$idUserTo,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $resUser=$this->con->query("SELECT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password ='".mysqli_real_escape_string($this->con, $passwordUser)."'");
            if($rowUser['id']==$idUserTo)
            {
                return "userId_error";
            }
            if($rowUser=$resUser->fetch_assoc())
            {
                $this->con->query("DELETE FROM blacklist WHERE id_from='".(int)$rowUser['id']."' AND id_to='".(int)$idUserTo."'");
            }
        }
        function getUserById($id)
        {
            if((int)$id>0)
            {
                $res=$this->con->query("SELECT users.status AS UStatus,users.verify AS UVerify,users.id AS UId,users.name AS UName,users.baned AS UBaned,users.realName AS URealName,users.phone AS uPhone FROM users WHERE users.id='".(int)$id."'");
                if($row=$res->fetch_assoc())
                {
                    $row['PPath']=$this->getUsersProfileImg($row['UId']);
                    return $row;
                }   
            }
            return false;
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
        function regUser($login,$password,$name,$realName,$phone,$token)
        {
            if($token!=$_SESSION['token'])
            {//mail("shoppokupedia@gmail.com","","","");
                return "csrf_error";
            }
            if(!trim($login)||!trim($password)||!trim($name)||!trim($realName)||!trim($phone)) {
                return false;
            }
            $res=$this->con->query("SELECT login FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' OR name LIKE '".mysqli_real_escape_string($this->con, $name)."'");// AND verify='1'
            if(!$row=$res->fetch_assoc())
            {
                $header='';
                $subject='Ankaboot registration';
                $checkSum=crypt(md5(mt_rand(1,1000000)."musor"),"hgfrebharambfgvju134bc2");
                $message = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

</head>
<body>

<div>
    <a href ="http://ankabootore.com/verify.php?userLogin='.$login.'&confirmCode='.$checkSum.'">Перейдите по ссылке для подтверждения профиля</a>
    <h1>
        Ваш код: '.$checkSum.'
    </h1>

</div>
</body>
</html>';
$headers  = "From: Ankaboot\r\n";
//$headers .= "Reply-To: anoop@abc.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
               $this->con->query("INSERT INTO users(name,realName,login,phone,password) VALUES('".mysqli_real_escape_string($this->con, $name)."','".mysqli_real_escape_string($this->con, $realName)."','".mysqli_real_escape_string($this->con, $login)."','".mysqli_real_escape_string($this->con, $phone)."','".password_hash($password, PASSWORD_DEFAULT)."')");
               $this->con->query("INSERT INTO usersConfirmCode(id_user,code) VALUES('".mysqli_real_escape_string($this->con, $this->con->insert_id)."','".mysqli_real_escape_string($this->con, $checkSum)."')");
                    mail($login,$subject,$message,$headers);
            return true;
            }   
            return false;
        }
        function confirmUser($login,$confimCode)
        {
            $res=$this->con->query("SELECT usersConfirmCode.id_user AS id FROM usersConfirmCode LEFT JOIN users ON users.id=usersConfirmCode.id_user WHERE users.login='".mysqli_real_escape_string($this->con, $login)."' AND usersConfirmCode.code='".mysqli_real_escape_string($this->con, $confimCode)."' AND usersConfirmCode.action='0'");
            if($row=$res->fetch_assoc())
            {
                $this->con->query("DELETE FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND id!='".(int)$row['id']."'");
                $this->con->query("UPDATE users SET verify='1' WHERE id='".(int)$row['id']."'");
                $this->con->query("UPDATE usersConfirmCode SET action='1' WHERE id_user='".(int)$row['id']."'");
                if(!is_dir("storage/profileimg/"."".(int)$row['id']))
                {
                    mkdir("storage/profileimg/"."".(int)$row['id'], 0777);
                    mkdir("storage/wall/"."".(int)$row['id'], 0777);
                    mkdir("storage/goodsimg/"."".(int)$row['id'], 0777);
                    mkdir("storage/temporarygoods/"."".(int)$row['id'], 0777);
                    mkdir("storage/temporary/"."".(int)$row['id'], 0777);
                    mkdir("storage/basket/"."".(int)$row['id'], 0777);
                }
                return true;
            }
            return false;
        }
        function setUserProfilePhoto($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $blacklist = array(".php", ".phtml", ".php3", ".php4", ".html", ".htm");
            $flag=true; 
            $res=$this->con->query("SELECT* FROM users WHERE login='".mysqli_real_escape_string($this->con,$login)."' AND password='".mysqli_real_escape_string($this->con,$password)."'");
            $row=$res->fetch_assoc();
            if($row['baned'])
            {
                return "user_baned_error";
            }
            foreach ($blacklist as $item)
                if(preg_match("/$item\$/i", $_FILES['profile_photo']['name'])) {$flag=false;}
            $type = $_FILES['profile_photo']['type'];
            $size = $_FILES['profile_photo']['size'];
            if (($type != "image/jpg") && ($type != "image/jpeg") && ($type != "image/png")) {$flag=false;}
            if ($size > 5000000) {$flag=false;}
            if($flag)
            {
                if($handle = opendir('storage/profileimg/'.$row['id'].'/'))
                {
                    while(false !== ($file = readdir($handle)))
                    {
                        if($file != "." && $file != "..") 
                        {
                            unlink('storage/profileimg/'.$row['id'].'/'.$file);
                            $flag=false;break;
                        }
                    } 
                    closedir($handle);
                }
                move_uploaded_file($_FILES['profile_photo']['tmp_name'], 'storage/profileimg/'.$row['id'].'/1.jpg');
                /*if($flag)
                {
                    mysql_query("INSERT INTO profileimg(id_user,path) VALUES('".$row['id']."','".'storage/profileimg/'.$row['id'].'/1'.".jpg')");
                }*/
            }
        }
        function issetNotices($loginUser,$passwordUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if($rowUser=$this->getUser($loginUser,$passwordUser,$token))
            {
                $resMsg=$this->con->query("SELECT DISTINCT groupmessage.id AS gID,infomessage.lastMsg AS IMLastMsg,groupmessage.lastMsg AS GMLastMsg FROM infomessage LEFT JOIN groupmessage ON groupmessage.id=infomessage.id_groupmessage WHERE infomessage.lastMsg!=groupmessage.lastMsg AND infomessage.id_user='".$rowUser['UId']."' GROUP BY infomessage.id_groupmessage LIMIT 1");
                $resPurchase=$this->con->query("SELECT DISTINCT id FROM basket WHERE id_user='".$rowUser['UId']."' AND (action>='1' AND action<='4') AND checked_userFrom='0' LIMIT 1");
                $resSales=$this->con->query("SELECT DISTINCT id FROM basket WHERE id_goods IN(SELECT id FROM goods WHERE goods.id_user='".$rowUser['UId']."') AND (action>='1' AND action<='4') AND checked_userTo='0' LIMIT 1");
                $notices['messages']=$resMsg->num_rows;
                $notices['purchases']=$resPurchase->num_rows;
                $notices['sales']=$resSales->num_rows;
                return $notices;
            }
            return null;
        }
        function issetNoticesWithoutToken($loginUser,$passwordUser)
        {
            if($rowUser=$this->getUser($loginUser,$passwordUser,$_SESSION['token']))
            {
                $resMsg=$this->con->query("SELECT DISTINCT groupmessage.id AS gID,infomessage.lastMsg AS IMLastMsg,groupmessage.lastMsg AS GMLastMsg FROM infomessage LEFT JOIN groupmessage ON groupmessage.id=infomessage.id_groupmessage WHERE infomessage.lastMsg!=groupmessage.lastMsg AND infomessage.id_user='".$rowUser['UId']."' GROUP BY infomessage.id_groupmessage LIMIT 1");
                $resPurchase=$this->con->query("SELECT DISTINCT id FROM basket WHERE id_user='".$rowUser['UId']."' AND (action>='1' AND action<='4') AND checked_userFrom='0' LIMIT 1");
                $resSales=$this->con->query("SELECT DISTINCT id FROM basket WHERE id_goods IN(SELECT id FROM goods WHERE goods.id_user='".$rowUser['UId']."') AND (action>='1' AND action<='4') AND checked_userTo='0' LIMIT 1");
                $notices['messages']=$resMsg->num_rows;
                $notices['purchases']=$resPurchase->num_rows;
                $notices['sales']=$resSales->num_rows;
                return $notices;
            }
            return null;
        }

        function setGoodsRaiting($idGoods,$raiting,$loginUser,$passwordUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $rowUser=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con,$loginUser)."' AND password='".mysqli_real_escape_string($this->con,$passwordUser)."' LIMIT 1")->fetch_assoc();
            if($rowUser)
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $res=$this->con->query("SELECT DISTINCT id FROM goodsraiting WHERE id_user='".$rowUser['id']."' AND id_goods='".(int)$idGoods."' LIMIT 1")->fetch_assoc();
                if($res)
                {
                    $this->con->query("UPDATE goodsraiting SET raiting='".(int)$raiting."' WHERE id='".$res['id']."'");
                }
                else
                {
                    $this->con->query("INSERT INTO goodsraiting(id_user,id_goods,raiting) VALUES ('".$rowUser['id']."','".(int)$idGoods."','".(int)$raiting."')");
                }
                return (int)$raiting;
            }
            return false;
        }
        function getUsersByName($searchName,$limit)
        {
            $countRes=10;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }

            $res=$this->con->query("SELECT users.id AS uId,users.name AS uName,users.realName AS uRealName FROM users WHERE users.name LIKE'%".mysqli_real_escape_string($this->con,$searchName)."%' OR users.realName LIKE'%".mysqli_real_escape_string($this->con,$searchName)."%' ORDER BY LOCATE('".mysqli_real_escape_string($this->con,$searchName)."', uRealName) LIMIT ".$limit.",".$countRes."");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                //pPath
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['uId']);
                $massive[$i]['pPath']=$profilePhoto;
            }
            return $massive;
        }
        function sendComplaitUser($login,$password,$userTo,$text,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(!trim($text))
            {
                return "text_error";
            }
            $rowUser=$this->getUsersId($login,$password);
            if($rowUser&&$this->getUserById($userTo))
            {
                $resIssetComplaint=$this->con->query("SELECT id FROM usersComplaints WHERE user_from='".$rowUser['id']."' AND user_to='".(int)$userTo."'");
                if($rowIssetComplaint=$resIssetComplaint->fetch_assoc())
                {
                    $this->con->query("UPDATE usersComplaints SET text='".mysqli_real_escape_string($this->con,$text)."' WHERE id='".$rowIssetComplaint['id']."'");
                    return "true";
                }
                $this->con->query("INSERT INTO usersComplaints(user_from,user_to,text) VALUES('".$rowUser['id']."','".(int)$userTo."','".mysqli_real_escape_string($this->con,$text)."')");
                return "true";
            }
            return "user_error";
        }
    }
?>