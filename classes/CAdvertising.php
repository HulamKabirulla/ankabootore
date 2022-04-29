<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    class Advertising{
        private $con;
        function __construct($con) {
            $this->con=$con;
        }
        function getAdvertesmentMain($idCountry,$orderRand,$lan="ru")
            {
                $stringRand=$orderRand>0?" ORDER BY rand()":"";
                $queryLan="";
                if($lan=="en")
                {
                    $queryLan="_".$lan;
                }
                $res=$this->con->query("SELECT a.id,goods.action AS gAction,CEIL(SUM(goodsraiting.raiting)/COUNT(goodsraiting.id)) AS grRes,goods.price AS gPrice,MIN(InfoSetAndPropertyGoods.price) AS ispgMinPrice,MAX(InfoSetAndPropertyGoods.isset) AS ispgIsset,goods.isset AS gIsset, c.currency".$queryLan." AS cCurrency, MAX(InfoSetAndPropertyGoods.price) AS ispgMaxPrice,goods.id AS gId, goods.currency AS gCurrency, goods.name".$queryLan." AS gName,goodsimg.image AS giImage,goods.id_user AS gIdUser FROM advertising a LEFT JOIN goods ON a.id_goods=goods.id LEFT JOIN country_ c ON c.id=goods.currency LEFT JOIN goodsimg ON goodsimg.id_goods=goods.id 
                        LEFT JOIN InfoSetAndPropertyGoods ON InfoSetAndPropertyGoods.id_goods=goods.id LEFT JOIN goodsraiting ON goodsraiting.id_goods=goods.id LEFT JOIN goodsDeliverCountry ON 
                        goodsDeliverCountry.id_goods=goods.id LEFT JOIN goodsDeliverCountryNot ON goodsDeliverCountryNot.id_goods=goods.id GROUP BY goods.id ORDER BY a.id ");
                $massive[]=null;
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                    //echo $i;
                }
                //var_dump($massive);
                return $massive;
            }
    }
?>