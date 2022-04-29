<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */

?>
<?php
    include_once("classes/CString.php");
    require_once("classes/CDatabase.php");
    class Goods{
        use MyString;
        private $con;
        private $Database;
        private $countRes;
        function __construct($con) {
            $this->con=$con;
            $this->Database=new Database($this->con);
            //$this->con->query("SET SQL_BIG_SELECTS=1");
            $this->countRes=24;
        }
        function getGoodsReviews($idGoods,$limit)
        {
            $countReviews=3;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countReviews);
            }
            if($idGoods>0)
            {
                $res=$this->con->query("SELECT name,id_goods,text,rating,created_at FROM reviews WHERE isVisible=1 AND id_goods='".(int)$idGoods."' ORDER BY id DESC LIMIT ".$limit.",".$countReviews."");
                $resRating=$this->con->query("SELECT CEIL(SUM(rating)/COUNT(id)) AS grRes FROM reviews WHERE isVisible=1 AND id_goods='".(int)$idGoods."' GROUP BY id_goods ORDER BY id DESC LIMIT ".$limit.",".$countReviews."")->fetch_assoc()['grRes'];
            
                $resCount=$this->con->query("SELECT id FROM reviews WHERE isVisible=1 AND id_goods='".(int)$idGoods."'");
            }
            else
            {
                $res=$this->con->query("SELECT name,id_goods,text,rating,created_at FROM reviews WHERE isVisible=1 ORDER BY id DESC LIMIT ".$limit.",".$countReviews."");
                $resRating=$this->con->query("SELECT CEIL(SUM(rating)/COUNT(id)) AS grRes FROM reviews WHERE isVisible=1 GROUP BY id_goods ORDER BY id DESC LIMIT ".$limit.",".$countReviews."")->fetch_assoc()['grRes'];
            
                $resCount=$this->con->query("SELECT id FROM reviews WHERE isVisible=1");
            }
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            $massive['grRes']=$resRating;
            return $massive;
        }
        function getGoodsProperties($idGood)
        {
            $curString="";
            $curSetAndPropGoodsRow=$this->con->query("SELECT isp.id_infoSetAndPropertyGoods AS ispInfoSetAndPropertyGoods, ispg.isset AS ispgIsset,ispg.price AS ispgPrice,isp.id_property AS 
                        ispId_Property,isp.id_settings AS ispId_Settings FROM InfoSetAndPropertyGoods ispg,InfoSetAndProperty isp WHERE ispg.id=isp.id_infoSetAndPropertyGoods AND 
                        ispg.id_goods='".$idGood."' ORDER BY isp.id_infoSetAndPropertyGoods");
                        $rowIspId=0;
                        for($j=0;$rowIsp=$curSetAndPropGoodsRow->fetch_assoc();)
                        {
                            if($rowIspId!=$rowIsp['ispInfoSetAndPropertyGoods'])
                            {
                                $rowIspId=$rowIsp['ispInfoSetAndPropertyGoods'];
                                if($j==0)
                                {
                                    $j++;
                                }
                                else
                                {
                                    $curString.="}";
                                }
                                $curString.="price:".$rowIsp['ispgPrice']."_";
                            }
                                $curString.="property:".$rowIsp['ispId_Property'].";setting:".$rowIsp['ispId_Settings'].";isset:".$rowIsp['ispgIsset']."]";
                        }
                        $curString.="}";
                        return $curString;
        }
        function getGoodsCountByIdUser($idUser)
        {
            $userQuery=$this->con->query("SELECT id FROM goods WHERE id_user='".(int)$idUser."' AND is_deleted!=1");
            return $userQuery->num_rows;
        }
        function changeIssetGoods($login,$password,$idGoods,$isset,$properties,$token)
        {
            $isset=$isset>=1?1:0;
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            else if($isset>1||$isset<0)
            {
                return "isset_error";
            }
            $userQuery=$this->con->query("SELECT* FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND  password='".mysqli_real_escape_string($this->con, $password)."'");
            if($userRow=$userQuery->fetch_assoc())
            {
                $propertiesArray=explode(";",$properties);
                $stringPropertyQuery=" AND (";
                if(count($propertiesArray)>0)
                {
                for($i=0;$i<count($propertiesArray);$i++)
                {
                    if((int)$propertiesArray<1)
                    {
                        return;
                    }
                    $stringPropertyQuery.=$i==0?"":" OR ";
                    $stringPropertyQuery.="isp.id_settings='".(int)$propertiesArray[$i]."'";
                }
                }
                else
                {
                    $properties=(int)$properties;
                    $stringPropertyQuery.="isp.id_settings='".(int)$properties."'";
                }
                $stringPropertyQuery.=")";
                if($properties<=0)
                {
                
                    $this->con->query("UPDATE goods SET isset='".(int)$isset."' WHERE id='".(int)$idGoods."'");
                }
                else
                {
                    $rowIssetQuery=$this->con->query("SELECT DISTINCT isp.id_infoSetAndPropertyGoods AS ispgId,isp.id_settings AS ispIdSettings FROM InfoSetAndProperty AS isp WHERE isp.id_infoSetAndPropertyGoods IN(SELECT id FROM InfoSetAndPropertyGoods AS ispg WHERE ispg.id_goods=(SELECT DISTINCT id FROM goods WHERE id_user='".$userRow['id']."' AND id='".(int)$idGoods."' LIMIT 1))".$stringPropertyQuery." ORDER BY isp.id_infoSetAndPropertyGoods");
                    $propertiesDBArray=array();
                    $curI=-1;
                    $curIspgId=0;
                    while($rowIsset=$rowIssetQuery->fetch_assoc())
                    {
                        if(($rowIsset['ispgId']!=$curIspgId))
                        {
                            $curIspgId=$rowIsset['ispgId'];
                            $curI++;
                        }
                        $propertiesDBArray[$curI][]=$rowIsset;
                    }
                    sort($propertiesArray);
                    $ispgId=0;
                    for($i=0;$i<count($propertiesDBArray);$i++)
                    {
                        $flag=true;
                        if(count($propertiesDBArray[$i])!=count($propertiesArray))
                        {
                            continue;
                        }
                        for($j=0;$j<count($propertiesDBArray[$i]);$j++)
                        {//if not working try to del ['ispIdSettings']
                            if($propertiesDBArray[$i][$j]['ispIdSettings']!=$propertiesArray[$j])
                            {
                                $flag=false;break;
                            }
                        }
                        if($flag==true)
                        {
                            $ispgId=$propertiesDBArray[$i][0]['ispgId'];
                            break;
                        }
                    }
                    //$stringPropertyQuery
                    return $this->con->query("UPDATE InfoSetAndPropertyGoods SET isset='".(int)$isset."' WHERE id='".$ispgId."'");
                }
            }
        }
        function deleteGood($idGoods,$loginUser,$passwordUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                //return "csrf_error";
                return $token;
            }
            $res=$this->con->query("UPDATE goods SET is_deleted='1' WHERE id='".(int)$idGoods."' AND id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con,$loginUser)."' AND password='".mysqli_real_escape_string($this->con,$passwordUser)."' LIMIT 1)");
            return $res;
        }
        function getGoodsByMinMaxPriceCategory($nameGoods,$minPrice,$maxPrice,$idCategory,$limit,$sortBy,$country,$idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
                } else {
                     $language="_".$language;
                }
            $stringUser="";//товары пользователя
            $idUser=(int)$idUser;
            $stringGoodsAction=" goods.action='1' AND";
            if($idUser>0) {
                $stringUser=" goods.id_user='".(int)$idUser."' AND";
                $stringGoodsAction="";
            }
            $massive[]=null;
            $countRes=$this->countRes;
            $resCount="";
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $idCategory=(int)$idCategory;
            if((int)$idCategory==0)
            {
                if((float)$maxPrice==0)
                {
                    if($sortBy==2)
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                        // AGAINST ('женский тестер') GROUP BY goods.id ORDER BY IF(gPrice='0', MAX(InfoSetAndPropertyGoods.price),gPrice) DESC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,goods.isset AS gIsset,
                        MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    }
                    else if($sortBy==3)
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                        MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    }
                    else
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND 
                        goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                        (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                        GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                        MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    }
                }
                else
                {
                    if($sortBy==2)
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                        IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                        goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,
                        IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,
                        MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM 
                        goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON 
                        goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON 
                        goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                        (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND 
                        goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                        (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                        GROUP BY goods.id");
                    }
                    else if($sortBy==3)
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                        IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                        goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', 
                        InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                        ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                        goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                        goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." 
                        ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                        (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND 
                        goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                        (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                        GROUP BY goods.id");
                    }
                    else
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ".$stringUser."((goods.price>='".(float)$minPrice."' AND 
                        goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                        ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                        goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT 
                        JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." 
                        ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                        (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    }
                }
            }
            else 
            {
                if((float)$maxPrice==0)
                {
                    if($sortBy==2)
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                        MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.is_deleted='0' 
                        AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                        (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                        GROUP BY goods.id");
                    }
                    else if($sortBy==3)
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                        MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    }
                    else
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=g".$language."oods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.is_deleted='0' AND 
                        goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                        (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                        GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                        
                        /*$res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT 
                    JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE goods.id_subcategory IN(SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.action='1' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY grRes DESC LIMIT ".$limit.",".$countRes."");*/
                        
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                        MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND".$stringGoodsAction." goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    }
                }
                else
                {
                    if($sortBy==2)
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                        IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                        goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,
                        IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,
                        MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM 
                        goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON 
                        goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON 
                        goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                        (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND 
                        goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                        (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                        GROUP BY goods.id");
                    }
                    else if($sortBy==3)
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                        IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                        goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', 
                        InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                        ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                        goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                        goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." 
                        ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                        (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND 
                        goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                        (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                        GROUP BY goods.id");
                    }
                    else
                    {
                        $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                        $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                        MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,goods.currency AS gCurrency, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." ((goods.price>='".(float)$minPrice."' AND 
                        goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                        InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory IN (SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY grRes DESC");
                    }
                }
            }
            $i=0;
            for(;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            if($i==0)return null;
            return $massive;
        }
        
        
        function getGoodsByMinMaxPrice($nameGoods,$minPrice,$maxPrice,$idCategory,$limit,$sortBy,$country)
        {
            $massive[]=null;
            $countRes=$this->countRes;
            $resCount="";
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            if((float)$maxPrice==0)
            {
                if($sortBy==2)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage,goods.currency AS gCurrency,goods.isset AS gIsset, FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,goods.isset AS gIsset,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                }
                else if($sortBy==3)
                {
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND goods.action='1' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                }
                else
                {
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND goods.is_deleted='0' AND 
                    goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.action='1' AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                }
            }
            else
            {
                if($sortBy==2)
                {
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,
                    IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,
                    MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM 
                    goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON 
                    goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON 
                    goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
                else if($sortBy==3)
                {
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, goods.currency AS gCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%')))  AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC, IF(gPrice='0', 
                    InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE 
                    ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
                else
                {
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT 
                    JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE 
                    ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND (goods.id_subcategory IN (SELECT id FROM subcategories WHERE subcategory LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' OR  id_category IN (SELECT id FROM categories WHERE category LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%'))) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.action='1' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                }
            }
            $i=0;
            for(;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            if($i==0)return null;
            return $massive;
        }
        
        
        
        function getGoodsByCategory($nameGoods,$idCategory,$limit,$sortBy,$country,$idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
                } else {
                     $language="_".$language;
                }
            $stringUser="";//товары пользователя
            $idUser=(int)$idUser;
            $stringGoodsAction=" goods.action='1' AND";
            if($idUser>0) {
                $stringUser=" goods.id_user='".(int)$idUser."' AND";
                $stringGoodsAction="";
            }
            
            $countRes=$this->countRes;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $massive[]=null;
            if((int)$idCategory>0) {
                if($sortBy==2)
                {
                    $resCount=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." 
                    goods.id_subcategory IN(SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, 
                    goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.name 
                    LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,goods.currency AS gCurrency,c.currency AS cCurrency, goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subcategory IN(SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                }
                else if($sortBy==3)
                {
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subcategory IN(SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,c.currency AS cCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subcategory IN(SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                }
                else
                {
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT 
                    JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subcategory IN(SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,c.currency AS cCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT JOIN country_ c ON c.id=goods.currency LEFT 
                    JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subcategory IN(SELECT id FROM subcategories WHERE id_category='".(int)$idCategory."') AND".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC, grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                }
            }
            else
            {
                if($sortBy==2)
                {
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser."".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, 
                    goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.name 
                    LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,goods.currency AS gCurrency,c.currency AS cCurrency, goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser."".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                }
                else if($sortBy==3)
                {
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser."".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser,c.currency AS cCurrency FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id LEFT JOIN country_ c ON c.id=goods.currency WHERE".$stringUser."".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC, IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                }
                else
                {
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT 
                    JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser."".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                    $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser,c.currency AS cCurrency FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT 
                    JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id LEFT JOIN country_ c ON c.id=goods.currency WHERE".$stringUser."".$stringGoodsAction." goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                }
            }
            $i=0;
            for(;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            if($i==0)return null;
            return $massive;  
        }
        function getGoodsByMinMaxPriceAndProperties($nameGoods,$minPrice,$maxPrice,$properties,$idSubSubCategory,$limit,$sortBy,$country,$idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
                } else {
                     $language="_".$language;
                }
            //return;
            $properties=explode("}",$properties);
            $newSettingsProperties="";
            $countRes=$this->countRes;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            
            $stringUser="";//товары пользователя
            $idUser=(int)$idUser;
            $stringGoodsAction=" goods.action='1' AND";
            if($idUser>0) {
                $stringUser=" goods.id_user='".(int)$idUser."' AND";
                $stringGoodsAction="";
            }
            
            for($i=0;$i<count($properties)-1;$i++)
            {
                $curSet=explode(";",explode(":",$properties[$i])[1]);
                $curProp=explode(";",explode(":",$properties[$i])[0]);
                for($j=0;$j<count($curSet)-1;$j++)
                {
                    if($j==count($curSet)-2&&$i==count($properties)-2)
                    {
                        $newSettingsProperties.=$curSet[$j];
                    }
                    else
                    {
                        $newSettingsProperties.=$curSet[$j].",";
                    }
                }
            }
            $massive[]=null;
            if((float)$maxPrice==0.0)
            {
                if($sortBy==2)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN InfoSetAndProperty ON 
                    InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") 
                    AND goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT SUM(goodsraiting.raiting) AS grSum, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") AND 
                    goods.id_subsubcategory='".(int)$idSubSubCategory."' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') GROUP BY goods.id");
                }
                else if($sortBy==3)
                { 
                    $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,goods.currency AS gCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN InfoSetAndProperty ON 
                    InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot 
                    ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") AND 
                    goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT SUM(goodsraiting.raiting) AS grSum, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") AND 
                    goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
                else
                {
                    $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.currency AS gCurrency,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods  LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN InfoSetAndProperty ON 
                    InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot 
                    ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") AND 
                    goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT SUM(goodsraiting.raiting) AS grSum, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.is_deleted='0' AND goodsimg.is_main='1' AND InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") AND 
                    goods.id_subsubcategory='".(int)$idSubSubCategory."' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') GROUP BY goods.id");
                }
            }
            else
            {
                if($sortBy==2)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN InfoSetAndProperty ON 
                    InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot 
                    ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."'))  AND InfoSetAndProperty.id_settings 
                    IN(".$newSettingsProperties.") AND goods.id_subsubcategory='".(int)$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,
                    IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT SUM(goodsraiting.raiting) AS grSum, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) 
                    AND InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") AND goods.id_subsubcategory='".(int)$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' 
                    AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                }
                else if($sortBy==3)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN InfoSetAndProperty ON 
                    InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot 
                    ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."'))  AND InfoSetAndProperty.id_settings 
                    IN(".$newSettingsProperties.") AND goods.id_subsubcategory='".(int)$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,
                    IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT SUM(goodsraiting.raiting) AS grSum, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) 
                    AND InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") AND goods.id_subsubcategory='".(int)$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' 
                    AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                }
                else
                {
                    $res=$this->con->query("SELECT goods.action AS gAction, CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN InfoSetAndProperty ON 
                    InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND 
                    InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") AND goods.id_subsubcategory='".(int)$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') GROUP BY goods.id ORDER BY grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT SUM(goodsraiting.raiting) AS grSum, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    InfoSetAndProperty ON InfoSetAndProperty.id_infoSetAndPropertyGoods=InfoSetAndPropertyGoods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT 
                    JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND 
                    InfoSetAndProperty.id_settings IN(".$newSettingsProperties.") AND goods.id_subsubcategory='".(int)$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') GROUP BY goods.id");
                }
            }
            $i=0;
            for(;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            if($i==0)return null;
            return $massive;
        }
        function getGoodsByMinMaxPriceSubCategory($nameGoods,$minPrice,$maxPrice,$idSubCategory,$limit,$sortBy,$country,$idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
                } else {
                     $language="_".$language;
                }
            $stringUser="";//товары пользователя
            $idUser=(int)$idUser;
            $stringGoodsAction=" goods.action='1' AND";
            if($idUser>0) {
                $stringUser=" goods.id_user='".(int)$idUser."' AND";
                $stringGoodsAction="";
            }
            $massive[]=null;
            $countRes=$this->countRes;
            $resCount="";
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            if((float)$maxPrice==0)
            {
                if($sortBy==2)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id = goods.currency  LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND goods.is_deleted='0' AND 
                    goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND goods.is_deleted='0' 
                    AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
                else if($sortBy==3)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id = goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND goods.is_deleted='0' 
                    AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND goods.is_deleted='0' 
                    AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
                else
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id = goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND goods.is_deleted='0' AND 
                    goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id ORDER BY grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND goods.is_deleted='0' AND 
                    goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
            }
            else
            {
                if($sortBy==2)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency, goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id = goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,
                    IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice,
                    MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM 
                    goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON 
                    goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON 
                    goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
                else if($sortBy==3)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.currency AS gCurrency, goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id = goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE ".$stringUser.$stringGoodsAction."((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', 
                    InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                    goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." 
                    ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
                else
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id = goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY grRes DESC,gId DESC 
                    LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                    ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                    goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT 
                    JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." 
                    ((goods.price>='".(float)$minPrice."' AND goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR 
                    (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subcategory='".$idSubCategory."' AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
            }
            $i=0;
            for(;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            if($i==0)return null;
            return $massive;
        }
        function getGoodsByMinMaxPriceSubSubCategory($nameGoods,$minPrice,$maxPrice,$idSubSubCategory,$limit,$sortBy,$country,$idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
                } else {
                     $language="_".$language;
                }
            $massive[]=null;
            $stringUser="";//товары пользователя
            $idUser=(int)$idUser;
            $stringGoodsAction=" goods.action='1' AND";
            if($idUser>0) {
                $stringUser=" goods.id_user='".(int)$idUser."' AND";
                $stringGoodsAction="";
            }
            $countRes=$this->countRes;
            $resCount="";
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            if((float)$maxPrice==0)
            {
                if($sortBy==2)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP 
                    BY goods.id ORDER BY gIsset DESC,IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
                else if($sortBy==3)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.currency AS gCurrency,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
                else
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND 
                    goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                    (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                    GROUP BY goods.id");
                }
            }
            else
            {
                if($sortBy==2)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,
                    IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) DESC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                }
                else if($sortBy==3)
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY gIsset DESC,
                    IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND goods.name 
                    LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                }
                else
                {
                    $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage,c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id ORDER BY grRes DESC,gIsset DESC,gId DESC 
                    LIMIT ".$limit.",".$countRes."");
                    $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                    MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                    LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                    goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." ((goods.price>='".(float)$minPrice."' AND 
                    goods.price<='".(float)$maxPrice."' AND InfoSetAndPropertyGoods.price IS NULL) OR (InfoSetAndPropertyGoods.price>='".(float)$minPrice."' AND 
                    InfoSetAndPropertyGoods.price<='".(float)$maxPrice."')) AND goods.id_subsubcategory='".$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND 
                    IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR 
                    goodsDeliverCountry.id_country='0') AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                }
            }
            $i=0;
            for(;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            if($i==0)return null;
            return $massive;
        }
        function getGoodsBySubSubCategory($nameGoods,$idSubSubCategory,$limit,$sortBy,$country,$idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
                } else {
                     $language="_".$language;
                }
            $stringUser="";//товары пользователя
            $idUser=(int)$idUser;
            $stringGoodsAction=" goods.action='1' AND";
            if($idUser>0) {
                $stringUser=" goods.id_user='".(int)$idUser."' AND";
                $stringGoodsAction="";
            }
            $massive[]=null;
            $limit = $limit>0 ? $limit-1 : 0;
            $countRes=$this->countRes;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            if($sortBy==2)
            {
                $resCount=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS 
                ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage, c.currency AS cCurrency FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON 
                goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." 
                goods.id_subsubcategory='".(int)$idSubSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, 
                goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.name 
                LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
                $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency,goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subsubcategory='".(int)$idSubSubCategory."' AND 
                goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) DESC,gIsset DESC LIMIT ".$limit.",".$countRes."");
            }
            else if($sortBy==3)
            {
                $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subsubcategory='".(int)$idSubSubCategory."' AND 
                goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id");
                $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subsubcategory='".(int)$idSubSubCategory."' AND 
                goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
            }
            else
            {
                $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,
                MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage, c.currency AS cCurrency FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT 
                JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subsubcategory='".(int)$idSubSubCategory."' AND 
                goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id");
                $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId,goods.currency AS gCurrency, goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id LEFT 
                JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subsubcategory='".(int)$idSubSubCategory."' AND 
                goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
            }
            $i=0;
            for(;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            if($i==0)return null;
            return $massive;
        }
        function getGoodsByProperties($nameGoods,$properties,$idSubSubCategory,$limit,$sortBy,$country,$idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
                } else {
                     $language="_".$language;
                }
            
            $countRes=$this->countRes;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $idUser=(int)$idUser;
            $stringGoodsAction=" goods.action='1' AND";
            if($idUser>0) {
                $stringUser=" goods.id_user='".(int)$idUser."' AND";
                $stringGoodsAction="";
            }
            $properties=explode("}",$properties);
            $newSettingsProperties="";
            $arrayGoods=array();
            $arrayRemovedGoods=array();
            for($i=0;$i<count($properties)-1;$i++)
            {
                $arrayCurGoods=array();
                $curProp=explode(":",$properties[$i])[0];
                $curSet=explode(";",explode(":",$properties[$i])[1]);
                for($j=0;$j<count($curSet)-1;$j++)
                {
                    $resGoodsId=$this->con->query("SELECT id_goods FROM InfoSetAndPropertyGoods LEFT JOIN goods ON goods.id=InfoSetAndPropertyGoods.id_goods WHERE goods.id_subsubcategory='".(int)$idSubSubCategory."' AND InfoSetAndPropertyGoods.id IN(SELECT DISTINCT id_infoSetAndPropertyGoods FROM InfoSetAndProperty AS isp WHERE isp.id_property='".(int)$curProp."' AND isp.id_settings='".(int)$curSet[$j]."') GROUP BY goods.id ORDER BY goods.isset DESC");
                    while($rowGoodsId=$resGoodsId->fetch_assoc())
                    {
                        //Тут нужно будет доделать, так как при большом количестве товаров может быть задержка
                        //Прошлая задержка была решена путем добавлентя LEFT JOIN goods.id_subsubcategory
                        //Еще одна задержка была решена путем добавления GROUP BY goods.id
                        /*if(count($arrayGoods)>=$countRes)
                        {
                            break;
                        }*/
                        if(array_search($rowGoodsId['id_goods'],$arrayRemovedGoods)===false)
                        {
                            if($i==0)
                            {
                                array_push($arrayGoods,$rowGoodsId['id_goods']);
                            }
                            array_push($arrayCurGoods,$rowGoodsId['id_goods']);
                        }
                    }
                    /*if($j==count($curSet)-2&&$i==count($properties)-2)
                    {
                        $newSettingsProperties.=$curSet[$j];
                    }
                    else
                    {
                        $newSettingsProperties.=$curSet[$j].",";
                    }*/
                }
                for($s=0;$s<count($arrayGoods);$s++)
                {
                    if(array_search($arrayGoods[$s],$arrayCurGoods)===false)
                    {
                        array_push($arrayRemovedGoods,$arrayGoods[$s]);
                        unset($arrayGoods[$s]);
                    }
                }
            }
            $arrayGoods=array_unique($arrayGoods);
            $arrayGoods=array_filter($arrayGoods);
            //var_dump($arrayGoods);return;
            $massive=[];
            if($sortBy==2)
            {
                $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,goods.name".$language." AS gName, goods.currency AS gCurrency,goodsimg.image AS giImage,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,InfoSetAndPropertyGoods.id_goods AS gId,c.currency AS cCurrency FROM InfoSetAndProperty LEFT JOIN 
                InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=InfoSetAndProperty.id_infoSetAndPropertyGoods LEFT JOIN goods ON goods.id=InfoSetAndPropertyGoods.id_goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN 
                goodsimg ON goodsimg.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." goods.id IN(".implode(",",$arrayGoods).") AND goodsimg.is_main='1' AND 
                goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) 
                DESC LIMIT ".$limit.",".$countRes."");
                $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.name AS gName,goodsimg.image AS giImage,
                MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,InfoSetAndPropertyGoods.id_goods AS gId FROM InfoSetAndProperty LEFT JOIN 
                InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=InfoSetAndProperty.id_infoSetAndPropertyGoods LEFT JOIN goods ON goods.id=InfoSetAndPropertyGoods.id_goods LEFT JOIN 
                goodsimg ON goodsimg.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." goods.id IN(".implode(",",$arrayGoods).") AND goodsimg.is_main='1' AND 
                goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id");
            }
            else if($sortBy==3)
            {
                $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,goods.name".$language." AS gName,goodsimg.image AS giImage,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset, goods.currency AS gCurrency,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,InfoSetAndPropertyGoods.id_goods AS gId,c.currency AS cCurrency FROM InfoSetAndProperty LEFT JOIN 
                InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=InfoSetAndProperty.id_infoSetAndPropertyGoods LEFT JOIN goods ON goods.id=InfoSetAndPropertyGoods.id_goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN 
                goodsimg ON goodsimg.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." goods.id IN(".implode(",",$arrayGoods).") AND goodsimg.is_main='1' AND 
                goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) ASC LIMIT ".$limit.",".$countRes."");
                $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.name AS gName,goodsimg.image AS giImage,MAX(InfoSetAndPropertyGoods.price) 
                AS ispgMaxPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,InfoSetAndPropertyGoods.id_goods AS gId FROM InfoSetAndProperty LEFT JOIN InfoSetAndPropertyGoods ON 
                InfoSetAndPropertyGoods.id=InfoSetAndProperty.id_infoSetAndPropertyGoods LEFT JOIN goods ON goods.id=InfoSetAndPropertyGoods.id_goods LEFT JOIN goodsimg ON 
                goodsimg.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." goods.id IN(".implode(",",$arrayGoods).") AND goodsimg.is_main='1' AND 
                goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id");
            }
            else
            {
                $res=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes, goods.currency AS gCurrency,goods.price AS gPrice,goods.name".$language." AS gName,goodsimg.image AS giImage,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,InfoSetAndPropertyGoods.id_goods AS gId, c.currency AS cCurrency FROM InfoSetAndProperty LEFT JOIN 
                InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=InfoSetAndProperty.id_infoSetAndPropertyGoods LEFT JOIN goods ON goods.id=InfoSetAndPropertyGoods.id_goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg 
                ON goodsimg.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." goods.id IN(".implode(",",$arrayGoods).") AND goodsimg.is_main='1' AND 
                goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id ORDER BY gIsset DESC,grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                $resCount=$this->con->query("SELECT CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.name AS gName,goodsimg.image AS giImage,
                MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,InfoSetAndPropertyGoods.id_goods AS gId FROM InfoSetAndProperty LEFT JOIN 
                InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id=InfoSetAndProperty.id_infoSetAndPropertyGoods LEFT JOIN goods ON goods.id=InfoSetAndPropertyGoods.id_goods LEFT JOIN 
                goodsimg ON goodsimg.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser.$stringGoodsAction." goods.id IN(".implode(",",$arrayGoods).") AND goodsimg.is_main='1' AND 
                goods.id_subsubcategory='".(int)$idSubSubCategory."' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id");
            }
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            return $massive;
        }
        function getGoodsBySubCategory($idSubCategory,$nameGoods,$limit,$sortBy,$country,$idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
                } else {
                     $language="_".$language;
                }
            $stringUser="";//товары пользователя
            $idUser=(int)$idUser;
            $stringGoodsAction=" goods.action='1' AND";
            if($idUser>0) {
                $stringUser=" goods.id_user='".(int)$idUser."' AND";
                $stringGoodsAction="";
            }
            $sortBy=$sortBy<1?1:$sortBy;
            $massive[]=null;
            $countRes=$this->countRes;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            if($sortBy==2)
            {
                $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice, MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset, goods.currency AS gCurrency,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN 
                goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id WHERE".$stringUser." goods.id_subcategory='".(int)$idSubCategory."' 
                AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY 
                goods.id ORDER BY gIsset DESC, IF(gPrice='0', InfoSetAndPropertyGoods.price,gPrice) 
                DESC LIMIT ".$limit.",".$countRes."");
                $i=0;
                $resCount=$this->con->query("SELECT SUM(goodsraiting.raiting) AS grSum, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice, MIN(InfoSetAndPropertyGoods.price) AS 
                ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id 
                LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id WHERE".$stringUser." 
                goods.id_subcategory='".(int)$idSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, 
                goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.name 
                LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
            }
            else if($sortBy==3)
            {
                $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice, MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset, goods.currency AS gCurrency,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subcategory='".(int)$idSubCategory."' AND 
                goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id ORDER BY gIsset DESC,IF(gPrice='0',InfoSetAndPropertyGoods.price,gPrice) 
                ASC LIMIT ".$limit.",".$countRes."");
                $i=0;
                $resCount=$this->con->query("SELECT SUM(goodsraiting.raiting) AS grSum, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice, MIN(InfoSetAndPropertyGoods.price) AS 
                ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT 
                JOIN goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." 
                goods.id_subcategory='".(int)$idSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, 
                goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." 
                goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
            }
            else
            {
                $res=$this->con->query("SELECT goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice, MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset, goods.currency AS gCurrency,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name".$language." AS gName,goodsimg.image AS giImage, c.currency AS cCurrency,goods.id_user AS gIdUser FROM goods LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." goods.id_subcategory='".(int)$idSubCategory."' AND 
                goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND 
                (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' 
                GROUP BY goods.id ORDER BY gIsset DESC, grRes DESC,gId DESC LIMIT ".$limit.",".$countRes."");
                $i=0;
                $resCount=$this->con->query("SELECT SUM(goodsraiting.raiting) AS grSum, COUNT(goodsraiting.id) AS grCount,goods.price AS gPrice, MIN(InfoSetAndPropertyGoods.price) AS 
                ispgMinPrice,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON 
                goodsimg.id_goods=goods.id LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN 
                goodsDeliverCountry ON goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id WHERE".$stringUser." 
                goods.id_subcategory='".(int)$idSubCategory."' AND goods.is_deleted='0' AND goodsimg.is_main='1' AND IF(goodsDeliverCountryNot.id_country!=NULL, 
                goodsDeliverCountryNot.id_country!='".(int)$country."',TRUE) AND (goodsDeliverCountry.id_country='".(int)$country."' OR goodsDeliverCountry.id_country='0') AND".$stringGoodsAction." goods.name 
                LIKE '%".mysqli_real_escape_string($this->con,$nameGoods)."%' GROUP BY goods.id");
            }
            for(;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            if($i==0)return null;
            return $massive;
        }
        function getGoodsByIdUserAndName($idUser,$search,$limit)
        {
            $countRes=$this->countRes;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $massive[]=null;
            $res=$this->con->query("SELECT co.currency AS cCurrency, categories.category AS cName,subcategories.subcategory AS scName,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.id_user AS gIdUser,goods.price AS gPrice, MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice, MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,goods.currency AS gCurrency,goods.action AS gAction,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                LEFT JOIN subcategories ON goods.id_subcategory=subcategories.id LEFT JOIN categories ON categories.id=subcategories.id_category LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN country_ co ON co.id=goods.currency WHERE
                goods.is_deleted='0' AND goodsimg.is_main='1' AND goods.id_user='".(int)$idUser."' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$search)."%' GROUP BY 
                goods.id ORDER BY gId DESC LIMIT ".$limit.",".$countRes."");
            $resCount=$this->con->query("SELECT co.currency AS cCurrency, categories.category AS cName,subcategories.subcategory AS scName,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.id_user AS gIdUser,goods.price AS gPrice, MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice, MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset,goods.currency AS gCurrency,goods.isset AS gIsset,MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.name AS gName,goodsimg.image AS giImage FROM goods LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                LEFT JOIN subcategories ON goods.id_subcategory=subcategories.id LEFT JOIN categories ON categories.id=subcategories.id_category LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN country_ co ON co.id=goods.currency WHERE
                goods.is_deleted='0' AND goodsimg.is_main='1' AND goods.id_user='".(int)$idUser."' AND goods.searchName LIKE '%".mysqli_real_escape_string($this->con,$search)."%' GROUP BY 
                goods.id ORDER BY gId DESC");
                $i=0;
            for(;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            if($i==0)return null;
            return $massive;
        }
        function getGoodsSplitString($Variants)
        {
            $Variants=explode("}",$Variants);
            $ResString="";
            for($i=0;$i<count($Variants)-1;$i++)
            {
                $CurCariants=explode(",",$Variants[$i]);
                $CurString="";
                for($j=0;$CurCariants[$j];$j++)
                {
                    $CurValues=explode("_",$CurCariants[$j]);
                    $res=$this->con->query("SELECT p.id AS pId,p.name AS pName,s.id AS sId,s.name AS sName FROM property p,settings s WHERE p.id='".(int)$CurValues[0]."' AND 
                    s.id='".(int)$CurValues[1]."'");
                    if($row=$res->fetch_assoc())
                    {
                        $CurString.="PropertyId:".$row['pId'].",PropertyName:".$row['pName'].",SettingsId:".$row['sId'].",SettingsName:".$row['sName'].",;";
                    }
                }
                $CurString.="}";
                $ResString.=$CurString;
            }
            return $ResString;
        }
        function getGood($id)//Don`t use this function, it can be sql injected
        {
            if((int)$id!=0)
            {
                $resString=null;
            $res=$this->con->query("SELECT g.id_user AS gId_User,g.currency AS gCurrency,g.name AS gName,g.id_subcategory AS gId_SubCategory,g.id_subsubcategory AS gId_Subsubcategory,g.id AS gId, g.isset AS gIsset,
            g.price AS gPrice,gi.image AS giImage,c.currency AS cCurrency FROM goods g LEFT JOIN goodsimg gi ON g.id=gi.id_goods LEFT JOIN country_ c ON g.currency=c.id WHERE gi.is_main='1' AND g.is_deleted!='1' AND g.id='".(int)$id."'");
            if($row=$res->fetch_assoc())
            {
                    $curString="id_goods:".$row['gId'].",name:".$row["gName"].",id_user:".$row['gId_User'].",id_subcategory:".$row['gId_SubCategory'].",id_subsubcategory:
                    ".$row['gId_Subsubcategory'].",";
                    if(floatval($row["gPrice"])==0.0)
                    {
                        $curSetAndPropGoodsRow=$this->con->query("SELECT isp.id_infoSetAndPropertyGoods AS ispInfoSetAndPropertyGoods, ispg.isset AS ispgIsset,ispg.price AS ispgPrice,isp.id_property AS 
                        ispId_Property,isp.id_settings AS ispId_Settings FROM InfoSetAndPropertyGoods ispg,InfoSetAndProperty isp WHERE ispg.id=isp.id_infoSetAndPropertyGoods AND 
                        ispg.id_goods='".$row['gId']."' ORDER BY isp.id_infoSetAndPropertyGoods");
                        $rowIspId=0;
                        for($j=0;$rowIsp=$curSetAndPropGoodsRow->fetch_assoc();)
                        {
                            if($rowIspId!=$rowIsp['ispInfoSetAndPropertyGoods'])
                            {
                                $rowIspId=$rowIsp['ispInfoSetAndPropertyGoods'];
                                if($j==0)
                                {
                                    $j++;
                                }
                                else
                                {
                                    $curString.="}";
                                }
                                $curString.="price:".$rowIsp['ispgPrice']."_";
                            }
                                $curString.="property:".$rowIsp['ispId_Property'].";setting:".$rowIsp['ispId_Settings'].";isset:".$rowIsp['ispgIsset']."]";
                        }
                        $curString.="}";
                    }
                    else
                    {
                        $curString.="price:".$row['gPrice'].";;isset:".$row['gIsset'];
                    }
                    $gdcRes=$this->con->query("SELECT gDC.id_country AS GDCIdCountry,gDC.minCount AS GDCMinCount,gDC.price AS GDCPrice,gDC.deliveryDays AS gDCDeliveryDays FROM 
                    goodsDeliverCountry gDC WHERE gDC.id_goods='".(int)$id."'");
                    $curString.=",";
                    for(;$GDCRow=$gdcRes->fetch_assoc();)
                    {
                        $curString.="id_country:".$GDCRow['GDCIdCountry'].";minCount:".$GDCRow['GDCMinCount'].";price:".$GDCRow['GDCPrice'].";deliveryDays:".$GDCRow['gDCDeliveryDays'].";}";
                    }
                    $gdcNRes=$this->con->query("SELECT gDCN.id_country AS gDCNidCountry FROM goodsDeliverCountryNot gDCN WHERE gDCN.id_goods='".(int)$id."'");
                    $curString.=",";
                    for(;$GDCNRow=$gdcNRes->fetch_assoc();)
                    {
                        $curString.="id_country:".$GDCNRow['gDCNidCountry']."}";
                    }
                    $resString=$curString.",";
            }
            $resString.="currency:".$row['gCurrency'];
            $resString.=",giImage:".$row['giImage'];
            $resString.=",cCurrency:".$row['cCurrency'];
            return $resString;
            }  
            return null;
        }
        function AndroidgetGood($id,$language="ru")
        {
            if((int)$id!=0)
            {
                if($language=="ru") {
                $language="";
                } else {
                     $language="_".$language;
                }
                $resString=null;
            $res=$this->con->query("SELECT users.phone AS uPhone,g.id_user AS gId_User,g.description".$language." AS gDescription,g.currency AS gCurrency,c.currency AS cCurrency,g.name".$language." AS gName,g.id_subcategory AS gId_SubCategory,g.id_subsubcategory AS gId_Subsubcategory,g.id AS gId, g.isset AS gIsset,
            g.price AS gPrice,g.priceRoznica AS gPriceRoznica,gi.image AS giImage FROM goods g LEFT JOIN users ON users.id=g.id_user LEFT JOIN goodsimg gi ON g.id=gi.id_goods LEFT JOIN country_ c ON g.currency=c.id WHERE gi.is_main='1' AND g.is_deleted!='1' AND g.id='".(int)$id."'");
            $resPhotoes=$this->con->query("SELECT gi.image AS giImage FROM goodsimg gi WHERE gi.id_goods='".(int)$id."' AND gi.is_main!='1'");
            $massive[]=null;
            $massive['goodsSettings']="";
            $massive['goodsImg']=array();
            for($i=0;$rowPhotoes=$resPhotoes->fetch_assoc();$i++) {
                array_push($massive['goodsImg'],$rowPhotoes['giImage']);//+=$rowPhotoes['giImage']+",";
            }
            $massive['reviews']=$this->getGoodsReviews($id,0);
            if($row=$res->fetch_assoc())
            {
                $massive['goodsInfo']=$row;
                        $curString="";
                    if(floatval($row["gPrice"])==0.0)
                    {
                        $curSetAndPropGoodsRow=$this->con->query("SELECT isp.id_infoSetAndPropertyGoods AS ispInfoSetAndPropertyGoods, ispg.isset AS ispgIsset,ispg.price AS ispgPrice,isp.id_property AS 
                        ispId_Property,isp.id_settings AS ispId_Settings FROM InfoSetAndPropertyGoods ispg,InfoSetAndProperty isp LEFT JOIN property p ON p.id=isp.id_property WHERE  ispg.id=isp.id_infoSetAndPropertyGoods AND 
                        ispg.id_goods='".$row['gId']."' ORDER BY isp.id_infoSetAndPropertyGoods");//ispg.isset='1' AND
                        $rowIspId=0;
                        $curResSettings="";
                        for($j=0;$rowIsp=$curSetAndPropGoodsRow->fetch_assoc();)
                        {
                            if($rowIspId!=$rowIsp['ispInfoSetAndPropertyGoods'])
                            {
                                $rowIspId=$rowIsp['ispInfoSetAndPropertyGoods'];
                                if($j==0)
                                {
                                    $j++;
                                }
                                else
                                {
                                    $curString.="}";
                                }
                                $curString.="price:".$rowIsp['ispgPrice']."_";
                            }
                            //$curSetName=$this->Database->getSettingsById($rowIsp['ispId_Settings'])['name'];
                            //$curPropName=$this->Database->getPropertyById($rowIsp['ispId_Property'])['name'];
                            //$curString.="property:".$rowIsp['ispId_Property'].";setting:".$rowIsp['ispId_Settings'].";isset:".$rowIsp['ispgIsset'].";propName:".$curPropName.";setName:".$curSetName."]";
                            $curString.="property:".$rowIsp['ispId_Property'].";setting:".$rowIsp['ispId_Settings'].";isset:".$rowIsp['ispgIsset'].";propName:;setName:]";
                        }
                        $massive['goodsSettings']=$curString;
                    }
                    $gdcRes=$this->con->query("SELECT gDC.id_country AS GDCIdCountry,gDC.minCount AS GDCMinCount,gDC.price AS GDCPrice,gDC.deliveryDays AS gDCDeliveryDays FROM 
                    goodsDeliverCountry gDC WHERE gDC.id_goods='".(int)$id."'");
                    $curString.=",";
                    for($k=0;$GDCRow=$gdcRes->fetch_assoc();$k++)
                    {
                        $massive['goodsDeliveryCountry'][$k]=$GDCRow;
                    }
                    $gdcNRes=$this->con->query("SELECT gDCN.id_country AS gDCNidCountry FROM goodsDeliverCountryNot gDCN WHERE gDCN.id_goods='".(int)$id."'");
                    $curString.=",";
                    $stringGDCN="";
                    for($k=0;$GDCNRow=$gdcNRes->fetch_assoc();$k++)
                    {
                        $massive['goodsDeliveryCountryNot'][$k]=$GDCNRow;
                    }
            }
            return $massive;
            }  
            return null;
        }
        function getUsersGoods($id_user,$limit)//Don`t use this function, it can be sql injected
        {
            $countRes=$this->countRes;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $resString[]=null;
            $res=$this->con->query("SELECT g.action AS gAction,g.currency AS gCurrency,g.name AS gName,g.id_subcategory AS gId_SubCategory,g.id_subsubcategory AS gId_Subsubcategory,g.id AS gId,g.price AS gPrice FROM goods g,goodsimg gi 
            WHERE g.id_user='".(int)$id_user."' AND g.id=gi.id_goods AND gi.is_main='1' AND g.is_deleted='0' ORDER BY g.id DESC LIMIT ".$limit.",".$countRes."");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                    $curString="id_goods:".$row['gId'].",name:".$row["gName"].",id_subcategory:".$row['gId_SubCategory'].",gAction:".$row['gAction'].",id_subsubcategory:".$row['gId_Subsubcategory'].",";
                    if(floatval($row["gPrice"])==0.0)
                    {
                        
                        $minPrice=-1;
                        $maxPrice=-1;
                        $curSetAndPropGoodsRow=$this->con->query("SELECT isp.id_infoSetAndPropertyGoods AS ispInfoSetAndPropertyGoods,ispg.price AS ispgPrice,isp.id_property AS ispId_Property,isp.id_settings AS ispId_Settings 
                        FROM InfoSetAndPropertyGoods ispg,InfoSetAndProperty isp WHERE ispg.id=isp.id_infoSetAndPropertyGoods AND ispg.id_goods='".$row['gId']."' ORDER BY isp.id_infoSetAndPropertyGoods");
                        $rowIspId=0;
                        for(;$rowIsp=$curSetAndPropGoodsRow->fetch_assoc();)
                        {
                            if($rowIspId!=$rowIsp['ispInfoSetAndPropertyGoods'])
                            {
                                if($minPrice==-1)
                                {
                                    $minPrice=$rowIsp['ispgPrice'];
                                    $maxPrice=$rowIsp['ispgPrice'];
                                }
                                else
                                {
                                    if($minPrice>$rowIsp['ispgPrice'])
                                    {
                                        $minPrice=$rowIsp['ispgPrice'];
                                    }
                                    if($maxPrice<$rowIsp['ispgPrice'])
                                    {
                                        $maxPrice=$rowIsp['ispgPrice'];
                                    }
                                }
                                $rowIspId=$rowIsp['ispInfoSetAndPropertyGoods'];
                            }
                        }
                        if($minPrice!=$maxPrice)
                        {
                            $curString.="price:".$minPrice." - ".$maxPrice;
                        }
                        else
                        {
                            $curString.="price:".$minPrice;
                        }
                    }
                    else
                    {
                        $curString.="price:".$row['gPrice'];
                    }
                    $resString[$i]=$curString.",currency:".$row['gCurrency']."}";
                    
            }
            $resCount=$this->con->query("SELECT g.name AS gName,g.id_subcategory AS gId_SubCategory,g.id_subsubcategory AS gId_Subsubcategory,g.id AS gId,g.price AS gPrice FROM goods g,goodsimg gi 
            WHERE g.id_user='".(int)$id_user."' AND g.id=gi.id_goods AND gi.is_main='1' AND g.is_deleted='0'");
            $resString['countRows']=$resCount->num_rows;
            return $resString;   
        }
        function getGoodsRaitingByUser($idGoods,$idUser,$usersSendHash=0)
        {
            if(strcmp($usersSendHash,"0")!==0)
            {
                $res=$this->con->query("SELECT raiting FROM goodsraiting WHERE id_user=(SELECT DISTINCT id FROM users WHERE hash='".mysqli_real_escape_string($this->con, $usersSendHash)."' LIMIT 1) AND id_goods='".(int)$idGoods."'");
                $row=$res->fetch_assoc();
                return $row['raiting'];
            }
            $res=$this->con->query("SELECT raiting FROM goodsraiting WHERE id_user='".(int)$idUser."' AND id_goods='".(int)$idGoods."' AND id_user!=(SELECT id_user FROM goods WHERE 
            id='".(int)$idGoods."')");
            $row=$res->fetch_assoc();
            return $row['raiting'];
        }
        function getGoodsRaiting($idGoods)
        {
           $res=$this->con->query("SELECT raiting FROM goodsraiting WHERE id_goods='".(int)$idGoods."'");
                    $raitingSum=0.0;
                    $raitingCount=0;
                    for(;$row=$res->fetch_assoc();$raitingCount++)
                    {
                        $raitingSum+=(int)$row['raiting'];
                    }
                    if($raitingCount>0)
                    {
                        return $res=round($raitingSum/$raitingCount, 2);
                    }
                    return 0;
        }
        function getGoodsDescription($idGoods)
        {
            $res=$this->con->query("SELECT description FROM goods WHERE id='".(int)$idGoods."'");
            $row=$res->fetch_assoc();
            return $row['description'];
        }
        function getGoodsReviewByUser($idGoods,$idUser)
        {
            $res=$this->con->query("SELECT users.id AS id_user,users.name AS name,reviews.id AS reviews_id,reviews.text AS text FROM users LEFT JOIN reviews ON reviews.id_user=users.id WHERE 
            reviews.id_goods='".(int)$idGoods."' AND reviews.id_user='".(int)$idUser."'");
            return $res->fetch_assoc();
        }
        function getGoodsReviewByUserLoginPass($idGoods,$login,$password)
        {
            $res=$this->con->query("SELECT users.id AS id_user,users.name AS name,reviews.id AS reviews_id,reviews.text AS text FROM users LEFT JOIN reviews ON reviews.id_user=users.id WHERE 
            id_goods='".(int)$idGoods."' AND id_user=(SELECT DISTINCT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."')");
            return $res->fetch_assoc();
        }
        function getGoodsAllReviewsOrderByRevLikes($idGoods)
        {
            $res=$this->con->query("SELECT r.id AS id,r.id_user AS id_user,r.text AS text, COUNT(rl.id_review) AS total FROM reviews AS r LEFT JOIN review_likes AS rl ON r.id=rl.id_review WHERE 
            r.id_goods='".(int)$idGoods."' GROUP BY r.id ORDER BY total DESC");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }
        function getIsLikedReview($idReview,$idUser)
        {
            $res=$this->con->query("SELECT* FROM review_likes WHERE id_user='".(int)$idUser."' AND id_review='".(int)$idReview."'");
            return $res->fetch_assoc();
        }
        function getIsDisLikedReview($idReview,$idUser)
        {
            $res=$this->con->query("SELECT* FROM review_dislikes WHERE id_user='".(int)$idUser."' AND id_review='".(int)$idReview."'");
            return $res->fetch_assoc();
        }
        function getReviewLikesCount($idReview)
        {
            $res=$this->con->query("SELECT COUNT(*) AS countLikes FROM review_likes WHERE id_review='".(int)$idReview."'");
            $row=$res->fetch_assoc();
            return $row['countLikes']-1;
        }
        function getReviewDisLikesCount($idReview)
        {
            $res=$this->con->query("SELECT COUNT(*) AS countLikes FROM review_dislikes WHERE id_review='".(int)$idReview."'");
            $row=$res->fetch_assoc();
            return $row['countLikes']-1;
        }
        //AddReviewToGoods($idGoods,$text,$loginUser,$passwordUser,$token)
        function AddReviewToGoods($idGoods,$nameUser,$emailUser,$text,$rating,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(trim($nameUser)=="")
            {
                return "error_emptyName";
            }
            else if(trim($emailUser)=="")
            {
                return "error_emptyEmail";
            }
            else if(trim($text)=="")
            {
                return "error_emptyText";
            }
            else if($rating==0)
            {
                $rating=5;
            }
            $this->con->query("INSERT INTO reviews(name,email,id_goods,text,rating,isVisible) VALUES('".mysqli_real_escape_string($this->con,$nameUser)."','".mysqli_real_escape_string($this->con,$emailUser)."','".mysqli_real_escape_string($this->con,$idGoods)."','".mysqli_real_escape_string($this->con,$text)."','".mysqli_real_escape_string($this->con,$rating)."','0')");
            return true;
            /*$res=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con,$loginUser)."' AND 
            password='".mysqli_real_escape_string($this->con,$passwordUser)."' LIMIT 1");
            if($rowUser=$res->fetch_assoc())
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $res=$this->con->query("SELECT* FROM reviews WHERE id_goods='".(int)$idGoods."' AND id_user='".$rowUser['id']."'");
                if($row=$res->fetch_assoc())
                {
                    $this->con->query("UPDATE reviews SET text='".mysqli_real_escape_string($this->con,$text)."' WHERE id_user='".$rowUser['id']."' AND id_goods='".(int)$idGoods."'");
                }
                else
                {
                    $this->con->query("INSERT INTO reviews(text,id_user,id_goods) VALUES('".mysqli_real_escape_string($this->con,$text)."','".$rowUser['id']."','".(int)$idGoods."')");
                    $res=$this->con->query("SELECT id FROM reviews WHERE id_user='".$rowUser['id']."' AND id_goods='".(int)$idGoods."'");
                    $row=$res->fetch_assoc();
                    //$this->con->query("INSERT INTO review_likes(id_user,id_review) VALUES('".$rowUser['id']."','".(int)$row['id']."')");
                    //$this->con->query("INSERT INTO review_dislikes(id_user,id_review) VALUES('".$rowUser['id']."','".(int)$row['id']."')");
                }
                return true;
            }
            return false;*/
        }
        function setLikeToReview($loginUser,$passwordUser,$idReview,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con,$loginUser)."' AND 
            password='".mysqli_real_escape_string($this->con,$passwordUser)."' LIMIT 1");
            if($rowUser=$res->fetch_assoc())
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $resIssetLike=$this->con->query("SELECT* FROM review_likes WHERE id_user='".(int)$rowUser['id']."' AND id_review='".(int)$idReview."'");
                if($rowIssetLike=$resIssetLike->fetch_assoc())
                {
                    $this->con->query("DELETE FROM review_likes WHERE id_user='".(int)$rowUser['id']."' AND id_review='".(int)$idReview."'");
                    return "false";
                }
                else
                {
                    $resIssetDisLike=$this->con->query("SELECT* FROM review_dislikes WHERE id_user='".(int)$rowUser['id']."' AND id_review='".(int)$idReview."'");
                    if($rowIssetDisLike=$resIssetDisLike->fetch_assoc())
                    {
                        $this->con->query("DELETE FROM review_dislikes WHERE id_user='".(int)$rowUser['id']."' AND id_review='".(int)$idReview."'");
                        $this->con->query("INSERT INTO review_likes(id_user,id_review) VALUES('".(int)$rowUser['id']."','".(int)$idReview."')");
                        return "truefalse";
                    }
                    else
                    {
                        $this->con->query("INSERT INTO review_likes(id_user,id_review) VALUES('".(int)$rowUser['id']."','".(int)$idReview."')");
                        return "true";
                    }
                }
            }
            return "false";
        }
        function setDisLikeToReview($loginUser,$passwordUser,$idReview,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $res=$this->con->query("SELECT DISTINCT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con,$loginUser)."' AND 
            password='".mysqli_real_escape_string($this->con,$passwordUser)."' LIMIT 1");
            if($rowUser=$res->fetch_assoc())
            {
                if($rowUser['baned'])
                {
                    return "user_baned_error";
                }
                $resIssetDisLike=$this->con->query("SELECT* FROM review_dislikes WHERE id_user='".(int)$rowUser['id']."' AND id_review='".(int)$idReview."'");
                if($row=$resIssetDisLike->fetch_assoc())
                {
                    $this->con->query("DELETE FROM review_dislikes WHERE id_user='".(int)$rowUser['id']."' AND id_review='".(int)$idReview."'");
                    return "false";
                }
                else
                {
                    $resIssetLike=$this->con->query("SELECT* FROM review_likes WHERE id_user='".(int)$rowUser['id']."' AND id_review='".(int)$idReview."'");
                    if($rowIssetLike=$resIssetLike->fetch_assoc())
                    {
                        $this->con->query("DELETE FROM review_likes WHERE id_user='".(int)$rowUser['id']."' AND id_review='".(int)$idReview."'");
                        $this->con->query("INSERT INTO review_dislikes(id_user,id_review) VALUES('".(int)$rowUser['id']."','".(int)$idReview."')");
                        return "truefalse";
                    }
                    else
                    {
                        $this->con->query("INSERT INTO review_dislikes(id_user,id_review) VALUES('".(int)$rowUser['id']."','".(int)$idReview."')");
                        return "true";
                    }
                }
            }
            return "false";
        }
        function deleteReview($idReview,$loginUser,$passwordUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if($this->con->query("DELETE FROM reviews WHERE id='".(int)$idReview."' AND id_user=(SELECT id FROM users WHERE login='".mysqli_real_escape_string($this->con,$loginUser)."' AND password='".mysqli_real_escape_string($this->con,$passwordUser)."')"))
            {
                return true;
            }
            return false;
        }
        function addGood($login,$password,$name,$category,$currency,$description,$price,$titleImage,$inputWillDeliver,$inputWillDeliver_not,$VariantsInput,$VariantsPrices,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $count=$this->getTemporaryGoodsCount($login,$password,$token);
            $inputWillDeliverMassive=explode("}",$inputWillDeliver);
            $inputWillDeliver_notMassive=explode("_",$inputWillDeliver_not);
            $checkForVariants=false;
            if($this->CheckForPriceAndVariantsCount($VariantsInput,$VariantsPrices)&&floatval($price)<=0.0)
            {
                $checkForVariants=true;
            }
            if($UserGet=$this->con->query("SELECT* FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."'")->fetch_assoc())
            {
                if($UserGet['baned'])
                {
                    return "user_baned_error";
                }
                else if($UserGet['is_seller']!='1')
                {
                    return "isNot_seller";
                }
                    if(!(trim($name)==""||(int)$category<=0||(int)$titleImage<=0||(int)$currency==0||(int)$titleImage>$count||$count<=0||!$this->CheckForUniqueDeliverCountry($inputWillDeliver)
                    ||$this->CheckForEqualCountry($inputWillDeliver,$inputWillDeliver_not)||($checkForVariants==true&&floatval($price)>0.0)||($checkForVariants==false&&floatval($price)<=0.0)))
                    {
                        $this->con->query("INSERT INTO goods(id_user,name,searchName,description,id_subcategory,currency) VALUES('".(int)$UserGet['id']."','".mysqli_real_escape_string($this->con, $name)."','".mysqli_real_escape_string($this->con, $name)."','".mysqli_real_escape_string($this->con, $description)."','".(int)$category."',".(int)$currency.")");
                                    $res=$this->con->query("SELECT* FROM goods WHERE id_user='".(int)$UserGet['id']."' ORDER BY id DESC");
                                    $row=$res->fetch_assoc();
                                    $idgoods=(int)$row['id'];           
                        if(floatval($price)>0.0)
                        {
                            $VariantsInput=explode("}",$VariantsInput);
                            $i=0;
                            for(;$i<count($VariantsInput)-1;$i++)
                            {
                                $this->con->query("INSERT INTO InfoSetAndPropertyGoods(id_goods,price) VALUES('".(int)$idgoods."','".floatval($price)."')");
                                $res=$this->con->query("SELECT *FROM InfoSetAndPropertyGoods WHERE id_goods='".(int)$idgoods."' ORDER BY id DESC LIMIT 1");
                                $rowInfoSetAndProp=$res->fetch_assoc();
                                $idInfoSetAndpProperty=$rowInfoSetAndProp['id'];
                                $CurVariants=explode(",",$VariantsInput[$i]);
                                for($j=0;$j<count($CurVariants)-1;$j++)
                                {
                                    $SplitCurVariants=explode("_",$CurVariants[$j]);
                                    $CurProperty=$SplitCurVariants[0];
                                    $CurSetting=$SplitCurVariants[1];
                                    $this->con->query("INSERT INTO InfoSetAndProperty(id_infoSetAndPropertyGoods,id_property,id_settings) VALUES('".(int)$idInfoSetAndpProperty."','".(int)$CurProperty."','".(int)$CurSetting."')");
                                }
                            }
                            if($i==0)
                            {
                                $this->con->query("UPDATE goods SET price='".floatval($price)."' WHERE id='".(int)$idgoods."'");
                            }
                        }
                        else
                        {
                            $VariantsInput=explode("}",$VariantsInput);
                            $VariantsPrices=explode("}",$VariantsPrices);
                            for($i=0;$i<count($VariantsInput)-1;$i++)
                            {
                                $this->con->query("INSERT INTO InfoSetAndPropertyGoods(id_goods,price) VALUES('".(int)$idgoods."','".floatval($VariantsPrices[$i])."')");
                                $res=$this->con->query("SELECT *FROM InfoSetAndPropertyGoods WHERE id_goods='".(int)$idgoods."' ORDER BY id DESC LIMIT 1");
                                $rowInfoSetAndProp=$res->fetch_assoc();
                                $idInfoSetAndpProperty=$rowInfoSetAndProp['id'];
                                $CurVariants=explode(",",$VariantsInput[$i]);
                                for($j=0;$j<count($CurVariants)-1;$j++)
                                {
                                    $SplitCurVariants=explode("_",$CurVariants[$j]);
                                    $CurProperty=$SplitCurVariants[0];
                                    $CurSetting=$SplitCurVariants[1];
                                    $this->con->query("INSERT INTO InfoSetAndProperty(id_infoSetAndPropertyGoods,id_property,id_settings) VALUES('".(int)$idInfoSetAndpProperty."','".(int)$CurProperty."','".(int)$CurSetting."')");
                                }
                            }
                        }
                                    for($i=0;$inputWillDeliverMassive[$i]!=null;$i++)
                                    {
                                        $InfoDeliver=explode(",",$inputWillDeliverMassive[$i]);
                                        $this->con->query("INSERT INTO goodsDeliverCountry(id_country,id_goods,minCount,price,DeliveryDays) VALUES('".(int)$InfoDeliver[0]."','".(int)$row['id']."','".(int)$InfoDeliver[1]."','".(int)$InfoDeliver[2]."','".(int)$InfoDeliver[3]."')");
                                    }
                                    if($this->CheckDeliverNotString($inputWillDeliver_not))
                                    {
                                        for($i=0;$inputWillDeliver_notMassive[$i]!=null;$i++)
                                        {
                                            $this->con->query("INSERT INTO goodsDeliverCountryNot(id_country,id_goods) VALUES('".(int)$inputWillDeliver_notMassive[$i]."','".(int)$row['id']."')");
                                        }
                                    }    
                                    if((int)$_POST['hidden_SubSubInput']!=0)
                                    {
                                        $this->con->query("UPDATE goods SET id_subsubcategory='".(int)$_POST['hidden_SubSubInput']."' WHERE id='".$idgoods."'");
                                    }
                                    if(!(trim($_POST['hidden_settingInput'])==""))
                                    {
                                        $settingInput=explode(",",$_POST['hidden_settingInput']);
                                        for($i=0;$settingInput[$i];$i++)
                                        {
                                            $PropertyAndSettingDelimiter=explode("_",$settingInput[$i]);
                                            $this->con->query("INSERT INTO goodsproperty(id_goods,id_property,id_settings) VALUES('".$idgoods."','".(int)$PropertyAndSettingDelimiter[0]."','".(int)$PropertyAndSettingDelimiter[1]."')");
                                        }
                                    }
                                      $flag=false;
                                      $count=0;
                                      if($handle = opendir('storage/temporarygoods/'.(int)$UserGet['id'].'/'))
                                    {
                                                                while(false !== ($file = readdir($handle)))
                                                                {
                                                                        if($file != "." && $file != "..") 
                                                                        {
                                                                            $flag=true;
                                                                            $filename=uniqid();
                                                                            if($handle2 = opendir('storage/goodsimg/'.(int)$UserGet['id'].'/'))
                                                                            {   $filename=uniqid();
                                                                                $flag2=false;
                                                                                                    do{$filename=uniqid();
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
                                                                                                                    //rename('storage/temporary/'.$id.'/'.$file, "storage/wall/".$id."/rir");
                                                                                                                    //rename('storage/temporary/'.$id.'/'.$file, "storage/wall/".$id."/".$filename."");
                                                                                                                    //$query=mysql_query("INSERT INTO wallattach(id_wall,file) VALUES($idwall,'storage/wall/$id/$filename')");
                                                                                                        }
                                                                                                        }while($flag2);
                                                                                                        closedir($handle2);
                                                                    
                                                                            }
                                                                            $filename=$filename.".jpg";
                                                                            //rename('storage/temporary/'.$id.'/'.$file, "storage/wall/".$id."/rir");
                                                                            rename('storage/temporarygoods/'.(int)$UserGet['id'].'/'.$file, "storage/goodsimg/".(int)$UserGet['id']."/".$filename);
                                                                            $count++;
                                                                            if($count==(int)$titleImage)
                                                                            {
                                                                                $this->con->query("INSERT INTO goodsimg(id_goods,image,is_main) VALUES($idgoods,'storage/goodsimg/".(int)$UserGet['id']."/$filename','1')");
                                                                            }
                                                                            else
                                                                            {
                                                                                $this->con->query("INSERT INTO goodsimg(id_goods,image) VALUES($idgoods,'storage/goodsimg/".(int)$UserGet['id']."/$filename')");
                                                                            }
                                                                        }
                                                                }
                                                                //$this->con->query("INSERT INTO goodsraiting(id_goods,id_user,raiting) VALUES($idgoods,'".(int)(int)$UserGet['id']."','0')");        
                                                                closedir($handle);
                                    }
                                    
                        return("Success:".$idgoods);
                    }
                    else
                    {
                        if(trim($_POST['Nname'])=="")
                        {
                            return ("Nname_Error");
                        }
                        else if((int)$category<=0)
                        {
                            return("TextCategory_Error");
                        }
                        else if(!$checkForVariants&&floatval($price)<=0.0)
                        {
                            return ("price_Error");
                        }
                        else if(!$this->CheckForUniqueDeliverCountry($inputWillDeliver)
                        ||$this->CheckForEqualCountry($inputWillDeliver,$inputWillDeliver_not))
                        {
                        //else if(!$this->CheckForUniqueDeliverCountry($inputWillDeliver)
                        //||$this->CheckForEqualCountry($inputWillDeliver,$inputWillDeliver_not))
                            return("Deliver_Error");
                        }
                        else if($count<=0)
                        {
                            return("Image_Error");
                        }
                        else if((int)$titleImage<=0||(int)$titleImage>$count)
                        {
                            return("TitleImage_Error");
                        }
                        else if((int)$currency==0)
                        {
                            return("Currency_Error");
                        }
                        else
                        {
                            $VariantsInput=explode("}",$VariantsInput);
                            $VariantsPrices=explode("}",$VariantsPrices);
                            return(count($VariantsInput));
                        }
                    }                 
            }
        } 
        function getTemporaryGoodsImage($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $massive=array();
            if($UserGet=$this->con->query("SELECT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."'")->fetch_assoc())
            {
                if($handle = opendir('storage/temporarygoods/'.(int)$UserGet['id'].'/'))
                    {
                            for($i=0;false !== ($file = readdir($handle));)
                            {
                                if($file != "." && $file != "..") 
                                {
                                    array_push($massive, "storage/temporarygoods/".(int)$UserGet['id']."/".$file);
                                    $i++;
                                }
                            }     
                            closedir($handle);
                    }
            }
            return $massive;
        }
        function getGoodsImg($id,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if((int)$id!=0)
            {
                $massive[]=null;
                $res=$this->con->query("SELECT* FROM goodsimg WHERE id_goods='".(int)$id."' ORDER BY is_main DESC");
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
                return $massive;                
            }  
            return false;
        }
        function getGoodsImgMain($id,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if((int)$id>0)
            {
                $res=$this->con->query("SELECT* FROM goodsimg WHERE id_goods='".(int)$id."' AND is_main='1'");
                if($row=$res->fetch_assoc())
                {
                    return $row;
                }
            }  
            return false;
        }
        function getTemporaryGoodsCount($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $count=0;
            if($UserGet=$this->con->query("SELECT id FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."'")->fetch_assoc())
            {
                if($handle = opendir('storage/temporarygoods/'.(int)$UserGet['id'].'/'))
                    {
                            for(;false !== ($file = readdir($handle));)
                            {
                                if($file != "." && $file != "..") 
                                {
                                    $count++;
                                }
                            }     
                            closedir($handle);
                    }
            }
            return $count;
        }
	function unlinkTemporaryImg($login,$password,$token,$unlinkImage)
	{
	    if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $rowUser=$this->con->query("SELECT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."'")->fetch_assoc();
		    if($rowUser['baned']!='1')
		    {
		       unlink('storage/temporarygoods/'.$rowUser['id'].'/'.$unlinkImage);
		       return true;
		    }
    	     return false;
	}
	function compress($source, $destination, $quality) {

    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg') 
        $image = imagecreatefromjpeg($source);

    elseif ($info['mime'] == 'image/gif') 
        $image = imagecreatefromgif($source);

    elseif ($info['mime'] == 'image/png') 
        $image = imagecreatefrompng($source);

    imagejpeg($image, $destination, $quality);

    return $destination;
}
	    function addOneGoodImg($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
                //return $token." ".$_SESSION['token'];
            }
            $rowUser=$this->con->query("SELECT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."'")->fetch_assoc();
            if($rowUser['baned'])
            {
                return "user_baned_error";
            }
            $id=$rowUser['id'];
            if((int)$id==0) {
                return;
            }
            $error = false;
            $files = array();
            $uploaddir = 'storage/temporarygoods/'.$id.'/'; // . - текущая папка где находится submit.php
            $countAll=0;
         
            // Создадим папку если её нет
             if($handle = opendir('storage/temporarygoods/'.$id.'/'))
             {
                     while(false !== ($file = readdir($handle)))
                             if($file != "." && $file != "..") $countAll++;
                     closedir($handle);
             }
             if($countAll>30) return null;
             $count=0;
             $data[]=null;
             $i=0;
            // переместим файлы из временной директории в указанную
            foreach( $_FILES as $file ){
                /*if( move_uploaded_file( $file['tmp_name'], $uploaddir . basename($file['name']) ) ){
                    $files[] = realpath( $uploaddir . $file['name'] );
                }
                else{
                    $error = true;
                }*/
                $count++;
                if($count>30){break;}
                if ($error == UPLOAD_ERR_OK) {
                    $tmp_name = $file['tmp_name'];
                    $name = $file['name'];
                    $type = $file['type'];
                    $size=$file['size'];
                    $blacklist = array(".php", ".phtml", ".php3", ".php4");
                    $flag=true;
                    foreach ($blacklist as $item)
                    {
                     if(preg_match("/$item\$/i", $file['name']))
                     {
                     $flag=false;break;
                     }
                    }
                    if (($type == "image/jpg") || ($type == "image/jpeg"||$type="image/png")&&$flag) {
                        if($size<=10000000)
                        {
                            //$data[$i]="storage/temporarygoods/$id/$name";
                            $data[$i]="storage/temporarygoods/$id/$name";
                            move_uploaded_file($tmp_name, "storage/temporarygoods/$id/$name");
                            $image = imagecreatefromjpeg($data[$i]);
                            imagejpeg($image,$data[$i],25);
                            $i++;
                            //move_uploaded_file($tmp_name, "storage/temporarygoods/$id/$name");
                            //echo "<div style=\"float:left;margin-right:2px;margin-top:2px;width:150px;height:200px;background-image:url('storage/temporary/$id/$name.jpg');background-size:contain;background-repeat:no-repeat;margin-left:auto;\"></div>";
                        }
                    }
                }
            }
            return $data;
        }
        function addGoodsImg($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
                //return $token." ".$_SESSION['token'];
            }
            $rowUser=$this->con->query("SELECT id,baned FROM users WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con, $password)."'")->fetch_assoc();
            if($rowUser['baned'])
            {
                return "user_baned_error";
            }
            $id=$rowUser['id'];
            if((int)$id==0) {
                return;
            }
            $error = false;
            $files = array();
            $uploaddir = 'storage/temporarygoods/'.$id.'/'; // . - текущая папка где находится submit.php
         
            // Создадим папку если её нет
             if($handle = opendir('storage/temporarygoods/'.$id.'/'))
             {
                     while(false !== ($file = readdir($handle)))
                             if($file != "." && $file != "..") unlink('storage/temporarygoods/'.$id.'/'.$file);
                     closedir($handle);
             }
             $count=0;
             $data[]=null;
             $i=0;
            // переместим файлы из временной директории в указанную
            foreach( $_FILES as $file ){
                /*if( move_uploaded_file( $file['tmp_name'], $uploaddir . basename($file['name']) ) ){
                    $files[] = realpath( $uploaddir . $file['name'] );
                }
                else{
                    $error = true;
                }*/
                $count++;
                if($count>30){break;}
                if ($error == UPLOAD_ERR_OK) {
                    $tmp_name = $file['tmp_name'];
                    $name = $file['name'];
                    $type = $file['type'];
                    $size=$file['size'];
                    $blacklist = array(".php", ".phtml", ".php3", ".php4");
                    $flag=true;
                    foreach ($blacklist as $item)
                    {
                     if(preg_match("/$item\$/i", $file['name']))
                     {
                     $flag=false;break;
                     }
                    }
                    if (($type == "image/jpg") || ($type == "image/jpeg"||$type="image/png")&&$flag) {
                        if($size<=10000000)
                        {
                            $data[$i]="storage/temporarygoods/$id/$name";
                            move_uploaded_file($tmp_name, "storage/temporarygoods/$id/$name");
                            $image = imagecreatefromjpeg($data[$i]);
                            imagejpeg($image,$data[$i],25);
                            //compress($data[$i],NULL,25);
                            $i++;
                            //echo "<div style=\"float:left;margin-right:2px;margin-top:2px;width:150px;height:200px;background-image:url('storage/temporary/$id/$name.jpg');background-size:contain;background-repeat:no-repeat;margin-left:auto;\"></div>";
                        }
                    }
                }
            }
            return $data;
        }
    }
?>