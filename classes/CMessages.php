<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    include_once("classes/CCheck.php");
    class Messages{
        use Check;
        private $con;
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
        function __construct($con) {
            $this->con=$con;
        }
        function AddMemberToGroup($login,$password,$idGroup,$Members,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Members=explode(",",$Members);
            if($rowUser=$this->getUsersId($login,$password,$token))
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $res=$this->con->query("SELECT* FROM infomessage WHERE id_user='".$rowUser['id']."' AND id_groupmessage='".(int)$idGroup."' AND action='0'");
                if($row=$res->fetch_assoc())
                {
                    for($i=0;$i<5;$i++)
                    {
                        $curMemeber=$this->getUserById($Members[$i],$token);
                        if(!$curMemeber||$curMemeber['baned'])
                        {
                            continue;
                        }
                        if($this->isInBlackList($rowUser['id'],$Members[$i],$token)||
                        $this->isInBlackList($Members[$i],$rowUser['id'],$token))
                        {
                            continue;
                        }
                        $CheckCurMember=$this->con->query("SELECT DISTINCT id,action FROM infomessage WHERE id_user='".(int)$Members[$i]."' AND id_groupmessage='".(int)$idGroup."' LIMIT 1");
                        if($rowCheckCurMember=$CheckCurMember->fetch_assoc())
                        {
                            if($rowCheckCurMember['action']=='1')
                            {
                                $this->con->query("UPDATE infomessage SET action='0' WHERE id_user='".(int)$Members[$i]."' AND id_groupmessage='".(int)$idGroup."'");
                                $this->con->query("INSERT INTO messages(id_user,id_groupmessage,id_typeofmessage,id_infomessage) VALUES('".(int)$rowUser['id']."','".(int)$idGroup."','1','".(int)$rowCheckCurMember['id']."')");
                            }
                            continue;
                            //$this->con->query("UPDATE infomessage SET action='0' WHERE id_user='".(int)$Members[$i]."' AND id_groupmessage='".(int)$idGroup."'");
                            //$this->con->query("INSERT INTO messages(id_user,id_groupmessage,id_typeofmessage,id_infomessage) VALUES('".(int)$rowUser['id']."','".(int)$idGroup."','1','".$rowCheckCurMember['id']."')");
                        }
                        else
                        {
                            $this->con->query("INSERT INTO infomessage(id_user,id_groupmessage) VALUES('".(int)$Members[$i]."','".(int)$idGroup."')");
                            $idInfomessage=$this->con->insert_id;
                            $this->con->query("INSERT INTO messages(id_user,id_groupmessage,id_typeofmessage,id_infomessage) VALUES('".(int)$rowUser['id']."','".(int)$idGroup."','1','".(int)$idInfomessage."')");
                        }    
                    }
                    return true;
                }
            }
            return false;
        }
        function CreateDialog($login,$password,$idUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if($rowUser=$this->getUsersId($login,$password,$token))
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $curUser=$this->getUserById($idUser,$token);
                if(!$curUser||$curUser['baned'])
                {
                    return "user_error";
                }
                else if($rowUser['id']==(int)$idUser)
                {
                    return "user_error";
                }
                else if($this->isInBlackList($rowUser['id'],$idUser,$token)||
                        $this->isInBlackList($idUser,$rowUser['id'],$token))
                {
                    return "blackList_error";
                }
                $res=$this->con->query("SELECT DISTINCT id_groupmessage FROM infomessage WHERE id_user='".$rowUser['id']."' AND id_groupmessage IN(SELECT id FROM groupmessage WHERE id_typemessage='1' AND id IN(SELECT id_groupmessage FROM infomessage WHERE id_user='".(int)$idUser."')) LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    return $row['id_groupmessage'];
                }
                else
                {
                    $this->con->query("INSERT INTO groupmessage(creator,id_typemessage) VALUES('".$rowUser['id']."','1')");
                    $idGroup=$this->con->insert_id;
                    $this->con->query("INSERT INTO infomessage(id_user,id_groupmessage) VALUES('".$rowUser['id']."','".$idGroup."')");
                    $this->con->query("INSERT INTO infomessage(id_user,id_groupmessage) VALUES('".(int)$idUser."','".$idGroup."')");
                    return $idGroup;
                }
            }
        }
        function CreateGroupMessage($login,$password,$Addable,$nameGroup,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Addable=explode(",",$Addable);
            if($rowUser=$this->getUsersId($login,$password,$token))
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $flag=false;
                $this->con->autocommit(false);
                $this->con->query("INSERT INTO groupmessage(name,creator,id_typemessage) VALUES('".mysqli_real_escape_string($this->con,$nameGroup)."','".$rowUser['id']."','2')");
                $idGroup=$this->con->insert_id;
                $this->con->query("INSERT INTO infomessage(id_user,id_groupmessage) VALUES('".$rowUser['id']."','".$idGroup."')");
                //$this->con->query("INSERT INTO messages(id_user,id_groupmessage,text,id_typeofmessage) VALUES('".(int)$rowUser['id']."','".(int)$idGroup."',\"Создал беседу\",'1')");
                for($i=0;$i<5;$i++)
                {
                    $curAddableUser=$this->getUserById($Addable[$i],$token);
                    if(!$curAddableUser||$curAddableUser['baned'])
                    {
                        continue;
                    }
                    else if($this->isInBlackList($rowUser['id'],$curAddableUser['id'],$token)||
                        $this->isInBlackList($curAddableUser['id'],$rowUser['id'],$token))
                    {
                        continue;
                    }
                    $this->con->query("INSERT INTO infomessage(id_user,id_groupmessage) VALUES('".(int)$Addable[$i]."','".$idGroup."')");
                    $idInfomessage=$this->con->insert_id;
                    $this->con->query("INSERT INTO messages(id_user,id_groupmessage,id_typeofmessage,id_infomessage) VALUES('".(int)$rowUser['id']."','".(int)$idGroup."','1','".(int)$idInfomessage."')");
                    $lastMsg=$this->con->insert_id;
                    $this->con->query("UPDATE groupmessage SET lastMsg='".$lastMsg."' WHERE id='".$idGroup."'");
                    $this->con->query("UPDATE infomessage SET lastMsg='".$lastMsg."' WHERE id_groupmessage='".$idGroup."' AND id_user='".$rowUser['id']."'");
                    $flag=true;
                }
                if(!$flag)
                {
                    $this->con->rollback();
                }
                $this->con->commit();
                return $flag;
            }
            return false;
        }
        function getMessagesGroups($login,$password,$name,$limit,$token)
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
            if($rowUser=$this->getUsersId($login,$password,$token))
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $massive[]=null;
                $lastMsg=0;
                $res=$this->con->query("SELECT m.text AS mLastText, im.lastMsg AS IMLastMsg,groupmessage.lastMsg AS maxMId,users.id AS UId,users.name AS UName,users.realName AS URealName,infomessage.id_groupmessage AS IId_groupmessage,
                groupmessage.id_typemessage AS GIdtypemessage,groupmessage.name AS GName FROM users LEFT JOIN infomessage ON infomessage.id_user=users.id LEFT JOIN groupmessage ON 
                groupmessage.id=infomessage.id_groupmessage LEFT JOIN infomessage im ON im.id_groupmessage=groupmessage.id AND im.id_user='".$rowUser['id']."' LEFT JOIN messages m ON m.id=im.lastMsg WHERE infomessage.id_groupmessage IN (SELECT id_groupmessage FROM infomessage WHERE id_user='".(int)$rowUser['id']."' GROUP BY id_groupmessage) 
                 AND users.id!='".(int)$rowUser['id']."' AND ((users.name LIKE '%".mysqli_real_escape_string($this->con,$name)."%' AND groupmessage.id_typemessage='1') OR (groupmessage.name 
                 LIKE '%".mysqli_real_escape_string($this->con,$name)."%' AND groupmessage.id_typemessage='2')) AND EXISTS(SELECT DISTINCT id FROM messages WHERE id_groupmessage=groupmessage.id LIMIT 1) GROUP BY infomessage.id_groupmessage ORDER BY maxMId DESC LIMIT ".$limit.",".$countRes."");
                for($i=0,$j=0;$row=$res->fetch_assoc();$i++)
                {
                    //if(!$this->getGroupsMessagesAnonym($login,$password,$row['IId_groupmessage'],1,$token)[0])
                    if(!$this->getGroupsMessagesAnonym($login,$password,$row['IId_groupmessage'],1,$token))
                    {
                        continue;
                    }
                    $massive[$j]=$row;
                    $profilePhoto=$this->getUsersProfileImg($massive[$j]['UId'],$token);
                    $massive[$j]['PPath']=$profilePhoto;

                    $resInfoMessage=$this->con->query("SELECT id FROM messages WHERE id_infomessage=(SELECT id FROM infomessage WHERE id_groupmessage='".$massive[$j]['IId_groupmessage']."' AND id_user='".$rowUser['id']."') AND id_typeofmessage='3'");
                    if($rowInfoMessage=$resInfoMessage->fetch_assoc())
                    {
                        $massive[$j]['lastRemovedId']=$rowInfoMessage['id'];
                    }
                    else
                    {
                        $resLastRemoved=$this->con->query("SELECT DISTINCT id_message FROM delmessage WHERE id_user='".$rowUser['id']."' AND id_message='".$massive[$j]['maxMId']."' LIMIT 1");
                        if($rowLastRemoved=$resLastRemoved->fetch_assoc())
                        {
                            $massive[$j]['lastRemovedId']=$massive[$j]['IMLastMsg'];
                        }
                    }
                    $j++;
                }
                return $massive;
            }
            return false;
        }
        function getGroupsMessagesAnonym($login,$password,$idGroup,$limit,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if($rowUser=$this->getUsersId($login,$password,$token))
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $countRes=10;
                $limit = $limit>0 ? $limit-1 : 0;
                if($limit>0)
                {
                    $limit=$limit*($countRes);
                }
                // AND action='0'
                $resInfoMessage=$this->con->query("SELECT id FROM messages WHERE id_infomessage=(SELECT id FROM infomessage WHERE id_groupmessage='".(int)$idGroup."' AND id_user='".$rowUser['id']."') AND id_typeofmessage='3'");
                $rowInfoMessage=$resInfoMessage->fetch_assoc();
                $isRowInfoRequest=$rowInfoMessage['id']?" AND messages.id<='".$rowInfoMessage['id']."'":"";
                $res=$this->con->query("SELECT u.id AS UAddUser,u.name AS UAddName,messages.id_typeofmessage AS MType,users.id AS UId,users.name AS UName,infomessage.id_groupmessage AS IId_groupmessage,infomessage.id AS IId,
                messages.text AS MText,messages.id AS MId,groupmessage.id_typemessage AS GIdtypemessage,groupmessage.name AS GName FROM users LEFT JOIN infomessage ON 
                infomessage.id_user=users.id LEFT JOIN groupmessage ON groupmessage.id=infomessage.id_groupmessage LEFT JOIN messages ON messages.id_groupmessage=infomessage.id_groupmessage 
                AND messages.id_user=users.id LEFT JOIN infomessage AS im ON im.id=messages.id_infomessage LEFT JOIN users u ON im.id_user=u.id WHERE infomessage.id_groupmessage IN (SELECT id_groupmessage FROM infomessage WHERE 
                id_user='".$rowUser['id']."' AND id_groupmessage='".(int)$idGroup."') AND (messages.text IS NOT NULL OR messages.id_typeofmessage='1' OR messages.id_typeofmessage='3') AND messages.id NOT IN(SELECT id_message FROM delmessage WHERE 
                id_user='".$rowUser['id']."')".$isRowInfoRequest." ORDER BY messages.id DESC LIMIT ".$limit.",".$countRes."");
                $massive[]=null;
                $lastMsg=0;
                /*for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    if($i==0)
                    {
                        $lastMsg=$row['MId'];
                    }
                    $massive[$i]=$row;
                    $profilePhoto=$this->getUsersProfileImg($massive[$i]['UId']);
                    $massive[$i]['PPath']=$profilePhoto;
                }
                if($lastMsg&&$limit/$countRes==0)
                {
                    $this->con->query("UPDATE infomessage SET infomessage.lastMsg='".$lastMsg."' WHERE infomessage.id_groupmessage='".(int)$idGroup."' AND id_user='".$rowUser['id']."'");
                }*/
                if($row=$res->fetch_assoc())
                {
                    return true;
                }
            }
            return false;
        }
        function getGroupsMessages($login,$password,$idGroup,$limit,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if($rowUser=$this->getUsersId($login,$password,$token))
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $countRes=20;
                $limit = $limit>0 ? $limit-1 : 0;
                if($limit>0)
                {
                    $limit=$limit*($countRes);
                }
                // AND action='0'
                $resInfoMessage=$this->con->query("SELECT id FROM messages WHERE id_infomessage=(SELECT id FROM infomessage WHERE id_groupmessage='".(int)$idGroup."' AND id_user='".$rowUser['id']."') AND id_typeofmessage='3'");
                $rowInfoMessage=$resInfoMessage->fetch_assoc();
                $isRowInfoRequest=$rowInfoMessage['id']?" AND messages.id<='".$rowInfoMessage['id']."'":"";
                $res=$this->con->query("SELECT u.id AS UAddUser,u.name AS UAddName,messages.id_typeofmessage AS MType,users.id AS UId,users.name AS UName,infomessage.id_groupmessage AS IId_groupmessage,infomessage.id AS IId,
                messages.text AS MText,messages.id AS MId,groupmessage.id_typemessage AS GIdtypemessage,groupmessage.name AS GName FROM users LEFT JOIN infomessage ON 
                infomessage.id_user=users.id LEFT JOIN groupmessage ON groupmessage.id=infomessage.id_groupmessage LEFT JOIN messages ON messages.id_groupmessage=infomessage.id_groupmessage 
                AND messages.id_user=users.id LEFT JOIN infomessage AS im ON im.id=messages.id_infomessage LEFT JOIN users u ON im.id_user=u.id WHERE infomessage.id_groupmessage IN (SELECT id_groupmessage FROM infomessage WHERE 
                id_user='".$rowUser['id']."' AND id_groupmessage='".(int)$idGroup."') AND (messages.text IS NOT NULL OR messages.id_typeofmessage='1' OR messages.id_typeofmessage='3') AND messages.id NOT IN(SELECT id_message FROM delmessage WHERE 
                id_user='".$rowUser['id']."')".$isRowInfoRequest." ORDER BY messages.id DESC LIMIT ".$limit.",".$countRes."");
                $massive[]=null;
                $lastMsg=0;
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    if($i==0)
                    {
                        $lastMsg=$row['MId'];
                    }
                    $massive[$i]=$row;
                    $profilePhoto=$this->getUsersProfileImg($massive[$i]['UId'],$token);
                    $massive[$i]['PPath']=$profilePhoto;
                }
                if($lastMsg&&$limit/$countRes==0)
                {
                    $this->con->query("UPDATE infomessage SET infomessage.lastMsg='".$lastMsg."' WHERE infomessage.id_groupmessage='".(int)$idGroup."' AND id_user='".$rowUser['id']."'");
                }
                return $massive;
            }
            return false;
        }
        function getMessagesPartners($login,$password,$idGroup,$limit,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if($rowUser=$this->getUsersId($login,$password,$token))
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
                if($this->IsInGroup($login,$password,$idGroup,$token))
                {
                    $res=$this->con->query("SELECT groupmessage.creator AS GCreator,users.id AS UId,users.name AS UName FROM users LEFT JOIN infomessage ON 
                    infomessage.id_user=users.id LEFT JOIN groupmessage ON groupmessage.id=infomessage.id_groupmessage WHERE 
                    infomessage.id_groupmessage IN (SELECT id_groupmessage FROM infomessage WHERE id_user='".$rowUser['id']."' AND id_groupmessage='".(int)$idGroup."') AND infomessage.action='0' ORDER BY 
                    groupmessage.creator DESC LIMIT ".$limit.",".$countRes."");
                    $massive[]=null;
                    for($i=0;$row=$res->fetch_assoc();$i++)
                    {
                        $massive[$i]=$row; 
                        $profilePhoto=$this->getUsersProfileImg($massive[$i]['UId'],$token);
                        $massive[$i]['PPath']=$profilePhoto;
                    }
                    return $massive;
                }
                return "isNotInGroup_error";
            }
        }
        function delMemberFromGroupMessage($login,$password,$idGroup,$idMember,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if($rowUser=$this->getUsersId($login,$password,$token))
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                if($idMember!=$rowUser['id'])
                {
                    $this->con->query("UPDATE infomessage SET action='1' WHERE id_groupmessage=(SELECT id FROM groupmessage WHERE creator='".$rowUser['id']."' AND id='".(int)$idGroup."') AND 
                    id_user='".(int)$idMember."'");
                    $res=$this->con->query("SELECT id FROM infomessage WHERE id_user='".(int)$idMember."' AND action='1' AND id_groupmessage='".(int)$idGroup."' LIMIT 1");
                    if($row=$res->fetch_assoc())
                    {
                        $this->con->query("INSERT INTO messages(id_user,id_groupmessage,id_typeofmessage,id_infomessage) VALUES('".(int)$rowUser['id']."','".(int)$idGroup."','3','".$row['id']."')");
                    }
                }
                else
                {
                    $this->con->query("UPDATE infomessage SET action='1' WHERE id_groupmessage='".(int)$idGroup."' AND id_user='".$rowUser['id']."'");
                    $res=$this->con->query("SELECT id FROM infomessage WHERE id_user='".(int)$idMember."' AND action='1' AND id_groupmessage='".(int)$idGroup."' LIMIT 1");
                    if($row=$res->fetch_assoc())
                    {
                        $this->con->query("INSERT INTO messages(id_user,id_groupmessage,id_typeofmessage,id_infomessage) VALUES('".(int)$rowUser['id']."','".(int)$idGroup."','3','".$row['id']."')");
                    }
                }
                return true;
            }
            return false;
        }
        function IsInGroup($login,$password,$idGroup,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT id FROM infomessage WHERE action='0' AND id_groupmessage='".(int)$idGroup."' AND id_user=(SELECT id FROM users WHERE login='".mysqli_real_escape_string($this->con,$login)."' AND password='".mysqli_real_escape_string($this->con,$password)."')");
            $row_cnt = $res->num_rows;
            if($row_cnt>0)
            {
                return true;
            }
            return false;
        }
        function isGroupPersonal($login,$password,$idGroup,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if((int)$idGroup<1)
            {
                return false;
            }
            if($this->IsInGroup($login,$password,$idGroup,$token))
            {
                $typeMessageRes=$this->con->query("SELECT DISTINCT id_typemessage FROM groupmessage WHERE id='".(int)$idGroup."' LIMIT 1");
                return $typeMessageRes->fetch_assoc()['id_typemessage'];
            }  
            return false;
        }
        function SendMessage($login,$password,$idGroup,$text,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(!trim($text))
            {
                return "text_empty_error";
            }
            $rowUser=$this->getUsersId($login,$password,$token);
            if($curTypeMessage=$this->isGroupPersonal($login,$password,$idGroup,$token))
            {
                if($curTypeMessage==1)
                {
                    $rowPartners=$this->getMessagesPartners($login,$password,$idGroup,1,$token);
                    if($this->isInBlackList($rowPartners[0]['UId'],$rowPartners[1]['UId'],$token)||
                        $this->isInBlackList($rowPartners[1]['UId'],$rowPartners[0]['UId'],$token))
                    {
                        return "blackList_error";
                    }
                    else if($this->getUserById($rowPartners[0]['UId'],$token)['baned']||$this->getUserById($rowPartners[1]['UId'],$token)['baned'])
                    {
                        return "user_baned_error";
                    }
                }
                $this->con->query("INSERT INTO messages(text,id_user,id_groupmessage) VALUES('".mysqli_real_escape_string($this->con,$text)."','".(int)$rowUser['id']."','".(int)$idGroup."')");
                $insertedId=$this->con->insert_id;
                $this->con->query("UPDATE groupmessage SET lastMsg='".$insertedId."' WHERE id='".(int)$idGroup."'");
                $this->con->query("UPDATE infomessage SET lastMsg='".$insertedId."' WHERE id_groupmessage='".(int)$idGroup."' AND id_user='".(int)$rowUser['id']."'");
                return $insertedId;  
            }
            return "isNotInGroup_error";
        }
        function DeleteMessage($login,$password,$idMessage,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $rowUser=$this->getUsersId($login,$password,$token);
            $res=$this->con->query("SELECT id_groupmessage FROM messages WHERE id='".(int)$idMessage."'");
            if($row=$res->fetch_assoc())
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                if($this->getGroupsMessages($login,$password,$row['id_groupmessage'],1,$token)&&$rowUser)
                {
                    $this->con->query("INSERT INTO delmessage(id_user,id_message) VALUES('".(int)$rowUser['id']."','".(int)$idMessage."')");
                    return true;
                }
            }
            return true;
        }
        function DeleteMessages($login,$password,$arrayMessages,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $rowUser=$this->getUsersId($login,$password,$token);
            $AllArrayMessages=explode(",",$arrayMessages);
            if($AllArrayMessages[0]=="") return;
            $res=$this->con->query("SELECT id_groupmessage FROM messages WHERE id='".(int)$AllArrayMessages[0]."'");
            if($row=$res->fetch_assoc())
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                if($this->getGroupsMessages($login,$password,$row['id_groupmessage'],1,$token)&&$rowUser)
                {
                    for($i=0;$i<count($AllArrayMessages)-1;$i++) {
                    $this->con->query("INSERT INTO delmessage(id_user,id_message) VALUES('".(int)$rowUser['id']."','".(int)$AllArrayMessages[$i]."')");
                    }
                    return true;
                }
            }
            return true;
        }
    }
?>