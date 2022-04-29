<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    include_once("classes/CCheck.php");
    class Profile{
        use Check;
        private $con;
        function __construct($con) {
            $this->con=$con;
        }
        function updateProfileIno($loginUser,$passwordUser,$newStatus,$newPassword,$newRepeatPassword,$token){
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(trim($newStatus))
            {
                $this->con->query("UPDATE users SET status='".mysqli_real_escape_string($this->con, $newStatus)."' WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password='".mysqli_real_escape_string($this->con, $passwordUser)."'");
            }
            if($newPassword==$newRepeatPassword&&trim($newPassword))
            {
                $this->con->query("UPDATE users SET password='".crypt(mysqli_real_escape_string($this->con, $newPassword),"f54a009ge43mmkvabikr")."' WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."'");
                $_SESSION['password_user']=crypt($newPassword,"f54a009ge43mmkvabikr");
            }
        }
        function insertToWall($loginUser,$passwordUser,$textWall,$token){
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $issetAttach=true;
            if($handle = opendir('storage/temporary/'.$row['id'].'/'))
            {
                while(false !== ($file = readdir($handle)))
                { 
                    if($file != "." && $file != "..") 
                    {
                        $issetAttach=false;
                        break;
                    }
                }        
                closedir($handle);
            }
            if(!trim($textWall)&&!$issetAttach)
            {
                return;
            }
            $textWall=substr($_POST['record'], 0, 15000);
            $res=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password='".mysqli_real_escape_string($this->con, $passwordUser)."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                if($row['baned'])
                {
                    return "user_baned_error";
                }
                $this->con->query("INSERT INTO wall(text,user_id) VALUES('".mysqli_real_escape_string($this->con,$textWall)."','".$row['id']."')");
                $idWall=$this->con->insert_id;
                if($handle = opendir('storage/temporary/'.$row['id'].'/'))
                {
                    while(false !== ($file = readdir($handle)))
                    { 
                        if($file != "." && $file != "..") 
                        {
                            $flag=true;
                            $filename=uniqid();
                            if($handle2 = opendir('storage/wall/'.$row['id'].'/'))
                            {
                                $filename=uniqid();
                                $flag2=false;
                                do{
                                    $filename=uniqid();
                                    $flag2=false;
                                    while(false !== ($file2 = readdir($handle2)))
                                    { 
                                        if($file2 != "." && $file2 != ".."&&!$flag2) 
                                        {
                                            if($file2=$filename)
                                            {
                                                $flag2=true;
                                            }
                                        }
                                    }
                                }while($flag2);
                                closedir($handle2);
                                
                            }
                            //rename('storage/temporary/'.$id.'/'.$file, "storage/wall/".$id."/rir");
                            rename('storage/temporary/'.$row['id'].'/'.$file, "storage/wall/".$row['id']."/".mysqli_real_escape_string($this->con,$filename));
                            $this->con->query("INSERT INTO wallattach(id_wall,file) VALUES('".$idWall."','storage/wall/".$row['id']."/$filename')");
                        }
                    }        
                    closedir($handle);
                }
            }
        }
        function getWallById($idWall)
        {
            $res=$this->con->query("SELECT DISTINCT* FROM wall WHERE id='".(int)$idWall."' LIMIT 1");
            return $res->fetch_assoc();
        }
        function getWallByIdUser($idUser,$limit,$loginUser,$passwordUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            /*for($i=0;$i<90000;$i++)
            {
                $this->con->query("INSERT INTO messages(text,id_user,id_groupmessage) VALUES('Hi','5','25')");
            }*/
            
            /*for($i=0;$i<10000;$i++)
            {
                $this->con->query("INSERT INTO groupmessage(name,creator,id_typemessage,lastMsg) VALUES('Hi','6','2','12')");
            }*/
            /*for($i=0;$i<500;$i++)
            {
                 $this->con->query("INSERT INTO wall(text,user_id) VALUES('dwfewfweq','5')");
            }*/
            
            /*for($i=0;$i<200000;$i++)
            {
                 $this->con->query("INSERT INTO wallattach(id_wall,file) VALUES('".$i."','storage/wall/5/58bda036b9d3e')");
            }*/
            /*for($i=0;$i<90000;$i++)
            {
                 $this->con->query("INSERT INTO users(name,login,password,network,auth_identity,status,verify) VALUES('ashotoneshot','ashot@gmail.com','f5enb.gw3Hmek','','','ds','1')");
            }*/
            $resUser=$this->con->query("SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password='".mysqli_real_escape_string($this->con, $passwordUser)."' LIMIT 1");
            $rowUser=$resUser->fetch_assoc();
            $countRes=5;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $res=$this->con->query("SELECT wall.text AS wText,wall.id AS wId,users.id AS uId,users.name AS uName FROM wall LEFT JOIN users ON users.id=wall.user_id WHERE wall.user_id='".(int)$idUser."' AND wall.id NOT IN(SELECT id_wall FROM delwall WHERE id_user='".(int)$idUser."') ORDER BY wall.id DESC LIMIT ".$limit.",".$countRes."");
            $massive[]=null;
            $massiveShared[]=null;
            $massiveWallAttach[]=null;
            $massiveIsLiked[]=null;
            $massiveWallLikes[]=null;
            $massiveIsShared[]=null;
            $massiveWallsComments[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                if($i==0){
                    $profilePhoto=$this->getUsersProfileImg($row['uId'],$token);
                    $massive['uPath']=$profilePhoto;
                }
                $massive[$i]=$row;
                $massiveShared[$i]=$this->getWhileTheWallShared($massive[$i]['wId'],$token);
                $massiveWallAttach[$i]=$this->getWallAttachById($massive[$i]['wId'],$token);
                $massiveIsLiked[$i]=$this->isCurrentUserSetLike($massive[$i]['wId'],$rowUser['id'],$token);
                $massiveWallLikes[$i]=$this->getWallLikes($massive[$i]['wId'],$token);
                $massiveIsShared[$i]=$this->isShared($massive[$i]['wId'],$rowUser['id'],$token);
                $massiveWallsComments[$i]=$this->getWallsComments($massive[$i]['wId'],1,$token);
            }
            return array($massive,$massiveShared,$massiveWallAttach,$massiveIsLiked,$massiveWallLikes,$massiveIsShared,$massiveWallsComments);
        }
        function getNewsByIdUser($loginUser,$passwordUser,$limit,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $resUser=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password='".mysqli_real_escape_string($this->con, $passwordUser)."' LIMIT 1");
            if($rowUser=$resUser->fetch_assoc())
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $countRes=5;
                $limit = $limit>0 ? $limit-1 : 0;
                if($limit>0)
                {
                    $limit=$limit*($countRes);
                }
                $res=$this->con->query("SELECT wall.text AS wText,wall.id AS wId,users.id AS uId,users.name AS uName FROM wall LEFT JOIN users ON users.id=wall.user_id WHERE wall.user_id IN(SELECT user_to FROM friends WHERE user_from='".$rowUser['id']."') AND wall.id NOT IN(SELECT id_wall FROM delwall) ORDER BY wall.id DESC LIMIT ".$limit.",".$countRes."");
                $massive[]=null;
                $massiveShared[]=null;
                $massiveWallAttach[]=null;
                $massiveIsLiked[]=null;
                $massiveWallLikes[]=null;
                $massiveIsShared[]=null;
                $massiveWallsComments[]=null;
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    if($i==0)
                    {
                        $profilePhoto=$this->getUsersProfileImg($row['uId'],$token);
                        $massive['uPath']=$profilePhoto;
                    }
                    $massive[$i]=$row;
                    $massiveShared[$i]=$this->getWhileTheWallShared($massive[$i]['wId'],$token);
                    $massiveWallAttach[$i]=$this->getWallAttachById($massive[$i]['wId'],$token);
                    $massiveIsLiked[$i]=$this->isCurrentUserSetLike($massive[$i]['wId'],$rowUser['id'],$token);
                    $massiveWallLikes[$i]=$this->getWallLikes($massive[$i]['wId'],$token);
                    $massiveIsShared[$i]=$this->isShared($massive[$i]['wId'],$rowUser['id'],$token);
                    $massiveWallsComments[$i]=$this->getWallsComments($massive[$i]['wId'],1,$token);
                }
                return array($massive,$massiveShared,$massiveWallAttach,$massiveIsLiked,$massiveWallLikes,$massiveIsShared,$massiveWallsComments);
            }
        }
        function getWhileTheWallShared($idWall,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $massive[]=null;
            $row=$this->getWallById($idWall);
            $i=0;
            for(;$row['shared_id']>0;$i++)
            {
                $idWall=$row['shared_id'];
                $massive[$i]=$idWall;
                $row=$this->getWallById($idWall);
            }
            if(!$i)
            {
                return null;
            }
            $massive=join(",",$massive);
            $massiveShared[]=null;
            $res=$this->con->query("SELECT wall.id AS wId,wall.shared_id AS wShared_id,wall.text AS wText,users.id AS uId,users.name AS uName FROM wall LEFT JOIN users ON users.id=wall.user_id WHERE wall.id IN(".$massive.") ORDER BY wall.id DESC");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massiveShared[$i]=$row;
                $massiveShared[$i]['uPath']=$this->getUsersProfileImg($row['uId'],$token);
            }
            return $massiveShared;
        }
        function getWallAttachById($idWall,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $massive[]=null;
            $res=$this->con->query("SELECT* FROM wallattach WHERE id_wall='".(int)$idWall."'");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }
        function getWallLikes($idWall,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT id FROM likes WHERE active='1' AND id_wall='".$idWall."' GROUP BY id_user");
            $resCountLikes=$res->num_rows;
            return $resCountLikes;
        }
        function isCurrentUserSetLike($idWall,$idUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT id FROM likes WHERE active='1' AND id_wall='".(int)$idWall."' AND id_user='".(int)$idUser."'");
            $isLiked=$res->num_rows;
            return $isLiked;
        }
        function isShared($idWall,$idUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT DISTINCT id FROM wall WHERE shared_id='".(int)$idWall."' AND user_id='".(int)$idUser."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                $res=$this->con->query("SELECT DISTINCT id FROM delwall WHERE id_wall='".$row['id']."' AND id_user='".(int)$idUser."' LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    return false;
                }
                return true;
            }
            return false;
        }
        function getWallsComments($idWall,$limit,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $countRes=5;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $massive[]=null;
            $res=$this->con->query("SELECT users.name AS uName,comments.id AS cId,comments.id_user AS cId_user,comments.text AS cText FROM comments LEFT JOIN users ON users.id=comments.id_user WHERE comments.id_wall='".(int)$idWall."' ORDER BY comments.id DESC LIMIT ".$limit.",".$countRes."");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
                $flag=false;
                    if($handle = opendir('storage/profileimg/'.$row['cId_user'].'/'))
                    {
                        while(false !== ($file = readdir($handle)))
                        {
                            if($file != "." && $file != "..")
                            {
                                $massive[$i]['uPath']='storage/profileimg/'.$row['cId_user']."/".$file;
                                $flag=true;
                                break;
                            }
                        }
                        closedir($handle);
                    }
                    if(!$flag)
                    {
                        $massive[$i]['uPath']="anonym.png";
                    }  
            }
            return $massive;
        }
        function updateWallText($idWall,$text,$loginUser,$passwordUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(mb_strlen($text)<15000&&mb_strlen($text)>=0)
            {
                $res=$this->con->query("SELECT id FROM wall WHERE user_id=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password='".mysqli_real_escape_string($this->con, $passwordUser)."' LIMIT 1)");
                if($res->num_rows)
                {
                    $res=$this->con->query("UPDATE wall SET text='".mysqli_real_escape_string($this->con, $text)."' WHERE id='".(int)$idWall."'");
                    return $this->con->query("SELECT DISTINCT text FROM wall WHERE id='".(int)$idWall."' LIMIT 1")->fetch_assoc()['text'];
                }
                return false;
            }
            return false;
        }
        function shareTheWall($login,$password,$idWall,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $rowSharedWall=$this->getWhileTheWallShared($idWall,$token);
            $shared_id=$rowSharedWall['wId'];
            $resUserRow=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1")->fetch_assoc();
            if($resUserRow['baned'])
            {
                return "user_baned_error";
            }
            $res=$this->con->query("SELECT DISTINCT user_id,shared_id FROM wall WHERE id='".(int)$idWall."' LIMIT 1");
            $row=$res->fetch_assoc();
            $shared_id=$row['shared_id'];
            $firstUId=$row['user_id'];
            while($shared_id>0)
            {
                $res=$this->con->query("SELECT DISTINCT shared_id,id,user_id AS UId FROM wall WHERE id='".(int)$shared_id."' LIMIT 1");
                $row=$res->fetch_assoc();
                $shared_id=$row['shared_id'];
                $firstUId=$row['user_id'];
                if($firstUId!=$resUserRow['id'])
                {
                    if($this->isInBlackList($resUserRow['id'],$firstUId,$token)||$this->isInBlackList($firstUId,$resUserRow['id'],$token))
                    {
                        return "blackList_error";
                    }
                }
                if($shared_id==0)
                {
                    $shared_id=(int)$row['id'];
                    break;
                }
            }
            if(!$shared_id)
            {
                if($this->isInBlackList($resUserRow['id'],$firstUId,$token)||$this->isInBlackList($firstUId,$resUserRow['id'],$token))
                {
                    return "blackList_error";
                }
            }

            $res=$this->con->query("SELECT id FROM delwall WHERE id_wall=(SELECT id FROM wall WHERE user_id='".(int)$resUserRow['id']."' AND shared_id='".(int)$idWall."')");
            if($row=$res->fetch_assoc())
            {
                $this->con->query("DELETE FROM delwall WHERE id='".$row['id']."'");
            }
            else
            {
                $issetWallRes=$this->con->query("SELECT id FROM wall WHERE user_id='".(int)$resUserRow['id']."' AND shared_id='".(int)$idWall."'");
                if(!$row=$issetWallRes->fetch_assoc())
                {
                    $this->con->query("INSERT INTO wall(user_id,shared_id) VALUES('".(int)$resUserRow['id']."','".(int)$idWall."')");
                }
            }
            return $this->setLikeToWall($login,$password,$idWall,$token);
        }
        function deleteWall($idWall,$token,$login,$password)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT DISTINCT id,user_id FROM wall WHERE id='".(int)$idWall."' AND user_id=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1) LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                $this->con->query("INSERT INTO delwall(id_user,id_wall) VALUES('".$row['user_id']."','".(int)$idWall."')");
                $resWallAttach=$this->con->query("SELECT* FROM wallattach WHERE id_wall='".(int)$idWall."'");

                for($j=0;$rowWallAttach=$resWallAttach->fetch_assoc();$j++)
                {
                    if($handle = opendir('storage/wall/'.$row['user_id'].'/'))
                    {
                        while(false !== ($file = readdir($handle)))
                        {
                            if($file != "." && $file != "..") 
                            {
                                if('storage/wall/'.$row['user_id'].'/'.$file==$rowWallAttach['file'])
                                {
                                    unlink('storage/wall/'.$row['user_id'].'/'.$file);
                                }
                            }
                        }
                        closedir($handle);
                    }
                }
                return "true";
            }
        }
        function getIsLikedWall($loginUser,$passwordUser,$idWall,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT active FROM likes WHERE id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password='".mysqli_real_escape_string($this->con, $passwordUser)."' LIMIT 1) AND id_wall='".(int)$idWall."'");
            $row=$res->fetch_assoc();
            return $row['active'];
        }
        function setLikeToWall($loginUser,$passwordUser,$idWall,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $resUser=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password='".mysqli_real_escape_string($this->con, $passwordUser)."' LIMIT 1");
            $rowUser=$resUser->fetch_assoc();
            if($rowUser['baned'])
            {
                return "user_baned_error";
            }
            else if($rowUser)
            {
                $res=$this->con->query("SELECT DISTINCT user_id,shared_id FROM wall WHERE id='".(int)$idWall."' LIMIT 1");
                $row=$res->fetch_assoc();
                $shared_id=$row['shared_id'];
                $firstUId=$row['user_id'];
                while($shared_id>0)
                {
                    $res=$this->con->query("SELECT DISTINCT shared_id,id,user_id AS UId FROM wall WHERE id='".(int)$shared_id."' LIMIT 1");
                    $row=$res->fetch_assoc();
                    $shared_id=$row['shared_id'];
                    $firstUId=$row['user_id'];
                    if($firstUId!=$rowUser['id'])
                    {
                        if($this->isInBlackList($rowUser['id'],$firstUId,$token)||$this->isInBlackList($firstUId,$rowUser['id'],$token))
                        {
                            return "blackList_error";
                        }
                    }
                    if($shared_id==0)
                    {
                        $shared_id=(int)$row['id'];
                        break;
                    }
                }
                if(!$shared_id)
                {
                    if($this->isInBlackList($rowUser['id'],$firstUId,$token)||$this->isInBlackList($firstUId,$rowUser['id'],$token))
                    {
                        return "blackList_error";
                    }
                }
                $idUser=$rowUser['id'];
                $res=$this->con->query("SELECT active,id_user FROM likes WHERE id_user='".$idUser."' AND id_wall='".(int)$idWall."'");
                if($row=$res->fetch_assoc())
                {
                    if($row['active']=='1')
                    {
                        $this->con->query("UPDATE likes SET active='0' WHERE id_user='".$idUser."' AND id_wall='".(int)$idWall."'");
                        if($shared_id>0)
                        {
                            $this->con->query("UPDATE likes SET active='0' WHERE id_user='".$idUser."' AND id_wall='".(int)$shared_id."'");
                        }
                    }
                    else
                    {
                        $this->con->query("UPDATE likes SET active='1' WHERE id_user='".$idUser."' AND id_wall='".(int)$idWall."'");
                        if($shared_id>0)
                        {
                            $this->con->query("UPDATE likes SET active='1' WHERE id_user='".$idUser."' AND id_wall='".(int)$shared_id."'");
                        }
                    }
                }
                else
                {
                    $this->con->query("INSERT INTO likes(id_user,id_wall,active) VALUES('".$idUser."','".(int)$idWall."','1')");
                    $this->con->query("INSERT INTO likes(id_user,id_wall,active) VALUES('".$idUser."','".(int)$shared_id."','1')");
                }
                return $this->getIsLikedWall($loginUser,$passwordUser,$idWall,$token);
            }
        }
        function unlinkTemporaryImg($img,$loginUser,$passwordUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $loginUser)."' AND password='".mysqli_real_escape_string($this->con, $passwordUser)."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                if($row['baned'])
                {
                    return "user_baned_error";
                }
                unlink('storage/temporary/'.$row['id'].'/'.$img);
                return true;
            }
            return false;
        }
        function insertComment($login,$password,$idWall,$text,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $resWall=$this->con->query("SELECT DISTINCT user_id AS UId FROM wall WHERE id='".(int)$idWall."' LIMIT 1");
            $rowWall=$resWall->fetch_assoc();
            $firstUId=$rowWall['UId'];
            $res=$this->con->query("SELECT DISTINCT id,name,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1");
            $row=$res->fetch_assoc();
            if($row['baned'])
            {
                return "user_baned_error";
            }
            if($this->isInBlackList($row['id'],$firstUId,$token)||$this->isInBlackList($firstUId,$row['id'],$token))
            {
                return "blackList_error";
            }
            if($row)
            {
                if(mb_strlen($text)<15000&&mb_strlen($idWall)>0)
                {
                    $this->con->query("INSERT INTO comments(id_user,id_wall,text) VALUES('".(int)$row['id']."','".(int)$idWall."','".mysqli_real_escape_string($this->con,$text)."')");
                    $massive[4]=$this->con->insert_id;
                }
                $flag=false;
                if($handle = opendir('storage/profileimg/'.$row['id'].'/'))
                {
                    while(false !== ($file = readdir($handle)))
                    {
                        if($file != "." && $file != "..") 
                        {
                            $flag=true;
                            $massive[3]='storage/profileimg/'.$row['id'].'/1';
                        }
                    } 
                    closedir($handle);
                }
                if(!$flag)
                {
                    $massive[3]='anonym.png';
                }
                $massive[]=null;
                $massive[0]=$row['id'];
                $massive[1]=$row['name'];
                $massive[2]=htmlspecialchars($text);
                return ($massive);
            }
        }
        function deleteComment($login,$password,$idComment,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {  
                if($row['baned'])
                {
                    return "user_baned_error";
                }
                $resWallUser=$this->con->query("SELECT DISTINCT user_id AS id_user FROM wall WHERE id=(SELECT DISTINCT id_wall FROM comments WHERE id='".(int)$idComment."' LIMIT 1) LIMIT 1");
                $rowWallUser=$resWallUser->fetch_assoc();
                
                if($rowWallUser['id_user']==$row['id'])
                {
                    $this->con->query("DELETE FROM comments WHERE id='".(int)$idComment."'");
                }
                $this->con->query("DELETE FROM comments WHERE (id='".(int)$idComment."') AND id_user='".$row['id']."'");
                return "true";
            }
        }
    }
?>