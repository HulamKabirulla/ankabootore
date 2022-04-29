<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */

/*
<?xml version="1.0" encoding="utf-8"?>
<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">
    <channel>
        <title>Data feed title</title>
        <description>Data feed description</description>
        <link>http://ankabootore.com</link>u

        </channel>
</rss>
*///header('Content-Type: text/plain');
ini_set("display_errors",1);
error_reporting(E_ALL);
?>
<?php
    include_once("classes/CCheck.php");
    class Admin{
        use Check;
        private $con;
        private $firstCategoryIdArray=array();
        private $secondSubCategoryArray=array();
        private $thirdSubSubCategoryArray=array();
        private $fourthGoogleMerchantArray=array();
        function __construct($con) 
        {
            $this->con=$con;
            $this->setConsctructorValues();

                     
        }
        function jpgConvert($file)
            {
                $justName = explode(".", $file)[0];
                echo $justName."<br/>";
                if (strpos($file, ".jpg") === false)
                {
        		imagewebp($file, 'php.webp');
                }
                else
                {
                if (strpos($file, ".jpeg") === false)
        		$image = imagecreatefromjpeg($file);
        	    else if (strpos($file, ".png") === false) 
        		$image = imagecreatefrompng($file);
                ob_start();
        		imagejpeg($image,NULL,100);
    
                $cont=ob_get_contents();
                ob_end_clean();
                imagedestroy($image);
                $content = imagecreatefromstring($cont) or die();
                imagewebp($content,$justName.'.webp');
                imagedestroy($content);
                unlink($file);
                }
            }
            
            function pngConvert($file)
            {
                $justName = explode(".", $file)[0];
                echo $justName."<br/>";
                $image = imagecreatefromstring(file_get_contents($file));
                ob_start();
                imagejpeg($image,NULL,100);
                $cont = ob_get_contents();
                ob_end_clean();
                imagedestroy($image);
                $content = imagecreatefromstring($cont);
                $output = $justName.'.webp';
                imagewebp($content,$output);
                imagedestroy($content);
                unlink($file);
            }
        function getAllImages()
        {
        set_time_limit(0);
            $res=$this->con->query("SELECT* FROM goodsimg WHERE id_goods=39038");
            //$massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                echo $row['id']."<br/>";
                $file=$row['image'];
                echo strpos($file, ".jpg")."<br/>";
                if(strpos($file, ".png")!==false)
                {
                    $this->pngConvert($file);
                }
                else if(strpos($file, ".jpg")!==false||strpos($file, ".jpeg")!==false)
                {
                    $this->jpgConvert($file);
                }
                //$massive[$i]=$row;
            }
            //return $massive;
        }
        function getAllSubSubCategories()
        {
            $res=$this->con->query("SELECT* FROM subsubcategories WHERE action=1");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                
            echo $i;
                $massive[$i]=$row;
               // $this->con->query("INSERT INTO dublicates(id,url, redirectUrl) VALUES(NULL,'/Search.php?SubSubCategory=".$row['id']."','/Search.php?lan=ru&SubSubCategory=".$row['id']."')");
            }
            return $massive;
        }
        function getAllProperties()
        {
            $res=$this->con->query("SELECT* FROM property WHERE id>1220");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }
        function getAllSettings()
        {
            $res=$this->con->query("SELECT* FROM settings WHERE id>=1221");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }//33282
        function getAllGoodsSiteMap7()
        {//
            //$res=$this->con->query("SELECT* FROM goods WHERE is_deleted!=1 AND description_ua IS NULL");
            $res=$this->con->query("SELECT* FROM goods WHERE is_deleted!=1");
            $massive[]=null;
            for($i=0,$j=0;$row=$res->fetch_assoc();$i++)
            {
                if($i<=35000||$i>40000)
                {
                    continue;
                }
                $massive[$j]=$row;
                $j++;
            }
            return $massive;
        }
        function getAllGoodsSiteMap6()
        {//
            //$res=$this->con->query("SELECT* FROM goods WHERE is_deleted!=1 AND description_ua IS NULL");
            $res=$this->con->query("SELECT* FROM goods WHERE is_deleted!=1");
            $massive[]=null;
            for($i=0,$j=0;$row=$res->fetch_assoc();$i++)
            {
                if($i<=30259||$i>35000)
                {
                    continue;
                }
                $massive[$j]=$row;
                $j++;
            }
            return $massive;
        }
        function getAllGoodsSiteMap5()
        {//
            //$res=$this->con->query("SELECT* FROM goods WHERE is_deleted!=1 AND description_ua IS NULL");
            $res=$this->con->query("SELECT* FROM goods WHERE is_deleted!=1");
            $massive[]=null;
            for($i=0,$j=0;$row=$res->fetch_assoc();$i++)
            {
                if($i<=25000||$i>30259)
                {
                    continue;
                }
                $massive[$j]=$row;
                $j++;
            }
            return $massive;
        }
        function getAllGoods()
        {//
            //$res=$this->con->query("SELECT* FROM goods WHERE is_deleted!=1 AND description_ua IS NULL");
            $res=$this->con->query("SELECT* FROM goods WHERE is_deleted!=1");
            $massive[]=null;
            for($i=0,$j=0;$row=$res->fetch_assoc();$i++)
            {
                if($i<=20000||$i>25000)
                {
                    continue;
                }
                $massive[$j]=$row;
                $j++;
            }
            return $massive;
        }
        function updateAvailability($xml)
        {
        set_time_limit(0);
        //echo count($xml->shop->offers->offer);
        //return;42115
        //for($i=count($xml->shop->offers->offer)-1;$i>0;$i--) {
        
            for($i=count($xml->shop->offers->offer)-1;$i>=0;$i--) {
                $name=$xml->shop->offers->offer[$i]->name;
                $resGoods=$this->con->query("SELECT DISTINCT id FROM goods WHERE name LIKE '".mysqli_real_escape_string($this->con,$name)."' AND is_deleted!=1");
                
                //if($i==20)
                  //return;
                //echo var_dump($xml->shop->offers->offer[$i]);
                //echo var_dump($xml->shop->offers->offer[$i]->attributes());
                /*foreach($xml->shop->offers->offer[$i]->attributes() as $a => $b) {
                        if(strpos($b,"available")!==false)
                        {
                              echo $a,'="',$b,"\"\n";
                        }
                  }*/
                  echo $i."<br/>";
                    if($row=$resGoods->fetch_assoc())
                    {
                        foreach($xml->shop->offers->offer[$i]->attributes() as $a => $b) {
                              if(strpos($a,"available")!==false&&strcmp("",$b)===0)
                              {
                                    //echo $a,'="',$b,"\"\n";
                                    //echo $name."<br/>";
                                    $this->con->query("UPDATE goods SET isset=0 WHERE id='".$row['id']."'");
                                    $this->con->query("UPDATE InfoSetAndPropertyGoods SET isset=0 WHERE id_goods='".$row['id']."'");
                                    break;
                              }
                              else if(strpos($a,"available")!==false&&strcmp("true",$b)===0)
                              {
                                    //echo $a,'="',$b,"\"\n";
                                    //echo $name."<br/>";
                                    $this->con->query("UPDATE goods SET isset=1 WHERE id='".$row['id']."'");
                                    $this->con->query("UPDATE InfoSetAndPropertyGoods SET isset=1 WHERE id_goods='".$row['id']."'");
                                    break;
                              }
                        }
                    }
            }   
        }
        function setConsctructorValues()
        {
            //4657538 - Мыло
            array_push($this->firstCategoryIdArray, "4657538");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "146");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4628884 - Изделия из резины и латекса
            array_push($this->firstCategoryIdArray, "4628884");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "145");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4644352 - Одноразовые перчатки
            array_push($this->firstCategoryIdArray, "4644352");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "145");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //155515 - Цепные пилы
            array_push($this->firstCategoryIdArray, "155515");
            array_push($this->secondSubCategoryArray, "21");
            array_push($this->thirdSubSubCategoryArray, "144");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //155072 - Газонокосилки
            array_push($this->firstCategoryIdArray, "155072");
            array_push($this->secondSubCategoryArray, "21");
            array_push($this->thirdSubSubCategoryArray, "143");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4628912 - Аптечки
            array_push($this->firstCategoryIdArray, "4628912");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "142");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4629375 - Витамины и минеральные добавки
            array_push($this->firstCategoryIdArray, "4629375");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "141");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //274789 - Витамины и минералы
            array_push($this->firstCategoryIdArray, "274789");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "141");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4637839 - Мужские футболки
            array_push($this->firstCategoryIdArray, "4637839");
            array_push($this->secondSubCategoryArray, "6");
            array_push($this->thirdSubSubCategoryArray, "139");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //
            array_push($this->firstCategoryIdArray, "4634985");
            array_push($this->secondSubCategoryArray, "18");
            array_push($this->thirdSubSubCategoryArray, "138");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4654055 - Скейтборды
            array_push($this->firstCategoryIdArray, "4654055");
            array_push($this->secondSubCategoryArray, "39");
            array_push($this->thirdSubSubCategoryArray, "137");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4654041 - Лонгборды
            array_push($this->firstCategoryIdArray, "4654041");
            array_push($this->secondSubCategoryArray, "39");
            array_push($this->thirdSubSubCategoryArray, "136");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //189145 - Детские скейтборды
            array_push($this->firstCategoryIdArray, "189145");
            array_push($this->secondSubCategoryArray, "39");
            array_push($this->thirdSubSubCategoryArray, "135");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //155018 - Садовые принадлежности
            array_push($this->firstCategoryIdArray, "155018");
            array_push($this->secondSubCategoryArray, "21");
            array_push($this->thirdSubSubCategoryArray, "128");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //85169 - Удилища
            array_push($this->firstCategoryIdArray, "85169");
            array_push($this->secondSubCategoryArray, "49");
            array_push($this->thirdSubSubCategoryArray, "126");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4626836 - Пищевые контейнеры
            array_push($this->firstCategoryIdArray, "4626836");
            array_push($this->secondSubCategoryArray, "38");
            array_push($this->thirdSubSubCategoryArray, "125");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //102848 - Пазлы
            array_push($this->firstCategoryIdArray, "102848");
            array_push($this->secondSubCategoryArray, "14");
            array_push($this->thirdSubSubCategoryArray, "147");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //97422 - Радиоуправляемые игрушки
            array_push($this->firstCategoryIdArray, "97422");
            array_push($this->secondSubCategoryArray, "14");
            array_push($this->thirdSubSubCategoryArray, "124");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            
            //81232 - Весы напольные
            array_push($this->firstCategoryIdArray, "81232");
            array_push($this->secondSubCategoryArray, "48");
            array_push($this->thirdSubSubCategoryArray, "123");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //82412 - Палатки для отдыха
            array_push($this->firstCategoryIdArray, "82412");
            array_push($this->secondSubCategoryArray, "41");
            array_push($this->thirdSubSubCategoryArray, "122");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            
            //4330821 - Автошины
            array_push($this->firstCategoryIdArray, "4330821");
            array_push($this->secondSubCategoryArray, "12");
            array_push($this->thirdSubSubCategoryArray, "29");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //2635172 - Тонометры
            array_push($this->firstCategoryIdArray, "2635172");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "133");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //81230 - Медицинские приборы
            array_push($this->firstCategoryIdArray, "81230");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "0");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4627816 - Термометры для тела
            array_push($this->firstCategoryIdArray, "4627816");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "131");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //269512 - Термометры
            array_push($this->firstCategoryIdArray, "269512");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "131");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //81233 - Массажеры
            array_push($this->firstCategoryIdArray, "81233");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "130");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //2798842 - Средства индивидуальной защиты
            array_push($this->firstCategoryIdArray, "2798842");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "134");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4650159 - Антисептики
            array_push($this->firstCategoryIdArray, "4650159");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "140");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4644368 - Одноразовые медицинские маски
            array_push($this->firstCategoryIdArray, "4644368");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "134");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            
            //4626133 - Аксессуры к медицинским приборам
            array_push($this->firstCategoryIdArray, "4626133");
            array_push($this->secondSubCategoryArray, "46");
            array_push($this->thirdSubSubCategoryArray, "0");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //80179 - Йогуртницы и мороженицы
            array_push($this->firstCategoryIdArray, "80179");
            array_push($this->secondSubCategoryArray, "17");
            array_push($this->thirdSubSubCategoryArray, "118");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //139406 - Детские самокаты
            array_push($this->firstCategoryIdArray, "139406");
            array_push($this->secondSubCategoryArray, "39");
            array_push($this->thirdSubSubCategoryArray, "117");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4657382 - Электросамокаты
            array_push($this->firstCategoryIdArray, "4657382");
            array_push($this->secondSubCategoryArray, "39");
            array_push($this->thirdSubSubCategoryArray, "116");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4657346 - Гироскутеры
            array_push($this->firstCategoryIdArray, "4657346");
            array_push($this->secondSubCategoryArray, "39");
            array_push($this->thirdSubSubCategoryArray, "115");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //100389 - Детские коляски
            array_push($this->firstCategoryIdArray, "100389");
            array_push($this->secondSubCategoryArray, "45");
            array_push($this->thirdSubSubCategoryArray, "114");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //273298 - Аксессуары для спортивного питания
            array_push($this->firstCategoryIdArray, "273298");
            array_push($this->secondSubCategoryArray, "44");
            array_push($this->thirdSubSubCategoryArray, "0");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4634169 - Кеды для девочек
            array_push($this->firstCategoryIdArray, "4634169");
            array_push($this->secondSubCategoryArray, "43");
            array_push($this->thirdSubSubCategoryArray, "112");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4634153 - Кроссовки для девочек
            array_push($this->firstCategoryIdArray, "4634153");
            array_push($this->secondSubCategoryArray, "43");
            array_push($this->thirdSubSubCategoryArray, "111");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4633777 - Шлепанцы для мальчиков
            array_push($this->firstCategoryIdArray, "4633777");
            array_push($this->secondSubCategoryArray, "42");
            array_push($this->thirdSubSubCategoryArray, "110");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4633721 - Кеды для мальчиков
            array_push($this->firstCategoryIdArray, "4633721");
            array_push($this->secondSubCategoryArray, "42");
            array_push($this->thirdSubSubCategoryArray, "109");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4633657 - Кроссовки для мальчиков
            array_push($this->firstCategoryIdArray, "4633657");
            array_push($this->secondSubCategoryArray, "42");
            array_push($this->thirdSubSubCategoryArray, "108");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4634889 - Шлепанцы мужские
            array_push($this->firstCategoryIdArray, "4634889");
            array_push($this->secondSubCategoryArray, "18");
            array_push($this->thirdSubSubCategoryArray, "107");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4634489 - Кеды женские
            array_push($this->firstCategoryIdArray, "4634489");
            array_push($this->secondSubCategoryArray, "31");
            array_push($this->thirdSubSubCategoryArray, "85");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4634921 - Кеды мужские
            array_push($this->firstCategoryIdArray, "4634921");
            array_push($this->secondSubCategoryArray, "18");
            array_push($this->thirdSubSubCategoryArray, "95");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //1086704 - Средства защиты от насекомых
            array_push($this->firstCategoryIdArray, "1086704");
            array_push($this->secondSubCategoryArray, "41");
            array_push($this->thirdSubSubCategoryArray, "106");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //90423 - Дорожные сумки
            array_push($this->firstCategoryIdArray, "90423");
            array_push($this->secondSubCategoryArray, "41");
            array_push($this->thirdSubSubCategoryArray, "105");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //104132 - Детские велосипеды
            array_push($this->firstCategoryIdArray, "104132");
            array_push($this->secondSubCategoryArray, "39");
            array_push($this->thirdSubSubCategoryArray, "102");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //83884 - Велосипеды
            array_push($this->firstCategoryIdArray, "83884");
            array_push($this->secondSubCategoryArray, "39");
            array_push($this->thirdSubSubCategoryArray, "101");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //82741 - Наборы для пикника
            array_push($this->firstCategoryIdArray, "82741");
            array_push($this->secondSubCategoryArray, "38");
            array_push($this->thirdSubSubCategoryArray, "100");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4629992 - Солнцезащитные очки
            array_push($this->firstCategoryIdArray, "4629992");
            array_push($this->secondSubCategoryArray, "40");
            array_push($this->thirdSubSubCategoryArray, "0");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //80186 - Вентиляторы
            array_push($this->firstCategoryIdArray, "80186");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "99");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            
            //82696 - Мангалы, барбекю, гриль
            array_push($this->firstCategoryIdArray, "82696");
            array_push($this->secondSubCategoryArray, "38");
            array_push($this->thirdSubSubCategoryArray, "0");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4634865 - Мужские кроссовки
            array_push($this->firstCategoryIdArray, "4634865");
            array_push($this->secondSubCategoryArray, "18");
            array_push($this->thirdSubSubCategoryArray, "82");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4634473 - Женские кроссовки
            array_push($this->firstCategoryIdArray, "4634473");
            array_push($this->secondSubCategoryArray, "31");
            array_push($this->thirdSubSubCategoryArray, "84");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //80133 - Кондиционеры
            array_push($this->firstCategoryIdArray, "80133");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "96");
            //array_push($this->fourthGoogleMerchantArray, "Toys & Games > Toys > Play Vehicles > Toy Trucks & Construction Vehicles");
            array_push($this->fourthGoogleMerchantArray, "0");
            
            //4635233 - Женские пуховики
            array_push($this->firstCategoryIdArray, "4635233");
            array_push($this->secondSubCategoryArray, "9");
            array_push($this->thirdSubSubCategoryArray, "80");
            //array_push($this->fourthGoogleMerchantArray, "Toys & Games > Toys > Play Vehicles > Toy Trucks & Construction Vehicles");
            array_push($this->fourthGoogleMerchantArray, "3296");
            
            //4635311 - Мужские зимние куртки
            array_push($this->firstCategoryIdArray, "4635311");
            array_push($this->secondSubCategoryArray, "6");
            array_push($this->thirdSubSubCategoryArray, "74");
            //array_push($this->fourthGoogleMerchantArray, "Toys & Games > Toys > Play Vehicles > Toy Trucks & Construction Vehicles");
            array_push($this->fourthGoogleMerchantArray, "3296");
            
            //4637999 - Мужские свитера
            array_push($this->firstCategoryIdArray, "4637999");
            array_push($this->secondSubCategoryArray, "6");
            array_push($this->thirdSubSubCategoryArray, "66");
            //array_push($this->fourthGoogleMerchantArray, "Toys & Games > Toys > Play Vehicles > Toy Trucks & Construction Vehicles");
            array_push($this->fourthGoogleMerchantArray, "3296");
            
            //4637999 - Мужские свитера
            array_push($this->firstCategoryIdArray, "4637999");
            array_push($this->secondSubCategoryArray, "6");
            array_push($this->thirdSubSubCategoryArray, "66");
            //array_push($this->fourthGoogleMerchantArray, "Toys & Games > Toys > Play Vehicles > Toy Trucks & Construction Vehicles");
            array_push($this->fourthGoogleMerchantArray, "3296");

            //102308 - Автотреки
            array_push($this->firstCategoryIdArray, "102308");
            array_push($this->secondSubCategoryArray, "14");
            array_push($this->thirdSubSubCategoryArray, "73");
            //array_push($this->fourthGoogleMerchantArray, "Toys & Games > Toys > Play Vehicles > Toy Trucks & Construction Vehicles");
            array_push($this->fourthGoogleMerchantArray, "3296");

            //4627554 - Фитнес-браслеты
            array_push($this->firstCategoryIdArray, "4627554");
            array_push($this->secondSubCategoryArray, "35");
            array_push($this->thirdSubSubCategoryArray, "72");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Jewelry > Watches");
            array_push($this->fourthGoogleMerchantArray, "201");

            //80122 - Плиты
            array_push($this->firstCategoryIdArray, "80122");
            array_push($this->secondSubCategoryArray, "4");
            array_push($this->thirdSubSubCategoryArray, "70");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Cooktops");
            array_push($this->fourthGoogleMerchantArray, "679");   

            //80141 - Духовые шкафы
            array_push($this->firstCategoryIdArray, "80141");
            array_push($this->secondSubCategoryArray, "4");
            array_push($this->thirdSubSubCategoryArray, "71");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Ovens");
            array_push($this->fourthGoogleMerchantArray, "683");   

            //387969 - Универсальные мобильные батареи
            array_push($this->firstCategoryIdArray, "387969");
            array_push($this->secondSubCategoryArray, "34");
            array_push($this->thirdSubSubCategoryArray, "69");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Electronics Accessories > Power > Power Adapters & Chargers");
            array_push($this->fourthGoogleMerchantArray, "505295");

            //146229 - Чехлы для мобильных телефонов
            array_push($this->firstCategoryIdArray, "146229");
            array_push($this->secondSubCategoryArray, "34");
            array_push($this->thirdSubSubCategoryArray, "68");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Communications > Telephony > Mobile Phone Accessories > Mobile Phone Cases");
            array_push($this->fourthGoogleMerchantArray, "2353");

            //80003 - Мобильные телефоны
            array_push($this->firstCategoryIdArray, "80003");
            array_push($this->secondSubCategoryArray, "33");
            array_push($this->thirdSubSubCategoryArray, "87");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Communications > Telephony > Mobile Phones");
            array_push($this->fourthGoogleMerchantArray, "267");

            //80027 - Наушники
            array_push($this->firstCategoryIdArray, "80027");
            array_push($this->secondSubCategoryArray, "34");
            array_push($this->thirdSubSubCategoryArray, "3");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Audio > Audio Components > Headphones & Headsets > Headphones");
            array_push($this->fourthGoogleMerchantArray, "543626");

            //80003 - GPS навигаторы
            array_push($this->firstCategoryIdArray, "80047");
            array_push($this->secondSubCategoryArray, "37");
            array_push($this->thirdSubSubCategoryArray, "49");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > GPS Navigation Systems");
            array_push($this->fourthGoogleMerchantArray, "339");

            //80124 - Стиральные машины
            array_push($this->firstCategoryIdArray, "80124");
            array_push($this->secondSubCategoryArray, "4");
            array_push($this->thirdSubSubCategoryArray, "7");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Laundry Appliances > Washing Machines");
            array_push($this->fourthGoogleMerchantArray, "2549");


            //80125 - Холодильники
            array_push($this->firstCategoryIdArray, "80125");
            array_push($this->secondSubCategoryArray, "4");
            array_push($this->thirdSubSubCategoryArray, "8");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Refrigerators");
            array_push($this->fourthGoogleMerchantArray, "686");

            //80142 - Электрические печи
            array_push($this->firstCategoryIdArray, "80142");
            array_push($this->secondSubCategoryArray, "17");
            array_push($this->thirdSubSubCategoryArray, "50");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Ovens");//eeeeeeee
            array_push($this->fourthGoogleMerchantArray, "683");

            //80155 - Блендеры
            array_push($this->firstCategoryIdArray, "80155");
            array_push($this->secondSubCategoryArray, "17");
            array_push($this->thirdSubSubCategoryArray, "51");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Food Mixers & Blenders");
            array_push($this->fourthGoogleMerchantArray, "505666");

            //80156 - Миксеры
            array_push($this->firstCategoryIdArray, "80156");
            array_push($this->secondSubCategoryArray, "17");
            array_push($this->thirdSubSubCategoryArray, "52");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Food Mixers & Blenders");
            array_push($this->fourthGoogleMerchantArray, "505666");
            
            //80158 - Пылесосы
            array_push($this->firstCategoryIdArray, "80158");
            array_push($this->secondSubCategoryArray, "5");
            array_push($this->thirdSubSubCategoryArray, "9");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Vacuums");
            array_push($this->fourthGoogleMerchantArray, "619");
            
            //80160 - Электрочайники
            array_push($this->firstCategoryIdArray, "80160");
            array_push($this->secondSubCategoryArray, "17");
            array_push($this->thirdSubSubCategoryArray, "53");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Electric Kettles");
            array_push($this->fourthGoogleMerchantArray, "751");
            
            //80161 - Утюги и гладильные системы
            array_push($this->firstCategoryIdArray, "80161");
            array_push($this->secondSubCategoryArray, "5");
            array_push($this->thirdSubSubCategoryArray, "22");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Laundry Appliances > Irons & Ironing Systems");
            array_push($this->fourthGoogleMerchantArray, "5139");
            
            //80162 - Микроволновые печи
            array_push($this->firstCategoryIdArray, "80162");
            array_push($this->secondSubCategoryArray, "4");
            array_push($this->thirdSubSubCategoryArray, "54");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Microwave Ovens");
            array_push($this->fourthGoogleMerchantArray, "753");

            //80164 - Кофеварки
            array_push($this->firstCategoryIdArray, "80164");
            array_push($this->secondSubCategoryArray, "17");
            array_push($this->thirdSubSubCategoryArray, "55");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Coffee Makers & Espresso Machines > Drip Coffee Makers");
            array_push($this->fourthGoogleMerchantArray, "1388");

            //80164 - Инфракрасные обогреватели
            array_push($this->firstCategoryIdArray, "80247");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "18");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Climate Control Appliances > Space Heaters");
            array_push($this->fourthGoogleMerchantArray, "611");

            //80248 - Масляные радиаторы
            array_push($this->firstCategoryIdArray, "80248");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "56");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Climate Control Appliances > Space Heaters");
            array_push($this->fourthGoogleMerchantArray, "611");

            //80250 - Тепловентиляторы
            array_push($this->firstCategoryIdArray, "80250");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "56");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Climate Control Appliances > Space Heaters");
            array_push($this->fourthGoogleMerchantArray, "611");

            //81225 - Эпиляторы
            array_push($this->firstCategoryIdArray, "81225");
            array_push($this->secondSubCategoryArray, "32");
            array_push($this->thirdSubSubCategoryArray, "12");
            //array_push($this->fourthGoogleMerchantArray, "Health & Beauty > Personal Care > Shaving & Grooming > Hair Removal > Epilators");
            array_push($this->fourthGoogleMerchantArray, "4510");

            //81229 - Машинки для стрижки и триммеры
            array_push($this->firstCategoryIdArray, "81229");
            array_push($this->secondSubCategoryArray, "32");
            array_push($this->thirdSubSubCategoryArray, "11");
            //array_push($this->fourthGoogleMerchantArray, "Health & Beauty > Personal Care > Shaving & Grooming > Hair Clippers & Trimmers");
            array_push($this->fourthGoogleMerchantArray, "533");

            //81234 - Электрические простыни и одеяла
            array_push($this->firstCategoryIdArray, "81234");
            array_push($this->secondSubCategoryArray, "19");
            array_push($this->thirdSubSubCategoryArray, "27");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Linens & Bedding > Bedding > Blankets");
            array_push($this->fourthGoogleMerchantArray, "1985");

            //83244 - Мобильный интернет
            array_push($this->firstCategoryIdArray, "83244");
            array_push($this->secondSubCategoryArray, "35");
            array_push($this->thirdSubSubCategoryArray, "58");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Networking > Modems");
            array_push($this->fourthGoogleMerchantArray, "343");

            //91451 - Наручные часы
            array_push($this->firstCategoryIdArray, "91451");
            array_push($this->secondSubCategoryArray, "20");
            array_push($this->thirdSubSubCategoryArray, "59");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Jewelry > Watches");
            array_push($this->fourthGoogleMerchantArray, "201");

            //100209 - Термобелье
            array_push($this->firstCategoryIdArray, "100209");
            array_push($this->secondSubCategoryArray, "6");
            array_push($this->thirdSubSubCategoryArray, "44");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Clothing > Uniforms > Sports Uniforms");
            array_push($this->fourthGoogleMerchantArray, "3598");

            //101909 - Детские кроватки
            array_push($this->firstCategoryIdArray, "101909");
            array_push($this->secondSubCategoryArray, "11");
            array_push($this->thirdSubSubCategoryArray, "60");
            //array_push($this->fourthGoogleMerchantArray, "Furniture > Baby & Toddler Furniture > Cribs & Toddler Beds");
            array_push($this->fourthGoogleMerchantArray, "6394");

            //103214 - Комоды и пеленаторы
            array_push($this->firstCategoryIdArray, "103214");
            array_push($this->secondSubCategoryArray, "11");
            array_push($this->thirdSubSubCategoryArray, "35");
            //array_push($this->fourthGoogleMerchantArray, "Furniture > Cabinets & Storage > Dressers");
            array_push($this->fourthGoogleMerchantArray, "4195");

            //112986 - Мультиварки
            array_push($this->firstCategoryIdArray, "112986");
            array_push($this->secondSubCategoryArray, "17");
            array_push($this->thirdSubSubCategoryArray, "61");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Kitchen & Dining > Kitchen Appliances > Food Cookers & Steamers > Slow Cookers");
            array_push($this->fourthGoogleMerchantArray, "737");

            //120571 - Шкафы в детскую комнату
            array_push($this->firstCategoryIdArray, "120571");
            array_push($this->secondSubCategoryArray, "11");
            array_push($this->thirdSubSubCategoryArray, "62");
            //array_push($this->fourthGoogleMerchantArray, "Furniture > Cabinets & Storage > Storage Cabinets & Lockers");
            array_push($this->fourthGoogleMerchantArray, "5938");

            //146202 - Защитные пленки и стекла
            array_push($this->firstCategoryIdArray, "146202");
            array_push($this->secondSubCategoryArray, "34");
            array_push($this->thirdSubSubCategoryArray, "5");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Electronics Accessories > Electronics Films & Shields > Screen Protectors");
            array_push($this->fourthGoogleMerchantArray, "5468");

            //150741 - Уличные обогреватели
            array_push($this->firstCategoryIdArray, "150741");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "48");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Climate Control Appliances > Patio Heaters");
            array_push($this->fourthGoogleMerchantArray, "2649");

            //155285 - Компьютерные столы
            array_push($this->firstCategoryIdArray, "155285");
            array_push($this->secondSubCategoryArray, "11");
            array_push($this->thirdSubSubCategoryArray, "22");
            //array_push($this->fourthGoogleMerchantArray, "Furniture > Tables");///////////
            array_push($this->fourthGoogleMerchantArray, "6392");///////////

            //167428 - Карбоновые обогреватели
            array_push($this->firstCategoryIdArray, "167428");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "19");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Climate Control Appliances > Space Heaters");
            array_push($this->fourthGoogleMerchantArray, "611");

            //169825 - Одеяла
            array_push($this->firstCategoryIdArray, "169825");
            array_push($this->secondSubCategoryArray, "19");
            array_push($this->thirdSubSubCategoryArray, "45");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Linens & Bedding > Bedding > Blankets");
            array_push($this->fourthGoogleMerchantArray, "1985");

            //169826 - Подушка
            array_push($this->firstCategoryIdArray, "169826");
            array_push($this->secondSubCategoryArray, "19");
            array_push($this->thirdSubSubCategoryArray, "46");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Linens & Bedding > Bedding > Pillows");
            array_push($this->fourthGoogleMerchantArray, "2700");

            //173873 - Покрывала
            array_push($this->firstCategoryIdArray, "173873");
            array_push($this->secondSubCategoryArray, "19");
            array_push($this->thirdSubSubCategoryArray, "63");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Linens & Bedding > Bedding > Bed Sheets");
            array_push($this->fourthGoogleMerchantArray, "2314");

            //177055 - Матрасы
            array_push($this->firstCategoryIdArray, "177055");
            array_push($this->secondSubCategoryArray, "19");
            array_push($this->thirdSubSubCategoryArray, "39");
            //array_push($this->fourthGoogleMerchantArray, "Furniture > Beds & Accessories > Mattresses");
            array_push($this->fourthGoogleMerchantArray, "2696");

            //177055 - Наматрасники и подматрасники
            array_push($this->firstCategoryIdArray, "177612");
            array_push($this->secondSubCategoryArray, "19");
            array_push($this->thirdSubSubCategoryArray, "47");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Linens & Bedding > Bedding > Bed Sheets");
            array_push($this->fourthGoogleMerchantArray, "2314");

            //177055 - Радиаторы отопления
            array_push($this->firstCategoryIdArray, "228148");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "64");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Climate Control Appliances > Space Heaters");
            array_push($this->fourthGoogleMerchantArray, "611");

            //237815 - Роботы-пылесосы
            array_push($this->firstCategoryIdArray, "237815");
            array_push($this->secondSubCategoryArray, "5");
            array_push($this->thirdSubSubCategoryArray, "10");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Vacuums");
            array_push($this->fourthGoogleMerchantArray, "619");

            //246720 - Микатермические обогреватели
            array_push($this->firstCategoryIdArray, "246720");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "17");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Climate Control Appliances > Space Heaters");
            array_push($this->fourthGoogleMerchantArray, "611");

            //252133 - Фотоэпиляторы
            array_push($this->firstCategoryIdArray, "252133");
            array_push($this->secondSubCategoryArray, "32");
            array_push($this->thirdSubSubCategoryArray, "21");
            //array_push($this->fourthGoogleMerchantArray, "Health & Beauty > Personal Care > Shaving & Grooming > Hair Removal > Epilators");
            array_push($this->fourthGoogleMerchantArray, "4510");

            //437994 - Зубные щетки и ирригаторы
            array_push($this->firstCategoryIdArray, "437994");
            array_push($this->secondSubCategoryArray, "36");
            array_push($this->thirdSubSubCategoryArray, "0");//89 - щетки, 90 - ирригаторы
            //array_push($this->fourthGoogleMerchantArray, "Health & Beauty > Personal Care > Oral Care");
            array_push($this->fourthGoogleMerchantArray, "526");

            //651392 - Смарт-часы
            array_push($this->firstCategoryIdArray, "651392");
            array_push($this->secondSubCategoryArray, "35");
            array_push($this->thirdSubSubCategoryArray, "4");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Jewelry > Watches");
            array_push($this->fourthGoogleMerchantArray, "201");

            //1321132 - Сумки
            array_push($this->firstCategoryIdArray, "1321132");
            array_push($this->secondSubCategoryArray, "30");
            array_push($this->thirdSubSubCategoryArray, "88");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Handbags, Wallets & Cases");
            array_push($this->fourthGoogleMerchantArray, "6551");

            //1564297 - Керамические панели
            array_push($this->firstCategoryIdArray, "1564297");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "20");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Climate Control Appliances > Space Heaters");
            array_push($this->fourthGoogleMerchantArray, "611");

            //2048522 - Парфюмерия
            array_push($this->firstCategoryIdArray, "2048522");
            array_push($this->secondSubCategoryArray, "15");
            array_push($this->thirdSubSubCategoryArray, "0");// 65 - масла, 23,24 - мужские,женские парфумы
            //array_push($this->fourthGoogleMerchantArray, "Health & Beauty > Personal Care > Cosmetics > Perfume & Cologne");
            array_push($this->fourthGoogleMerchantArray, "479");

            //2678297 - Ароматизаторы в машину
            array_push($this->firstCategoryIdArray, "2678297");
            array_push($this->secondSubCategoryArray, "12");
            array_push($this->thirdSubSubCategoryArray, "29");
            //array_push($this->fourthGoogleMerchantArray, "Vehicles & Parts > Vehicle Parts & Accessories");
            array_push($this->fourthGoogleMerchantArray, "5613");

            //2769487 - Автопринадлежности
            array_push($this->firstCategoryIdArray, "2769487");
            array_push($this->secondSubCategoryArray, "12");
            array_push($this->thirdSubSubCategoryArray, "0"); //30 - Обогреватели, 31 - Пылесосы, 32 - Вентилторы
            //array_push($this->fourthGoogleMerchantArray, "Vehicles & Parts > Vehicle Parts & Accessories");
            array_push($this->fourthGoogleMerchantArray, "5613");

            //4627561 - Аксессуары для смарт-часов и трекеров
            array_push($this->firstCategoryIdArray, "4627561");
            array_push($this->secondSubCategoryArray, "34");
            array_push($this->thirdSubSubCategoryArray, "0"); //13 - Ремешки для смарт-часов
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Jewelry > Watch Accessories");
            array_push($this->fourthGoogleMerchantArray, "5122");

            //4627561 - Кровати
            array_push($this->firstCategoryIdArray, "4627785");
            array_push($this->secondSubCategoryArray, "11");
            array_push($this->thirdSubSubCategoryArray, "36");
            //array_push($this->fourthGoogleMerchantArray, "Furniture > Beds & Accessories > Beds & Bed Frames");
            array_push($this->fourthGoogleMerchantArray, "505764");

            //4628982 - Мебель в прихожую
            array_push($this->firstCategoryIdArray, "4628982");
            array_push($this->secondSubCategoryArray, "11");
            array_push($this->thirdSubSubCategoryArray, "0"); //прихожие - вешалки -
            //array_push($this->fourthGoogleMerchantArray, "Furniture");
            array_push($this->fourthGoogleMerchantArray, "436");

            //4630377 - Рюкзаки
            array_push($this->firstCategoryIdArray, "4630377");
            array_push($this->secondSubCategoryArray, "30");
            array_push($this->thirdSubSubCategoryArray, "91");
            //array_push($this->fourthGoogleMerchantArray, "Luggage & Bags > Backpacks");
            array_push($this->fourthGoogleMerchantArray, "100");

            //4631249 - Пылесосы для сухой уборки
            array_push($this->firstCategoryIdArray, "4631249");
            array_push($this->secondSubCategoryArray, "5");
            array_push($this->thirdSubSubCategoryArray, "93");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Vacuums");
            array_push($this->fourthGoogleMerchantArray, "619");

            //4631249 - Моющие пылесосы
            array_push($this->firstCategoryIdArray, "4631256");
            array_push($this->secondSubCategoryArray, "5");
            array_push($this->thirdSubSubCategoryArray, "94");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Vacuums");
            array_push($this->fourthGoogleMerchantArray, "619");

            //80004 - Ноутбуки
            array_push($this->firstCategoryIdArray, "80004");
            array_push($this->secondSubCategoryArray, "2");
            array_push($this->thirdSubSubCategoryArray, "1");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Computers > Laptops");
            array_push($this->fourthGoogleMerchantArray, "328");

            //80036 - Сумки, рюкзаки и чехлы для ноутбуков
            array_push($this->firstCategoryIdArray, "80036");
            array_push($this->secondSubCategoryArray, "7");
            array_push($this->thirdSubSubCategoryArray, "16"); // 14 - рюкзаки, 15 - сумки, 16 - чехлы
            //array_push($this->fourthGoogleMerchantArray, "Luggage & Bags");
            array_push($this->fourthGoogleMerchantArray, "5181");

            //80095 - Компьютеры, неттопы, моноблоки
            array_push($this->firstCategoryIdArray, "80095");
            array_push($this->secondSubCategoryArray, "2");
            array_push($this->thirdSubSubCategoryArray, "2");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Computers");
            array_push($this->fourthGoogleMerchantArray, "278");

            //130309 - Планшеты
            array_push($this->firstCategoryIdArray, "130309");
            array_push($this->secondSubCategoryArray, "3");
            array_push($this->thirdSubSubCategoryArray, "6");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Computers > Tablet Computers");
            array_push($this->fourthGoogleMerchantArray, "4745");

            //4635121 - Защитные стекла
            array_push($this->firstCategoryIdArray, "4635121");
            array_push($this->secondSubCategoryArray, "34");
            array_push($this->thirdSubSubCategoryArray, "5");
            //array_push($this->fourthGoogleMerchantArray, "Electronics > Electronics Accessories > Electronics Films & Shields > Screen Protectors");
            array_push($this->fourthGoogleMerchantArray, "5468");

            //4633409 - Женские ботинки
            array_push($this->firstCategoryIdArray, "4633409");
            array_push($this->secondSubCategoryArray, "31");
            array_push($this->thirdSubSubCategoryArray, "86");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Shoes");
            array_push($this->fourthGoogleMerchantArray, "187");

            //4633433 - Женские сапоги
            array_push($this->firstCategoryIdArray, "4633433");
            array_push($this->secondSubCategoryArray, "31");
            array_push($this->thirdSubSubCategoryArray, "86");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Shoes");
            array_push($this->fourthGoogleMerchantArray, "187");

            //4633681 - Женские угги
            array_push($this->firstCategoryIdArray, "4633681");
            array_push($this->secondSubCategoryArray, "31");
            array_push($this->thirdSubSubCategoryArray, "86");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Shoes");
            array_push($this->fourthGoogleMerchantArray, "187");
            

            //4634705 - Женские ботильоны
            array_push($this->firstCategoryIdArray, "4634705");
            array_push($this->secondSubCategoryArray, "31");
            array_push($this->thirdSubSubCategoryArray, "86");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Shoes");
            array_push($this->fourthGoogleMerchantArray, "187");

            //4634777 - Женские сапоги,угги
            array_push($this->firstCategoryIdArray, "4634777");
            array_push($this->secondSubCategoryArray, "31");
            array_push($this->thirdSubSubCategoryArray, "86");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Shoes");
            array_push($this->fourthGoogleMerchantArray, "187");
            

            //4634953 - Мужские ботинки 4634953
            array_push($this->firstCategoryIdArray, "4634953");
            array_push($this->secondSubCategoryArray, "18");
            array_push($this->thirdSubSubCategoryArray, "1");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Shoes");
            array_push($this->fourthGoogleMerchantArray, "40");

            //4634953 - Мужские ботинки
            array_push($this->firstCategoryIdArray, "4634777");
            array_push($this->secondSubCategoryArray, "18");
            array_push($this->thirdSubSubCategoryArray, "1");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Shoes");
            array_push($this->fourthGoogleMerchantArray, "187");


            //4635025 - Мужские угги
            array_push($this->firstCategoryIdArray, "4635025");
            array_push($this->secondSubCategoryArray, "18");
            array_push($this->thirdSubSubCategoryArray, "1");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Shoes");
            array_push($this->fourthGoogleMerchantArray, "187");

            //4634449 - Электрические конвекторы
            array_push($this->firstCategoryIdArray, "4634449");
            array_push($this->secondSubCategoryArray, "10");
            array_push($this->thirdSubSubCategoryArray, "67");
            //array_push($this->fourthGoogleMerchantArray, "Home & Garden > Household Appliances > Climate Control Appliances > Space Heaters");
            array_push($this->fourthGoogleMerchantArray, "611");

            //4637439 - Женские носки
            array_push($this->firstCategoryIdArray, "4637439");
            array_push($this->secondSubCategoryArray, "9");
            array_push($this->thirdSubSubCategoryArray, "42");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Clothing > Underwear & Socks > Socks");
            array_push($this->fourthGoogleMerchantArray, "209");

            //4637727 - Мужские носки
            array_push($this->firstCategoryIdArray, "4637727");
            array_push($this->secondSubCategoryArray, "9");
            array_push($this->thirdSubSubCategoryArray, "41");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Clothing > Underwear & Socks > Socks");
            array_push($this->fourthGoogleMerchantArray, "209");

            //4654587 - Автомобильные вентиляторы
            array_push($this->firstCategoryIdArray, "4654587");
            array_push($this->secondSubCategoryArray, "13");
            array_push($this->thirdSubSubCategoryArray, "32");
            //array_push($this->fourthGoogleMerchantArray, "Vehicles & Parts > Vehicle Parts & Accessories");
            array_push($this->fourthGoogleMerchantArray, "5613");

            //4654580 - Автомобильные обогреватели
            array_push($this->firstCategoryIdArray, "4654580");
            array_push($this->secondSubCategoryArray, "13");
            array_push($this->thirdSubSubCategoryArray, "30");
            //array_push($this->fourthGoogleMerchantArray, "Vehicles & Parts > Vehicle Parts & Accessories");
            array_push($this->fourthGoogleMerchantArray, "5613");
            
            //4630244 - Перчатки и варежки
            array_push($this->firstCategoryIdArray, "4630244");
            array_push($this->secondSubCategoryArray, "9");
            array_push($this->thirdSubSubCategoryArray, "43");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Clothing Accessories > Gloves & Mittens");
            array_push($this->fourthGoogleMerchantArray, "170");
            
            //4630244 - Головные уборы
            array_push($this->firstCategoryIdArray, "4629998");
            array_push($this->secondSubCategoryArray, "6");
            array_push($this->thirdSubSubCategoryArray, "76");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Clothing Accessories > Gloves & Mittens");
            array_push($this->fourthGoogleMerchantArray, "170");

            //4630244 - Шарфы
            array_push($this->firstCategoryIdArray, "4630335");
            array_push($this->secondSubCategoryArray, "6");
            array_push($this->thirdSubSubCategoryArray, "77");
            //array_push($this->fourthGoogleMerchantArray, "Apparel & Accessories > Clothing Accessories > Gloves & Mittens");
            array_push($this->fourthGoogleMerchantArray, "170");
        }
        function convertToGoogleShoppingXml()
        {            //echo "12";
            //var_dump($this->fourthGoogleMerchantArray);a
            $res=$this->con->query("SELECT g.isset AS gIsset,g.vendor AS gVendor,g.description AS gDesc,g.id_subcategory AS gIdSubCategory,g.id_subsubcategory AS gIdSubSubCategory,gi.image AS giImage,g.id AS gId,g.name AS gName, g.is_deleted AS gDeleted,ispg.isset AS ispgIsset,ispg.price AS ispgPrice FROM goods g LEFT JOIN InfoSetAndPropertyGoods ispg ON ispg.id_goods=g.id LEFT JOIN goodsimg gi ON gi.id_goods=g.id WHERE gi.is_main=1 GROUP BY ispg.id_goods");// LIMIT 0,32000

                $count=0;
                
                echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>";
                echo "<rss xmlns:g=\"http://base.google.com/ns/1.0\" version=\"2.0\">";
                echo "<channel>
        <title>Data feed title</title>
        <description>Data feed description</description>
        <link>https://ankabootore.com</link>";
            while($row=$res->fetch_assoc())
            {
                if($row['ispgPrice']<300)
                    continue;
                $i=0;
                for(;$i<count($this->secondSubCategoryArray);$i++)
                {
                    if($row['gIdSubCategory']==$this->secondSubCategoryArray[$i]&&$row['gIdSubSubCategory']==$this->thirdSubSubCategoryArray[$i])
                    {
                        //echo $this->fourthGoogleMerchantArray[$i];
                        //echo $i."<br/>";
                        break;
                    }
                }
                $curAvailability=$row['ispgIsset']==1?"in stock":"out of stock";////eeeeeee check it
                if($row['gDeleted']==1)
                {
                    $curAvailability="out of stock";
                }
                if($row['gIsset']==0)
                {
                    $curAvailability="out of stock";
                }
                echo "<item>";
                    echo "<g:id>".$row['gId']."</g:id>";
                    echo "<g:condition>new</g:condition>";
                    echo "<g:availability>".$curAvailability."</g:availability>";
                    echo "<g:price>".$row['ispgPrice']." UAH</g:price>";
                    echo "<g:google_product_category>".$this->fourthGoogleMerchantArray[$i]."</g:google_product_category>";
                    echo "<title>";
                    echo htmlspecialchars($row['gName']);
                    echo "</title>";
                    echo "<link>ankabootore.com/GoodsDesc.php?goods=".$row['gId']."</link>";
                    $description= str_replace("<br/>", ". ", $row['gDesc']);
                    echo "<description>";
                    echo htmlspecialchars($description);
                    echo "</description>";
                    echo "<g:image_link>ankabootore.com/".$row['giImage']."</g:image_link>";
                    if($row['gVendor']!=null)
                    {
                        echo "<g:brand>";
                        echo htmlspecialchars($row['gVendor']);
                        echo "</g:brand>";
                    }
                echo "</item>";
            }
                echo "</channel></rss>";
        }
        function updateVendor($xml)
        {
        set_time_limit(0);
            for($i=0;$xml->shop->offers->offer[$i];$i++) {
                //var_dump($xml->shop->offers->offer[$i]);
                //echo $xml->shop->offers->offer[$i]->vendor;
                $name=$xml->shop->offers->offer[$i]->name;
                $this->con->query("UPDATE goods SET vendor='".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->vendor)."' WHERE name LIKE '".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->name)."'");
                //echo $name;
                //return;
            }
        }
        function updatePrice($xml)
        {
            set_time_limit(0);
            ini_set('max_execution_time', 0);
            //$i=count($xml->shop->offers->offer)-1
            //36286
            for($i=count($xml->shop->offers->offer)-1;$i>=0;$i--) {
                //if($i>19767)
                    //return;
                $name=$xml->shop->offers->offer[$i]->name;
                $idGoods=$this->con->query("SELECT DISTINCT id FROM goods WHERE name LIKE '".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->name)."' AND is_deleted!=1")->fetch_assoc();
                if($idGoods)
                {
                    $idGoods=$idGoods['id'];
                }
                else
                {
                    continue;
                }
                //echo $i."<br/>";
                echo $i.":".$name."<br/>";
                $this->con->query("UPDATE InfoSetAndPropertyGoods SET price='".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->price)."' WHERE id_goods='".$idGoods."' AND price!='".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->price)."'");
                //return;
                //echo $i."<br/>";
                //echo "UPDATE InfoSetAndPropertyGoods SET price='".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->price)."' WHERE id_goods=(SELECT DISTINCT id FROM goods WHERE name LIKE '".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->name)."');"."<br/>";;
                //return;
                    //$this->con->query("UPDATE InfoSetAndPropertyGoods SET price='".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->price)."' WHERE id_goods=(SELECT DISTINCT id FROM goods WHERE name LIKE '".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->name)."')");
                
            }
        }
        function updateDescription($xml)
        {
        set_time_limit(0);
            //$xml->shop->offers->offer[$i]->description;
            for($i=0;$xml->shop->offers->offer[$i];$i++) {
                //var_dump($xml->shop->offers->offer[$i]);
                //echo $xml->shop->offers->offer[$i]->vendor;
                //4633409,4633433,4633681,4634705,4634777
                if($xml->shop->offers->offer[$i]->categoryId!=100209)
                {
                    /*$xml->shop->offers->offer[$i]->categoryId!=4633409&&$xml->shop->offers->offer[$i]->categoryId!=4633433&&$xml->shop->offers->offer[$i]->categoryId!=4633681&&$xml->shop->offers->offer[$i]->categoryId!=4634705&&$xml->shop->offers->offer[$i]->categoryId!=4634777*/
                    continue;
                }
                $name=$xml->shop->offers->offer[$i]->name;
                $description=$xml->shop->offers->offer[$i]->description."<br/>";
                
                //$description="";
                for($j=0;$xml->shop->offers->offer[$i]->param[$j];$j++) {
                    $s=0;
                    foreach($xml->shop->offers->offer[$i]->param[$j]->attributes() as $a => $b) {
                        if(trim($b)==""||$s>0||strpos($b,"Артикул")!==false)
                            continue;
                        //echo $xml->shop->offers->offer[$i]->param[$j]."meow";
                        $s++;
                        $description.=$b.'='.$xml->shop->offers->offer[$i]->param[$j].'<br/>';
                    }
                }
                echo $description."<br/>";
                
                $this->con->query("UPDATE goods SET description='".mysqli_real_escape_string($this->con,$description)."' WHERE name LIKE '".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->name)."'");
                //echo $name;
                //return;
            }
        }
        function addGoodAdmin($xml)
        {
            set_time_limit(0);
            echo "<h1>".count($xml->shop->offers->offer)."</h1>";
            //return;
            for($i=count($xml->shop->offers->offer)-1;$i>0;$i--) {
                //if($i!=4113)
                    //continue;
                $flagIsset=false;
                $name=$xml->shop->offers->offer[$i]->name;
                $idgoods=0;
                //echo "<h1>".$name.count($xml->shop->offers->offer[$i]->param)."</h1>";
                
                if ($xml->shop->offers->offer[$i]->categoryId != 81230||strpos($name, "Пульсок")===false)
                {
                    //||strpos($name, "Лопат")===false
                    $this->con->set_charset("utf8");
                    //$xml->shop->offers->offer[$i]->categoryId !== 0
                    continue;
                }

                $price=$xml->shop->offers->offer[$i]->price;


                if(true)
                {
                    //echo "<h1>".$i." ".$name."</h1>";
                /*foreach($xml->shop->offers->offer[$i]->attributes() as $a => $b) {
                    if(strpos($a,"available")!==false&&strcmp("",$b)===0)
                    {
                        $flagIsset=true;
                        break;
                    }
                }
                if($flagIsset)
                    //continue;
                */

                $description=$xml->shop->offers->offer[$i]->description;
                if(strpos($description, "<b></b><b><b></b></b><b><b></b><b><b></b></b></b><b><b></b><b><b></b></b><b><b></b><b><b></b></b></b></b><b><b></b><b><b></b></b><b><b></b><b><b></b></b></b><b><b></b><b><b></b></b><b><b></b><b><b></b></b></b></b></b><b><b></b><b><b></b></b><b><b></b><b><b></b></b></b><b><b></b><b><b></b></b><b><b></b><b><b></b></b>")!==false)
                {/*strcmp("Комод для вещей 1 Эверест Дуб сонома", $name)===0||strcmp("Стол компьютерный СКМ-6 Компанит Венге Темный", $name)===0||strcmp("Отпариватель ручной Tobi Smoll w-26 Белый\синий (005510)", $name)===0*/
                    //continue;
                }
                $keyCheckCategory = array_search($xml->shop->offers->offer[$i]->categoryId, $this->firstCategoryIdArray);

                if($keyCheckCategory===false)
                {//||mb_stripos($name, "Вешалк", 0, 'UTF-8')===false
            // муж жен масло
            //||$xml->shop->offers->offer[$i]->categoryId!=4631263
            //$xml->shop->offers->offer[$i]->categoryId!=4631263||

                //echo "<h1>".$xml->shop->offers->offer[$i]->categoryId."</h1>";
                //return;
                    //continue;
                }
                else
                {
                    //continue;
                }


                /*if (strcmp($name, "Тестер женского парфюма Paco Rabanne Olympea edp 80ml (BT13623)") !== 0)
                {
                    $flaaag=true;
                }
                //0573b5a0-1d64-4a65-96a1-3664dd565077  
                for($j=0;$xml->shop->offers->offer[$i]->picture[$j];$j++) {
                    if($flaaag)
                    {
                        $url = $xml->shop->offers->offer[$i]->picture[$j];
                        $img = 'storage/goodsimg/2/'.explode("/", $url)[3];
                        echo $img."<br/>";
                        file_put_contents($img, file_get_contents($url));
                    }
                }
                continue;*/
                //echo "<h1>".$name."</h1>";
                $currency=1;
                $category=$this->secondSubCategoryArray[$keyCheckCategory];
                $subsubCategory=$this->thirdSubSubCategoryArray[$keyCheckCategory];

                $resGoods=$this->con->query("SELECT DISTINCT id FROM goods WHERE name LIKE '".mysqli_real_escape_string($this->con,$name)."'");

                    if($resGoods->num_rows>0)
                    {
                        continue;
                    }

                    if($idgoods!=0)
                        continue;
                //echo "<h1>".$i." ".$name."</h1>";
                //return "meow";xml->shop->offers->offer[$i]->vendor
                $this->con->query("INSERT INTO goods(vendor,id_user,name,searchName,description,id_subcategory,id_subsubcategory,currency) VALUES('".mysqli_real_escape_string($this->con, $xml->shop->offers->offer[$i]->vendor)."','2','".mysqli_real_escape_string($this->con, $name)."','".mysqli_real_escape_string($this->con, $name)."','".mysqli_real_escape_string($this->con, $description)."','".(int)$category."','$subsubCategory',".(int)$currency.")");
                $idgoods=$this->con->insert_id;

                //параметры
                $description="";
                            //$this->con->query("INSERT INTO InfoSetAndPropertyGoods(id_goods,price) VALUES('".(int)$idgoods."','".floatval($price)."')");
                            $idInfoSetAndpProperty=$this->con->insert_id;
                $arrayProp=array();
                $arraySet=array();
                if(count($xml->shop->offers->offer[$i]->param)>1)
                {
                for($j=0;$xml->shop->offers->offer[$i]->param[$j];$j++) {
                    $s=0;
                    foreach($xml->shop->offers->offer[$i]->param[$j]->attributes() as $a => $b) {
                        if(trim($b)==""||$s>0||strpos($b,"Артикул")!==false)
                            continue;
                        //echo $xml->shop->offers->offer[$i]->param[$j]."meow";
                        $s++;
                        $description.=$b.'='.$xml->shop->offers->offer[$i]->param[$j].'<br/>';
                        $resProperty=$this->con->query("SELECT DISTINCT id FROM property WHERE name LIKE '".mysqli_real_escape_string($this->con,$b)."'")->fetch_assoc();
                        if($resProperty)
                        {
                            $resProperty=$resProperty['id'];
                        }
                        else
                        {
                            $this->con->query("INSERT INTO property(name,type) VALUES('".mysqli_real_escape_string($this->con,$b)."',0)");
                            $resProperty=$this->con->insert_id;
                        }
                        //echo "<h1>".$resProperty."</h1>";

                        $resSetting=$this->con->query("SELECT DISTINCT id FROM settings WHERE name LIKE '".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->param[$j])."'")->fetch_assoc();
                        if($resSetting)
                        {
                            $resSetting=$resSetting['id'];
                        }
                        else
                        {
                            $this->con->query("INSERT INTO settings(name) VALUES('".mysqli_real_escape_string($this->con,$xml->shop->offers->offer[$i]->param[$j])."')");
                            $resSetting=$this->con->insert_id;
                        }
                        //echo "<h3>".$resSetting."</h3>";
                        $resPropertiesSetting=$this->con->query("SELECT DISTINCT id FROM propertiessettings WHERE id_subsubcategory ='".(int)$subsubCategory."' AND id_property='".$resProperty."' AND id_settings='".$resSetting."'")->fetch_assoc();
                        if($resPropertiesSetting)
                        {
                            $resPropertiesSetting=$resPropertiesSetting['id'];
                        }
                        else
                        {
                            $this->con->query("INSERT INTO propertiessettings(id_subsubcategory,id_property,id_settings,hiddenSet) VALUES('".(int)$subsubCategory."','".$resProperty."','".$resSetting."',1)");
                            $resPropertiesSetting=$this->con->insert_id;
                        }
                        //echo "<h3>".$resPropertiesSetting."</h3>";
                        $resPropertyCategories=$this->con->query("SELECT DISTINCT id FROM propertycategories WHERE id_subsubcategories='".(int)$subsubCategory."' AND id_property='".$resProperty."'")->fetch_assoc();
                        if($resPropertyCategories)
                        {
                            $resPropertyCategories=$resPropertyCategories['id'];
                        }
                        else
                        {
                            $this->con->query("INSERT INTO propertycategories(id_subsubcategories,id_property,hidden) VALUES('".(int)$subsubCategory."','".$resProperty."','1')");
                            $resPropertyCategories=$this->con->insert_id;
                        }
                        //echo "<h3>".$resPropertyCategories."</h3>";
                            //if($j>10)
                                //return;
                                array_push($arrayProp, $resProperty);
                                array_push($arraySet, $resSetting);
                    }
                    //echo "hihi".$idgoods;
                }
                }//params>1
                else
                {
                    $this->con->query("UPDATE goods SET price='".floatval($price)."' WHERE id='".(int)$idgoods."'");
                }


                for($z=0;$z<count($arrayProp);$z++)
                {
                    for($z2=0;$z2<count($arrayProp);$z2++)
                    {
                        if($arrayProp[$z]<$arrayProp[$z2])
                        {
                            $temp=$arrayProp[$z];
                            $arrayProp[$z]=$arrayProp[$z2];
                            $arrayProp[$z2]=$temp;

                            $temp2=$arraySet[$z];
                            $arraySet[$z]=$arraySet[$z2];
                            $arraySet[$z2]=$temp2;
                        }
                    }
                }
                $stringRes="";
                $lastProp=0;
                for($z=0;$z<count($arrayProp);$z++)
                {
                    if($lastProp!=$arrayProp[$z]&&$z!=0)
                    {
                        $lastProp=$arrayProp[$z];
                        $stringRes.="]";
                    }
                    else if($z==0)
                        $lastProp=$arrayProp[$z];
                    $stringRes.=$arrayProp[$z]."_".$arraySet[$z].",";
                }
                $stringRes.="]";        
                $VariantsInput=$this->returnPropResultVariants($stringRes);
                $VariantsInput=explode("}",$VariantsInput);
                $VariantsInput=array_unique($VariantsInput);
                //echo "<h1>".implode(" ",$VariantsInput)."</h1>";
                //return;
                            //$VariantsPrices=explode("}",$VariantsPrices);
                            for($z=0;$z<count($VariantsInput)-1;$z++)
                            {
                                if($VariantsInput[$z]==null)
                                    continue;
                                $this->con->query("INSERT INTO InfoSetAndPropertyGoods(id_goods,price) VALUES('".(int)$idgoods."','".floatval($price)."')");
                                $res=$this->con->query("SELECT *FROM InfoSetAndPropertyGoods WHERE id_goods='".(int)$idgoods."' ORDER BY id DESC LIMIT 1");
                                $rowInfoSetAndProp=$res->fetch_assoc();
                                $idInfoSetAndpProperty=$rowInfoSetAndProp['id'];
                                //echo $VariantsInput[$z]."()";
                                $CurVariants=explode(",",$VariantsInput[$z]);
                                //echo "<br/>";
                                for($j1=0;$j1<count($CurVariants)-1;$j1++)
                                {
                                    $SplitCurVariants=explode("_",$CurVariants[$j1]);
                                    $CurProperty=$SplitCurVariants[0];
                                    $CurSetting=$SplitCurVariants[1];
                                    $this->con->query("INSERT INTO InfoSetAndProperty(id_infoSetAndPropertyGoods,id_property,id_settings) VALUES('".(int)$idInfoSetAndpProperty."','".(int)$CurProperty."','".(int)$CurSetting."')");
                                }
                            }
                /*for($z=0;$z<count($arrayProp);$z++)
                {
                    $this->con->query("INSERT INTO InfoSetAndPropertyGoods(id_goods,price) VALUES('".(int)$idgoods."','".floatval($price)."')");
                    $idInfoSetAndpPropertyLast=$this->con->insert_id;
                    $resInfoSet=$this->con->query("SELECT* FROM InfoSetAndProperty WHERE id_infoSetAndPropertyGoods='".(int)$idInfoSetAndpProperty."'");
                    while($rowInfoSet=$resInfoSet->fetch_assoc())
                    {
                        if($rowInfoSet['id_property']==$arrayProp[$z])
                        {
                            $this->con->query("INSERT INTO InfoSetAndProperty(id_infoSetAndPropertyGoods,id_property,id_settings) VALUES('".(int)$idInfoSetAndpPropertyLast."','".(int)$arrayProp[$z]."','".(int)$arraySet[$z]."')");
                        }
                        else
                        {
                            $this->con->query("INSERT INTO InfoSetAndProperty(id_infoSetAndPropertyGoods,id_property,id_settings) VALUES('".(int)$idInfoSetAndpPropertyLast."','".(int)$rowInfoSet['id_property']."','".(int)$rowInfoSet['id_settings']."')");
                        }

                    }
                }*/
                //echo "<h1>".$idInfoSetAndpProperty."</h1>";
                // /var_dump($arrayProp);
                //var_dump($arraySet);
                //here
                $this->con->query("UPDATE goods SET description='".mysqli_real_escape_string($this->con, $description)."' WHERE id='".$idgoods."'");
                $this->con->query("INSERT INTO goodsDeliverCountry(id_country,id_goods,minCount,price,DeliveryDays) VALUES('1','".$idgoods."','1','0','3')");
                //все фото
                for($j=0;$xml->shop->offers->offer[$i]->picture[$j];$j++) {
                    $url = $xml->shop->offers->offer[$i]->picture[$j];
                    $img = 'storage/goodsimg/2/'.explode("/", $url)[3];
                    file_put_contents('storage/goodsimg/2/'.explode("/", $url)[3], file_get_contents($url));
                    if($j==0) {
                     $this->con->query("INSERT INTO goodsimg(id_goods,image,is_main) VALUES($idgoods,'$img','1')");
                    } else {
                     $this->con->query("INSERT INTO goodsimg(id_goods,image,is_main) VALUES($idgoods,'$img','0')");
                    }
                }
                //return;
            }
            }
        }
        function returnPropResultVariants($strVariants)
        {
            $str = $strVariants; 
            $arr = explode("]", trim($str," ]\t\n\r")); 
            $cnt = 1; 
            $result='';
            if(count($arr)<2)
            {
                $arr=explode(",", trim($arr[0]," ,\t\n\r"));
                    for($i=0;$i<count($arr);$i++)
                    {
                        $result.=$arr[$i].",}";
                    }
                    if($result==",}")
                    {
                        $result='';
                    }
            }
            else
            {
                for ($i=0;$i < count($arr); $i++) 
                { 
                    $arr[$i] = explode(",", trim($arr[$i]," ,\t\n\r")); 
                    $cnt *= count($arr[$i]); 
                } 
                $result = ''; 
                $end = $pre = false; 
                while (!$end) 
                { 
                    reset($arr); 
                    while (($item = current($arr))) 
                    { 
                        $result .= current($item) . ','; 
                        next($arr); 
                    } 
                    $result .= '}'; 
                    end($arr); 
                    do 
                    { 
                        $key = (int) key($arr); 
                        if ($pre) 
                        { 
                            if (next($arr[$key])) 
                            { 
                                $pre = false; 
                            } 
                            else 
                            { 
                                if ($key == 0) $end = true; 
                                else 
                                { 
                                    reset($arr[$key]); $pre = true; 
                                } 
                            } 
                        } 
                        elseif ($key == count($arr) - 1) 
                        { 
                            if (!next($arr[$key])) 
                            { 
                                reset($arr[$key]); 
                                $pre = true; 
                            } 
                        } 
                    } while (prev($arr)); 
                }
            }
            return $result;
        }
        function checkAdmin($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(strlen($login)>0)
            {
                $res=$this->con->query("SELECT *FROM administration WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".crypt($password,"f54a009ge43mmkvabikr")."'");
                if($row=$res->fetch_assoc())
                {
                    return $row;
                }   
            }
            return false;
        }
        function checkWithCryptPassword($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            if(strlen($login)>0)
            {
                $res=$this->con->query("SELECT *FROM administration WHERE login='".mysqli_real_escape_string($this->con, $login)."' AND password='".mysqli_real_escape_string($this->con,$password)."'");
                if($row=$res->fetch_assoc())
                {
                    return $row;
                }   
            }
            return false;
        }
        function setAdminUsersComplaints($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Admin=$this->checkWithCryptPassword($login,$password,$token);
            if($Admin)
            {
                if(!$this->getAdminUsersComplaints($login,$password,$token))
                {
                    $res=$this->con->query("SELECT DISTINCT id FROM usersComplaints WHERE id_admin='0' AND action='0' LIMIT 1");
                    if($row=$res->fetch_assoc())
                    {
                        $this->con->query("UPDATE usersComplaints SET id_admin='".$Admin['id']."' WHERE id='".$row['id']."'");
                    }
                }
            }
            return "admin_error";
        }
        function getAdminUsersComplaints($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Admin=$this->checkWithCryptPassword($login,$password,$token);
            if($Admin)
            {
                $res=$this->con->query("SELECT DISTINCT * FROM usersComplaints WHERE id_admin='".$Admin['id']."' AND action='0' LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    return $row;
                }
            }
            return false;
        }
        /*
            action 0 - в процессе
            action 1 - жалоба принята(забанен)
            action 2 - жалоба отклонена
        */ 
        function acceptComplaint($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Admin=$this->checkWithCryptPassword($login,$password,$token);
            if($Admin)
            {
                $res=$this->con->query("SELECT DISTINCT * FROM usersComplaints WHERE id_admin='".$Admin['id']."' AND action='0' LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    $this->con->query("UPDATE usersComplaints SET action='1' WHERE id='".$row['id']."'");
                    $this->con->query("UPDATE users SET baned='1' WHERE id='".$row['user_to']."'");
                    return true;
                }
            }
            return false;
        }
        function notacceptComplaint($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Admin=$this->checkWithCryptPassword($login,$password,$token);
            if($Admin)
            {
                $res=$this->con->query("SELECT DISTINCT * FROM usersComplaints WHERE id_admin='".$Admin['id']."' AND action='0' LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    $this->con->query("UPDATE usersComplaints SET action='2' WHERE id='".$row['id']."'");
                    return true;
                }
            }
            return false;
        }
        function getGoodsAccept($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Admin=$this->checkWithCryptPassword($login,$password,$token);
            if($Admin)
            {
                $res=$this->con->query("SELECT id FROM goods WHERE action='0' AND id_admin='".$Admin['id']."' AND is_deleted='0' ORDER BY id LIMIT 1");
                $row=$res->fetch_assoc();
                return $row;
            }
            return false;
        }
        function setAdminGoodsAccept($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Admin=$this->checkWithCryptPassword($login,$password,$token);
            if($Admin)
            {
                if(!$this->getGoodsAccept($login,$password,$token))
                {
                    $res=$this->con->query("SELECT id FROM goods WHERE action='0' AND id_admin='0' AND is_deleted='0' ORDER BY id LIMIT 1");
                    if($row=$res->fetch_assoc())
                    {
                        $this->con->query("UPDATE goods SET id_admin='".$Admin['id']."' WHERE id='".$row['id']."'");
                        return true;
                    }
                }
            }
            return false;
        }
        function acceptGood($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Admin=$this->checkWithCryptPassword($login,$password,$token);
            if($Admin)
            {
                $res=$this->con->query("SELECT DISTINCT id FROM goods WHERE action='0' AND id_admin='".$Admin['id']."' AND is_deleted='0' ORDER BY id LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    $this->con->query("UPDATE goods SET action='1' AND id_admin='".$Admin['id']."'");
                    return true;
                }
            }
            return false;
        }
        function notacceptGood($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $Admin=$this->checkWithCryptPassword($login,$password,$token);
            if($Admin)
            {
                $res=$this->con->query("SELECT DISTINCT id FROM goods WHERE action='0' AND id_admin='".$Admin['id']."' ORDER BY id LIMIT 1");
                if($row=$res->fetch_assoc())
                {
                    $this->con->query("UPDATE goods SET is_deleted='1' AND id_admin='".$Admin['id']."'");
                    return true;
                }
            }
            return false;
        }
        function banUser($login,$password,$idUser,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $issetUserRow=$this->con->query("SELECT DISTINCT id FROM users WHERE id='".(int)$idUser."' LIMIT 1")->fetch_assoc();
            if($issetUserRow)
            {
                $Admin=$this->checkWithCryptPassword($login,$password,$token);
                if($Admin)
                {
                    $this->con->query("INSERT INTO usersComplaints(id_admin,user_to,action) VALUES('".$Admin['id']."','".(int)$idUser."','1')");
                    $this->con->query("UPDATE users SET baned='1' WHERE id='".(int)$idUser."'");
                    return true;
                }
            }
            return false;
        }
        function banGood($login,$password,$idGood,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $issetGoodsRow=$this->con->query("SELECT DISTINCT id FROM goods WHERE id='".(int)$idGood."' AND is_deleted='0' AND id_admin='0' LIMIT 1")->fetch_assoc();
            if($issetGoodsRow)
            {
                $Admin=$this->checkWithCryptPassword($login,$password,$token);
                if($Admin)
                {
                    $this->con->query("UPDATE goods SET is_deleted='1',id_admin='".$Admin['id']."' WHERE id='".(int)$idGood."'");
                    return true;
                }
            }
            return false;
        }
    }
?>