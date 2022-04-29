<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    include_once("classes/CCheck.php");
    class Basket{
        use Check;
        private $con;
        function __construct($con) {
            $this->con=$con;
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
        function getOutFinishedOrders($login,$password,$limit,$token)
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
            $massiveIdBaskets[]=null;
            $res=$this->con->query("SELECT id FROM basket WHERE id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1) AND action='4' ORDER BY id DESC LIMIT ".$limit.",".$countRes."");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massiveIdBaskets[$i]=$row['id'];
            } 
            $massiveIdBaskets=join("','",$massiveIdBaskets);   

            $res=$this->con->query("SELECT goodsDeliverCountry.id_country AS GDCIdCountry,goodsDeliverCountry.minCount AS GDCMinCount,goodsDeliverCountry.price AS GDCPrice,goodsDeliverCountry.deliveryDays AS gDCDeliveryDays, basket.adressBasket AS bAdressBasket,basket.price AS bPrice,users.id AS uId,users.name AS uName,country_.country_name_en AS cName,InfoSetAndPropertyGoods.price AS ispgPrice,goods.price AS gPrice, goodsimg.image AS giImage,basket.id AS bId,basket.count AS bCount,goods.id AS gId,goods.currency AS gCurrency,goods.name AS gName,settings.name AS sName,property.name AS pName,c.currency AS cCurrency,property.id AS pId FROM basket 
            LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsDeliverCountry ON (goodsDeliverCountry.id_goods=basket.id_goods) AND (basket.id_country=goodsDeliverCountry.id_country) LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND goodsimg.is_main='1' LEFT JOIN 
            InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN users ON users.id=goods.id_user LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id IN('".$massiveIdBaskets."') AND basket.action='4' ORDER BY bId DESC");


            $resCount=$this->con->query("SELECT basket.id AS bId FROM basket 
            LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND goodsimg.is_main='1' LEFT JOIN 
            InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN users ON users.id=goods.id_user LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id_user=(SELECT DISTINCT id FROM users WHERE 
            login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1) AND basket.action='4' GROUP BY bId");

            $row[]=null;
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $this->con->query("UPDATE basket SET checked_userFrom='1' WHERE basket.id='".$row['bId']."'");
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['uId'],$token);
                $massive[$i]['piPath']=$profilePhoto;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            return $massive;
        }
        function getFinishedOrders($login,$password,$limit,$token)
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
            $massiveIdBaskets[]=null;
            $res=$this->con->query("SELECT id FROM basket WHERE id_goods IN(SELECT id FROM goods WHERE id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1)) AND action='4' ORDER BY id DESC LIMIT ".$limit.",".$countRes."");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massiveIdBaskets[$i]=$row['id'];
            } 
            $massiveIdBaskets=join("','",$massiveIdBaskets);   

            $res=$this->con->query("SELECT goodsDeliverCountry.id_country AS GDCIdCountry,goodsDeliverCountry.minCount AS GDCMinCount,goodsDeliverCountry.price AS GDCPrice,goodsDeliverCountry.deliveryDays AS gDCDeliveryDays,basket.price AS bPrice, basket.adressBasket AS bAdressBasket, goods.price AS gPrice,users.id AS uId,users.name AS uName,users.realName AS uRealName,country_.country_name_en AS cName,InfoSetAndPropertyGoods.price AS ispgPrice,goodsimg.image AS giImage,basket.id AS bId,basket.count AS bCount,goods.id AS gId,goods.currency AS gCurrency,goods.name AS gName,
            settings.name AS sName,property.name AS pName,property.id AS pId, c.currency AS cCurrency FROM basket LEFT JOIN users ON users.id=basket.id_user LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsDeliverCountry ON (goodsDeliverCountry.id_goods=basket.id_goods) AND (basket.id_country=goodsDeliverCountry.id_country) LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND 
            goodsimg.is_main='1' LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id IN('".$massiveIdBaskets."') AND basket.action='4' ORDER BY bId DESC");

            $resCount=$this->con->query("SELECT basket.id AS bId FROM basket LEFT JOIN users ON users.id=basket.id_user LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND 
            goodsimg.is_main='1' LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id IN('".$massiveIdBaskets."') AND basket.action='4' GROUP BY bId");



            $row[]=null;
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $this->con->query("UPDATE basket SET checked_userTo='1' WHERE basket.id='".$row['bId']."'");
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['uId'],$token);
                $massive[$i]['piPath']=$profilePhoto;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            return $massive;
        }
        function receivedOrder($login,$password,$idBasket,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("UPDATE basket SET action='4',checked_userTo='0',checked_userFrom='1' WHERE action='2' AND id='".(int)$idBasket."' AND id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND 
            password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1)");
            return $res;
        }
        function cancelOrder($login,$password,$idBasket,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("UPDATE basket SET action='3',checked_userTo='1',checked_userFrom='0' WHERE action='1' AND id='".(int)$idBasket."' AND basket.id_goods IN(SELECT id FROM goods WHERE id_user=(SELECT DISTINCT id FROM users WHERE 
            login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1))");
            return $res;
        }
        function sellGoods($login,$password,$idBasket,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("UPDATE basket SET action='2',checked_userTo='1',checked_userFrom='0' WHERE id='".(int)$idBasket."' AND basket.id_goods IN(SELECT 
            id FROM goods WHERE id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1)) AND action='1'");
            return $res;
        }
        function getOrders($login,$password,$limit,$token)
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
            $massiveIdBaskets[]=null;
            $res=$this->con->query("SELECT id FROM basket WHERE id_goods IN(SELECT id FROM goods WHERE id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1)) AND action='1' ORDER BY id DESC LIMIT ".$limit.",".$countRes."");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massiveIdBaskets[$i]=$row['id'];
            } 
            $massiveIdBaskets=join("','",$massiveIdBaskets);   

            $res=$this->con->query("SELECT goodsDeliverCountry.id_country AS GDCIdCountry,goodsDeliverCountry.minCount AS GDCMinCount,goodsDeliverCountry.price AS GDCPrice,goodsDeliverCountry.deliveryDays AS gDCDeliveryDays, basket.adressBasket AS bAdressBasket,basket.price AS bPrice,goods.price AS gPrice,users.id AS uId,users.name AS uName,users.phone AS uPhone,users.realName AS uRealName,country_.country_name_en AS cName,InfoSetAndPropertyGoods.price AS ispgPrice,goodsimg.image AS giImage,basket.id AS bId,basket.count AS bCount,goods.id AS gId,goods.currency AS gCurrency,goods.name AS gName,
            settings.name AS sName,property.name AS pName,property.id AS pId,c.currency AS cCurrency FROM basket LEFT JOIN users ON users.id=basket.id_user LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsDeliverCountry ON (goodsDeliverCountry.id_goods=basket.id_goods) AND (basket.id_country=goodsDeliverCountry.id_country) LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND 
            goodsimg.is_main='1' LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id IN('".$massiveIdBaskets."') AND basket.action='1' ORDER BY bId DESC");


            $resCount=$this->con->query("SELECT basket.id AS bId FROM basket LEFT JOIN users ON users.id=basket.id_user LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND 
            goodsimg.is_main='1' LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id IN('".$massiveIdBaskets."') AND basket.action='1' GROUP BY bId");
            $row=[];
            $massive=[];
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $this->con->query("UPDATE basket SET checked_userTo='1' WHERE basket.id='".$row['bId']."'");
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['uId'],$token);
                $massive[$i]['piPath']=$profilePhoto;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            return $massive;
        }
        function getOutOrders($login,$password,$limit,$token)
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
            $massiveIdBaskets[]=null;
            $res=$this->con->query("SELECT id FROM basket WHERE id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1) AND action='1' ORDER BY id DESC LIMIT ".$limit.",".$countRes."");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massiveIdBaskets[$i]=$row['id'];
            } 
            $massiveIdBaskets=join("','",$massiveIdBaskets);  
            
            
            $res=$this->con->query("SELECT goodsDeliverCountry.id_country AS GDCIdCountry,goodsDeliverCountry.minCount AS GDCMinCount,goodsDeliverCountry.price AS GDCPrice,goodsDeliverCountry.deliveryDays AS gDCDeliveryDays, basket.id_country AS bIdCountry,basket.price AS bPrice,basket.adressBasket AS bAdressBasket,users.id AS uId, users.name AS uName,country_.country_name_en AS cName,country_.currency AS cCurrency,InfoSetAndPropertyGoods.price AS ispgPrice,goods.price AS gPrice, goodsimg.image AS giImage,basket.id AS bId,basket.count AS bCount,goods.id AS gId,goods.currency AS gCurrency,goods.name AS gName,goods.priceRoznica AS gPriceRoznica,settings.name AS sName,property.id AS pId,property.name AS pName, goodsDeliverCountry.price AS gdcPrice,goodsDeliverCountry.id_country AS gdcIdCountry FROM basket LEFT JOIN goodsDeliverCountry ON (goodsDeliverCountry.id_goods=basket.id_goods) AND (basket.id_country=goodsDeliverCountry.id_country) LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND goodsimg.is_main='1' LEFT JOIN users ON users.id=goods.id_user LEFT JOIN 
            InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id IN('".$massiveIdBaskets."') AND basket.action='1' ORDER BY bId DESC");

            $resCount=$this->con->query("SELECT basket.id AS bId FROM basket 
            LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND goodsimg.is_main='1' LEFT JOIN users ON users.id=goods.id_user LEFT JOIN 
            InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id_user=(SELECT DISTINCT id FROM users WHERE 
            login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1) AND basket.action='1' GROUP BY bId");
            $row=[];
            $massive=[];
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;

                $profilePhoto=$this->getUsersProfileImg($massive[$i]['uId'],$token);


                $massive[$i]['piPath']=$profilePhoto;
            }

            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            return $massive;
        }
        function getInProcOutOrders($login,$password,$limit,$token)
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
            $massiveIdBaskets[]=null;
            $res=$this->con->query("SELECT id FROM basket WHERE id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1) AND action='2' ORDER BY id DESC LIMIT ".$limit.",".$countRes."");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massiveIdBaskets[$i]=$row['id'];
            } 
            $massiveIdBaskets=join("','",$massiveIdBaskets);


            $res=$this->con->query("SELECT goodsDeliverCountry.id_country AS GDCIdCountry,goodsDeliverCountry.minCount AS GDCMinCount,goodsDeliverCountry.price AS GDCPrice,goodsDeliverCountry.deliveryDays AS gDCDeliveryDays,basket.price AS bPrice,basket.adressBasket AS bAdressBasket, users.id AS uId, users.name AS uName, country_.country_name_en AS cName,InfoSetAndPropertyGoods.price AS ispgPrice,goods.price AS gPrice, goodsimg.image AS giImage,basket.id AS bId,basket.count AS bCount,goods.id AS gId,goods.currency AS gCurrency,c.currency AS cCurrency,goods.name AS gName,settings.name AS sName,property.name AS pName,property.id AS pId FROM basket 
            LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsDeliverCountry ON (goodsDeliverCountry.id_goods=basket.id_goods) AND (basket.id_country=goodsDeliverCountry.id_country) LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND goodsimg.is_main='1' LEFT JOIN 
            InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN users ON users.id=goods.id_user LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id IN('".$massiveIdBaskets."') AND basket.action='2'");
            $resCount=$this->con->query("SELECT basket.id AS bId FROM basket 
            LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND goodsimg.is_main='1' LEFT JOIN 
            InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN users ON users.id=goods.id_user LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id_user=(SELECT DISTINCT id FROM users WHERE 
            login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1) AND basket.action='2' GROUP BY bId");
            $row=[];
            $massive=[];
            for($i=0,$count=0,$curGId=0;$row=$res->fetch_assoc();$i++)
            {
                if($curGId!=$row['bId']&&$i>0)
                {
                    $count++;
                }
                if($curGId!=0&&$curGId!=$row['bId']&&$count>=$countRes)
                {
                    break;
                }
                else
                {
                    $this->con->query("UPDATE basket SET checked_userFrom='1' WHERE basket.id='".$row['bId']."'");
                    $massive[$i]=$row;
                    $curGId=$row['bId'];
            $profilePhoto=$this->getUsersProfileImg($massive[$i]['uId'],$token);
                    $massive[$i]['piPath']=$profilePhoto;
                }
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            return $massive;
        }
        function getInProcOrders($login,$password,$limit,$token)
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
            $massiveIdBaskets[]=null;
            $res=$this->con->query("SELECT id FROM basket WHERE id_goods IN(SELECT id FROM goods WHERE id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1)) AND action='2' ORDER BY id DESC LIMIT ".$limit.",".$countRes."");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massiveIdBaskets[$i]=$row['id'];
            } 
            $massiveIdBaskets=join("','",$massiveIdBaskets);

            $res=$this->con->query("SELECT goodsDeliverCountry.id_country AS GDCIdCountry,goodsDeliverCountry.minCount AS GDCMinCount,goodsDeliverCountry.price AS GDCPrice,goodsDeliverCountry.deliveryDays AS gDCDeliveryDays,basket.price AS bPrice,basket.adressBasket AS bAdressBasket, goods.price AS gPrice,users.id AS uId,users.name AS uName,users.realName AS uRealName,country_.country_name_en AS cName,InfoSetAndPropertyGoods.price AS ispgPrice,goodsimg.image AS giImage,basket.id AS bId,basket.price AS bPrice,basket.count AS bCount,goods.currency AS gCurrency,goods.id AS gId,goods.name AS gName,
            settings.name AS sName,property.name AS pName,property.id AS pId,c.currency AS cCurrency FROM basket LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND 
            goodsimg.is_main='1' LEFT JOIN goodsDeliverCountry ON (goodsDeliverCountry.id_goods=basket.id_goods) AND (basket.id_country=goodsDeliverCountry.id_country) LEFT JOIN users ON users.id=basket.id_user LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id IN('".$massiveIdBaskets."') AND basket.action='2' ORDER BY bId DESC");

            $resCount=$this->con->query("SELECT basket.id AS bId FROM basket LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND 
            goodsimg.is_main='1' LEFT JOIN users ON users.id=goods.id_user LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country WHERE basket.id IN('".$massiveIdBaskets."') AND basket.action='2' GROUP BY bId");
            $row=[];
            $massive=[];
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['uId'],$token);
                $massive[$i]['piPath']=$profilePhoto;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            return $massive;
        }
        function getCanceledOrders($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT users.id AS uId, users.name AS uName,country_.country_name_en AS cName,InfoSetAndPropertyGoods.price AS ispgPrice,goods.price AS gPrice, goodsimg.image AS giImage,basket.id AS bId,basket.count AS bCount,goods.id AS gId,goods.name AS gName,settings.name AS sName,property.name AS pName FROM basket 
            LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND goodsimg.is_main='1' LEFT JOIN 
            InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=Basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=Basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country LEFT JOIN users ON users.id=goods.id_user WHERE basket.id_user=(SELECT DISTINCT id FROM users WHERE 
            login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1) AND basket.action='3'");
            $row[]=null;
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['uId'],$token);
                $massive[$i]['piPath']=$profilePhoto;
            }
            return $massive;
        }
        function deleteGoodsFromBasket($login,$password,$idBasket,$hash,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error: ".$token." ".$_SESSION['token'];
            }
            $rowUser=$this->CheckForHashUser($hash);
            if($rowUser)
            {
                //withoutHash
                /*$res=$this->con->query("DELETE FROM basket WHERE action!='4' AND id='".(int)$idBasket."' AND id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND 
            password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1)");*/
            $res=$this->con->query("DELETE FROM basket WHERE action!='4' AND id='".(int)$idBasket."' AND id_user='".$rowUser['id']."'");
                return $res;
            }
            return false;
        }
        function buyGoods($login,$password,$idBasket,$adressBasket,$token,$count=0,$oplataType=0,$number="",$fioBasket="",$phoneMeType=0,$deliveryType=1)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if($count>0) {
                $this->con->query("UPDATE basket SET count='".(int)$count."' WHERE basket.id_user=(SELECT DISTINCT id FROM users WHERE 
            login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1)");
            $this->con->query("UPDATE basket SET count='".(int)$count."' WHERE basket.id_user=(SELECT DISTINCT id FROM users WHERE login LIKE '' AND password LIKE '' AND hash='".mysqli_real_escape_string($this->con,$_COOKIE['usersHash'])."')");
                //return "Meow";
            }
            $resIdGoods=$this->con->query("SELECT goods.id AS id,goods.id_user AS id_user,goods.action AS action,goods.isset AS gIsset,goods.price AS gPrice,goods.priceRoznica AS gPriceRoznica,goods.currency AS gCurrency,users.baned AS UBaned, basket.count AS bCount,basket.id AS bId, basket.id_infoSetAndPropertyGoods AS bIspgId,gDC.minCount AS GDCMinCount FROM basket LEFT JOIN users ON users.id=id_user LEFT JOIN goods ON goods.action='1' AND goods.is_deleted='0' AND basket.id_goods=goods.id AND goods.isset='1' LEFT JOIN goodsDeliverCountry gDC ON gDC.id_goods=goods.id AND gDC.id_country=basket.id_country WHERE basket.id='".(int)$idBasket."' LIMIT 1");
            $rowIdGoods=$resIdGoods->fetch_assoc();
            if(!$rowIdGoods)
            {
                return "deletedOrNotAcceptedError";
            }
            else if(!$rowIdGoods['gIsset'])
            {
                return "deletedOrNotAcceptedError";
            }
            else if($rowIdGoods['bIspgId']>0)
            {
                $issetGoods=$this->con->query("SELECT DISTINCT isset FROM InfoSetAndPropertyGoods AS ispg WHERE ispg.id='".$rowIdGoods['bIspgId']."' AND ispg.isset='1'")->fetch_assoc();
                if(!$issetGoods)
                {
                    return "deletedOrNotAcceptedError";
                }
            }
            if($rowIdGoods['UBaned'])
            {
                return "user_baned_error";
            }
            if(strlen($adressBasket)<1)
            {
                return "EMPTY_ADRESS";
            }
            if(strlen($number)<5)
            {
                return "EMPTY_NUMBER";
            }
            if(strlen($fioBasket)<4)
            {
                return "EMPTY_FIO";
            }
            $basketPrice=$rowIdGoods['gPrice'];
            if((double)$basketPrice==0.0||$rowIdGoods['bCount']<$rowIdGoods['GDCMinCount'])
            {
                $basketPrice=$rowIdGoods['gPriceRoznica'];
            }
            $basketCurrency=$rowIdGoods['gCurrency'];
            if((double)$basketPrice==0.0) 
            {
                $rowPrice=$this->con->query("SELECT price FROM InfoSetAndPropertyGoods WHERE InfoSetAndPropertyGoods.id='".$rowIdGoods['bIspgId']."' LIMIT 1")->fetch_assoc();
                $basketPrice=$rowPrice['price'];
            } 
            $resUser=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1");
            if($rowUser=$resUser->fetch_assoc())
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $res=$this->con->query("SELECT* FROM goodsDeliverCountry WHERE id_goods='".$rowIdGoods['id']."'");
                if($rowDelivery=$res->fetch_assoc())
                {
                    if($rowDelivery['minCount']>$rowIdGoods['bCount']&&$rowIdGoods['gPriceRoznica']==0.0)
                    {
                        return "basket_count_error_".$rowDelivery['minCount'];
                    }
                    else if($rowIdGoods['bCount']<1)
                    {
                        //$rowIdGoods['gPriceRoznica']
                        //return "basket_count_error_".$rowDelivery['minCount'];
                        return "basket_count_error_1";
                    }
                }
                $resIdGoods=$this->con->query("SELECT id_user,action FROM goods WHERE id=(SELECT DISTINCT id_goods FROM basket WHERE id='".(int)$idBasket."' LIMIT 1)");
                $rowIdGoods=$resIdGoods->fetch_assoc();
                $firstUId=$rowIdGoods['id_user'];
                if($this->isInBlackList($rowUser['id'],$firstUId,$token)||$this->isInBlackList($firstUId,$rowUser['id'],$token))
                {
                    return "blackList_error";
                }
                if($rowIdGoods['action']=='0')
                {
                    return "admin_error";
                }
                $res=$this->con->query("UPDATE basket SET action='1',adressBasket='".mysqli_real_escape_string($this->con,$adressBasket)."',price='".(double)$basketPrice."',currency='".(int)$basketCurrency."',checked_userTo='0',checked_userFrom='1',oplataType='".(int)$oplataType."',phoneMeType='".(int)$phoneMeType."',deliveryType='".(int)$deliveryType."' WHERE action='0' AND id='".(int)$idBasket."' AND id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1)");
                $this->con->query("UPDATE basket SET action='1',adressBasket='".mysqli_real_escape_string($this->con,$adressBasket)."',price='".(double)$basketPrice."',currency='".(int)$basketCurrency."',checked_userTo='0',checked_userFrom='1',oplataType='".(int)$oplataType."',phoneMeType='".(int)$phoneMeType."',deliveryType='".(int)$deliveryType."' WHERE action='0' AND id='".(int)$idBasket."' AND id_user=(SELECT DISTINCT id FROM users WHERE login LIKE '' AND password LIKE '' AND hash='".mysqli_real_escape_string($this->con,$_COOKIE['usersHash'])."')");
                $this->con->query("UPDATE users SET realName='".mysqli_real_escape_string($this->con,$fioBasket)."',number='".mysqli_real_escape_string($this->con,$number)."' WHERE login LIKE '' AND password LIKE '' AND hash='".mysqli_real_escape_string($this->con,$_COOKIE['usersHash'])."'");
                return true;
            }
            
        }
        function getBasket($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT pc.visibleForBasket AS pcVisibleForBasket,pc.hidden AS pcHidden,goodsDeliverCountry.id_country AS GDCIdCountry,goodsDeliverCountry.minCount AS GDCMinCount,goodsDeliverCountry.deliveryDays AS gDCDeliveryDays,goodsDeliverCountry.price gdcPrice,users.name AS uName,users.id AS uId,country_.country_name_en AS cName,country_.id AS cId,InfoSetAndPropertyGoods.price AS ispgPrice,goods.price AS gPrice, goodsimg.image AS giImage,basket.id AS bId,basket.count AS bCount,goods.currency AS gCurrency,goods.id AS gId,goods.priceRoznica AS gPriceRoznica,goods.name AS gName,settings.id AS sId,settings.name AS sName,c.currency AS cCurrency,property.id AS pId,property.name AS pName FROM basket 
            LEFT JOIN goods ON goods.id=basket.id_goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id AND goodsimg.is_main='1' LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN 
            InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=basket.id_infoSetAndPropertyGoods LEFT JOIN InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=basket.id_infoSetAndPropertyGoods 
            LEFT JOIN property ON property.id=InfoSetAndProperty.id_property LEFT JOIN settings ON settings.id=InfoSetAndProperty.id_settings LEFT JOIN country_ ON country_.id=basket.id_country LEFT JOIN users ON users.id=goods.id_user LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN propertycategories pc ON pc.id_subsubcategories=goods.id_subsubcategory WHERE basket.id_user=(SELECT DISTINCT id FROM users WHERE 
            hash='".mysqli_real_escape_string($this->con, $_COOKIE['usersHash'])."' LIMIT 1) AND basket.action='0' GROUP BY bId ORDER BY bId, pcVisibleForBasket DESC");// LIMIT 100
            //Убрать GROUP BY bId
            //Убрать ORDER BY pcVisibleForBasket
            $row[]=null;
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
                $profilePhoto=$this->getUsersProfileImg($massive[$i]['uId'],$token);
                $massive[$i]['piPath']=$profilePhoto;
            }
            return $massive;
        }
        function getAllCountBasket($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT id FROM basket WHERE action='0' AND id_user=(SELECT DISTINCT id FROM users WHERE 
            login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1)");
            $count=$res->num_rows;
            if($count>=10)
            {
                return false;
            }
            return true;
        }
        function CheckForHashUser($hash)
        {
            $res=$this->con->query("SELECT* FROM users WHERE hash='".mysqli_real_escape_string($this->con,$hash)."'");
            if($row=$res->fetch_assoc())
            {
                return $row;
            }
            return false;
        }
        function updateBasketHash($idBasket,$count,$deliveryCountry,$hash,$token)
        {
            $rowUser=$this->CheckForHashUser($hash);
            if($rowUser)
            {
                $res=$this->con->query("UPDATE basket SET count='".(int)$count."' WHERE id_user='".$rowUser['id']."' AND id='".(int)$idBasket."' AND action='0' AND id_country='".(int)$deliveryCountry."'");
                return "UPDATED";
                //return $res;
            }
            return "user_error";
        }
        function AddToBasketHash($idGoods,$count,$hash,$deliveryCountry,$propertiesOfGoods,$token)
        {
            $res=$this->con->query("SELECT id FROM goods WHERE id='".(int)$idGoods."'");  
            if((int)$idGoods<=0||(int)$count<=0||(int)$count>1000||!($res->fetch_assoc()))
            {
                return "false";       
            }
            $resIdGoods=$this->con->query("SELECT goods.id AS id,goods.id_user AS id_user,goods.action AS action,users.baned AS UBaned FROM goods LEFT JOIN users ON users.id=goods.id_user WHERE goods.id = '".$idGoods."' AND goods.action='1' AND goods.is_deleted='0' AND goods.isset='1' LIMIT 1");
            $rowIdGoods=$resIdGoods->fetch_assoc();
            if(!$rowIdGoods)
            {//или нет в наличии
                return "deletedOrNotAcceptedError";
            }
            else if($rowIdGoods['UBaned'])
            {
                return "user_baned_error";
            }
            $propertiesOfGoods=explode(",",$propertiesOfGoods);
            $countPropertiesOfGoods=count($propertiesOfGoods)-1;
            $res=$this->con->query("SELECT id,price FROM InfoSetAndPropertyGoods WHERE id_goods='".(int)$idGoods."'");
            $curInfoSet=0;
            $flagIssetInfoSetAndPropertyGoods=false;
            $z=0;
            while($row=$res->fetch_assoc())
            {
                        $z++;
                $flagIssetInfoSetAndPropertyGoods=true;
                $resString="";
                $resInfoSetAndProperty=$this->con->query("SELECT isp.id,isp.id_property, isp.id_settings FROM InfoSetAndProperty AS isp LEFT JOIN InfoSetAndPropertyGoods AS ispg ON ispg.id=isp.id_infoSetAndPropertyGoods WHERE isp.id_infoSetAndPropertyGoods='".$row['id']."' AND ispg.isset='1' ORDER BY isp.id_property");
                $CurCountInfoSetAndProperty=0;
                    $curzzz="";
                while($rowInfoSetAndProperty=$resInfoSetAndProperty->fetch_assoc())
                {
                    $flag=false;$CurCountInfoSetAndProperty++;
                    for($i=0;$i<$countPropertiesOfGoods;$i++)
                    {
                        $curProperty=(int)$propertiesOfGoods[$i];
                        $curSet=(int)explode("_",$propertiesOfGoods[$i])[1];
                        
                        $curzzz.=$curProperty." ".$curSet.":".$rowInfoSetAndProperty['id_property']." ".$rowInfoSetAndProperty['id_settings'].";";
                        if($curProperty==$rowInfoSetAndProperty['id_property']&&$curSet==$rowInfoSetAndProperty['id_settings'])
                        {
                            $resString.=$curProperty."_".$curSet.",";
                            $flag=true;
                            $curzzz.="true";
                            break;
                        }
                        $curzzz.="(      )";
                    }
                    if(!$flag)
                    {
                        break;
                    }
                }
                //if($z==1)
                //{
               //return $curzzz;
                //}
                if($flag&&$CurCountInfoSetAndProperty==$countPropertiesOfGoods)
                {
                    $curInfoSet=$row['id']; 
                    break;
                }
            }
            if($flagIssetInfoSetAndPropertyGoods&&$curInfoSet<=0)
            {
                //return $z;
                return "deletedOrNotAcceptedError1";
            }
            $res=$this->con->query("SELECT* FROM goodsDeliverCountryNot WHERE id_country='".(int)$deliveryCountry."' AND id_goods='".(int)$idGoods."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                return "NotDeliver";
            }
            $DeliveryminCount=0;
            $DeliveryPrice=0;
            $DeliveryDays=0;
            $res=$this->con->query("SELECT* FROM goodsDeliverCountry WHERE id_country='".(int)$deliveryCountry."' AND id_goods='".(int)$idGoods."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                $DeliveryminCount=$row['minCount'];
                $DeliveryPrice=$row['price'];
                $DeliveryDays=$row['DeliveryDays'];
            }
            else
            {
                $res=$this->con->query("SELECT* FROM goodsDeliverCountry WHERE id_country='0' AND id_goods='".(int)$idGoods."' LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    $DeliveryminCount=$row['minCount'];
                    $DeliveryPrice=$row['price'];
                    $DeliveryDays=$row['DeliveryDays'];
                }
                else
                {
                    return "";
                }
            }


            $rowUser=$this->CheckForHashUser($hash);
            if($rowUser)
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                else if($rowUser['id']==$rowIdGoods['id_user'])
                {
                    return "same_user_error";
                }
                if($this->isInBlackList($rowUser['id'],$rowIdGoods['id_user'],$token)||$this->isInBlackList($rowIdGoods['id_user'],$rowUser['id'],$token))
                {
                    return "blackList_error";
                }
                $res=$this->con->query("SELECT * FROM basket WHERE id_user = '".$rowUser['id']."' AND id_goods = '".$idGoods."' AND id_infoSetAndPropertyGoods='".$curInfoSet."' AND action='0' AND id_country='".(int)$deliveryCountry."' LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    $res=$this->con->query("UPDATE basket SET count='".(int)$count."' WHERE id_user='".$rowUser['id']."' AND id_goods='".(int)$idGoods."' AND id_infoSetAndPropertyGoods='".$curInfoSet."' AND action='0' AND id_country='".(int)$deliveryCountry."'");
                    return "UPDATED";
                }
                else
                {
                        $res=$this->con->query("SELECT id FROM goods WHERE id_user = '".$rowUser['id']."' AND id = '".$idGoods."' LIMIT 1");
                        if(!$row=$res->fetch_assoc())
                        {
                            
                            if(!$this->getAllCountBasket($login,$password,$token))
                            {
                                return "basketCount_error";
                            }
                            if(!$flagIssetInfoSetAndPropertyGoods)
                            {
                                $res=$this->con->query("INSERT INTO basket(id_goods,id_user,count,id_country,action) VALUES('".(int)$idGoods."','".$rowUser['id']."','".(int)$count."','".(int)$deliveryCountry."','0')");
                            }
                            else
                            {
                                $res=$this->con->query("INSERT INTO basket(id_goods,id_user,count,id_infoSetAndPropertyGoods,id_country,action) VALUES('".(int)$idGoods."','".$rowUser['id']."','".(int)$count."','".(int)$curInfoSet."','".(int)$deliveryCountry."','0')");
                            }
                            return "INSERTED";
                        }
                }
                return "true";
            }
            return "false";
        }
        function addToBasket($login,$password,$idGoods,$count,$deliveryCountry,$propertiesOfGoods,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
                //return $token." ".$_SESSION['token'];
            }
            if(!trim($login)&&!trim($password))
            {
                return "unregistred_user";
            }
            $resIdGoods=$this->con->query("SELECT goods.id AS id,goods.id_user AS id_user,goods.action AS action,users.baned AS UBaned FROM goods LEFT JOIN users ON users.id=id_user WHERE goods.id = '".$idGoods."' AND goods.action='1' AND goods.is_deleted='0' AND goods.isset='1' LIMIT 1");
            $rowIdGoods=$resIdGoods->fetch_assoc();
            if(!$rowIdGoods)
            {//или нет в наличии
                return "deletedOrNotAcceptedError";
            }
            else if($rowIdGoods['UBaned'])
            {
                return "user_baned_error";
            }
            $propertiesOfGoods=explode(",",$propertiesOfGoods);
            $countPropertiesOfGoods=count($propertiesOfGoods)-1;
            $res=$this->con->query("SELECT id,price FROM InfoSetAndPropertyGoods WHERE id_goods='".(int)$idGoods."'");
            $curInfoSet=0;
            $flagIssetInfoSetAndPropertyGoods=false;
            while($row=$res->fetch_assoc())
            {
                $flagIssetInfoSetAndPropertyGoods=true;
                $resString="";
                $resInfoSetAndProperty=$this->con->query("SELECT isp.id,isp.id_property, isp.id_settings FROM InfoSetAndProperty AS isp LEFT JOIN InfoSetAndPropertyGoods AS ispg ON ispg.id=isp.id_infoSetAndPropertyGoods WHERE isp.id_infoSetAndPropertyGoods='".$row['id']."' AND ispg.isset='1'");
                $CurCountInfoSetAndProperty=0;
                while($rowInfoSetAndProperty=$resInfoSetAndProperty->fetch_assoc())
                {
                    $flag=false;$CurCountInfoSetAndProperty++;
                    for($i=0;$i<$countPropertiesOfGoods;$i++)
                    {
                        $curProperty=(int)$propertiesOfGoods[$i];
                        $curSet=(int)explode("_",$propertiesOfGoods[$i])[1];
                        if($curProperty==$rowInfoSetAndProperty['id_property']&&$curSet==$rowInfoSetAndProperty['id_settings'])
                        {
                            $resString.=$curProperty."_".$curSet.",";
                            $flag=true;
                            break;
                        }
                    }
                    if(!$flag)
                    {
                        break;
                    }
                }
                if($flag&&$CurCountInfoSetAndProperty==$countPropertiesOfGoods)
                {
                    $curInfoSet=$row['id']; 
                }
            }
            if($flagIssetInfoSetAndPropertyGoods&&$curInfoSet<=0)
            {
                return "deletedOrNotAcceptedError";
            }
            $res=$this->con->query("SELECT* FROM goodsDeliverCountryNot WHERE id_country='".(int)$deliveryCountry."' AND id_goods='".(int)$idGoods."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                return "NotDeliver";
            }
            $DeliveryminCount=0;
            $DeliveryPrice=0;
            $DeliveryDays=0;
            $res=$this->con->query("SELECT* FROM goodsDeliverCountry WHERE id_country='".(int)$deliveryCountry."' AND id_goods='".(int)$idGoods."' LIMIT 1");
            if($row=$res->fetch_assoc())
            {
                $DeliveryminCount=$row['minCount'];
                $DeliveryPrice=$row['price'];
                $DeliveryDays=$row['DeliveryDays'];
            }
            else
            {
                $res=$this->con->query("SELECT* FROM goodsDeliverCountry WHERE id_country='0' AND id_goods='".(int)$idGoods."' LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    $DeliveryminCount=$row['minCount'];
                    $DeliveryPrice=$row['price'];
                    $DeliveryDays=$row['DeliveryDays'];
                }
                else
                {
                    return "";
                }
            }
            //$res=$this->con->query("SELECT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' AND verify='1' LIMIT 1");
            $res=$this->con->query("SELECT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."' LIMIT 1");
            if($rowUser=$res->fetch_assoc())
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                else if($rowUser['id']==$rowIdGoods['id_user'])
                {
                    return "same_user_error";
                }
                if($this->isInBlackList($rowUser['id'],$rowIdGoods['id_user'],$token)||$this->isInBlackList($rowIdGoods['id_user'],$rowUser['id'],$token))
                {
                    return "blackList_error";
                }
                $res=$this->con->query("SELECT * FROM basket WHERE id_user = '".$rowUser['id']."' AND id_goods = '".$idGoods."' AND id_infoSetAndPropertyGoods='".$curInfoSet."' AND action='0' AND id_country='".(int)$deliveryCountry."' LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    $res=$this->con->query("UPDATE basket SET count='".(int)$count."' WHERE id_user='".$rowUser['id']."' AND id_goods='".(int)$idGoods."' AND id_infoSetAndPropertyGoods='".$curInfoSet."' AND action='0' AND id_country='".(int)$deliveryCountry."'");
                    return "UPDATED";
                }
                else
                {
                        $res=$this->con->query("SELECT id FROM goods WHERE id_user = '".$rowUser['id']."' AND id = '".$idGoods."' LIMIT 1");
                        if(!$row=$res->fetch_assoc())
                        {
                            
                            if(!$this->getAllCountBasket($login,$password,$token))
                            {
                                return "basketCount_error";
                            }
                            if(!$flagIssetInfoSetAndPropertyGoods)
                            {
                                $res=$this->con->query("INSERT INTO basket(id_goods,id_user,count,id_country,action) VALUES('".(int)$idGoods."','".$rowUser['id']."','".(int)$count."','".(int)$deliveryCountry."','0')");
                            }
                            else
                            {
                                $res=$this->con->query("INSERT INTO basket(id_goods,id_user,count,id_infoSetAndPropertyGoods,id_country,action) VALUES('".(int)$idGoods."','".$rowUser['id']."','".(int)$count."','".(int)$curInfoSet."','".(int)$deliveryCountry."','0')");
                            }
                            return "INSERTED";
                        }
                }
            }
            return "FailedUser";
        }
    }
?>