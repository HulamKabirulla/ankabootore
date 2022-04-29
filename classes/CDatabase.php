<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    class Database{
        private $con;
        function __construct($con) {
            $this->con=$con;
        }
        function redirect301($curUrl)
        {
            $rowRes=$this->con->query("SELECT* FROM dublicates WHERE url='".mysqli_real_escape_string($this->con, $curUrl)."' LIMIT 1")->fetch_assoc();
            return $rowRes;
        }
        function getSeoText($url,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
            $res=$this->con->query("SELECT title".$language.",description".$language.",h1".$language.",seoText".$language." FROM seoText WHERE url='".mysqli_real_escape_string($this->con, $url)."' LIMIT 1");
            return $res->fetch_assoc();
        }
        function getAllReviewsCount()
        {
            $resCount=$this->con->query("SELECT id FROM reviews WHERE isVisible=1");
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            return $massive;
        }
        function getAllReviews($limit)
        {
            $countReviews=3;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countReviews);
            }
            $res=$this->con->query("SELECT name,id_goods,text,rating,created_at FROM reviews WHERE isVisible=1 ORDER BY id DESC LIMIT ".$limit.",".$countReviews."");
            $resRating=$this->con->query("SELECT CEIL(SUM(rating)/COUNT(id)) AS grRes FROM reviews WHERE isVisible=1 GROUP BY id_goods ORDER BY id DESC LIMIT ".$limit.",".$countReviews."")->fetch_assoc()['grRes'];
            
            $resCount=$this->con->query("SELECT id FROM reviews WHERE isVisible=1");
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
        function getSubSubCategoryByIdSubCategory($idSubSubCategory,$idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
            $idUser=(int)$idUser;
            $stringSubSubCategory="";
            if($idUser>0) {
                $stringSubSubCategory=" id IN(SELECT id_subsubcategory FROM goods WHERE goods.id_user='".$idUser."') AND";
            }
            if((int)$idSubSubCategory>0)
            {
                $massive[]=null;
                $res=$this->con->query("SELECT id,id_subcategory,name".$language." AS name FROM subsubcategories WHERE".$stringSubSubCategory." id_subcategory='".(int)$idSubSubCategory."' AND action='1' ORDER BY name");
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
                return $massive;
            }
            return false;
        }
        function getSubCategoryByIdCategory($id,$idUser=0,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
            $idUser=(int)$idUser;
            $stringCategory="";
            if($idUser>0) {
                $stringCategory=" id IN(SELECT id_subcategory FROM goods WHERE goods.id_user='".$idUser."') AND";
            }
            if((int)$id>0)
            {
                $res=$this->con->query("SELECT id,id_category,subcategory".$language." AS subcategory FROM subcategories WHERE".$stringCategory." id_category='".(int)$id."' AND action='1' ORDER BY subcategory");
                $massive[]=null;
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
                return $massive;
            }  
            return false;
        }
        function getAllCategory($idUser,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
              
            $idUser=(int)$idUser;
            $massive[]=null;
            if($idUser==0) {
                $res=$this->con->query("SELECT id,category".$language." AS category FROM categories WHERE action='1'");
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
            } else {
                $res=$this->con->query("SELECT id,category".$language." AS category FROM categories WHERE action='1' AND id IN (SELECT id_category FROM subcategories WHERE id IN(SELECT id_subcategory FROM goods WHERE id_user='".(int)$idUser."' AND is_deleted!='1'))");
                $massive[]=null;
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
            }
            return $massive;
        }
        function getCategory($id,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
            if((int)$id>0)
            {
                $res=$this->con->query("SELECT id,action,category".$language." AS category,title".$language." AS title,description".$language." AS description FROM categories WHERE id ='".(int)$id."'");
                if($row=$res->fetch_assoc())
                {
                    return $row;
                }
            }  
            return false;
        }
        function getIdCategoryOfSubCategory($idSubCategory)
        {
            if((int)$idSubCategory==0)
            {
                return null;
            }
            $res=$this->con->query("SELECT id FROM categories WHERE id = (SELECT id_category FROM subcategories WHERE id='".(int)$idSubCategory."')");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }

        function getAllSubCategory()
        {
                $res=$this->con->query("SELECT* FROM subcategories WHERE action='1'");
                $massive[]=null;
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
                return $massive;
        }
        function getAllSubSubCategory()
        {
                $res=$this->con->query("SELECT* FROM subsubcategories WHERE action='1'");
                $massive[]=null;
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
                return $massive;
        }
        function getSubCategory($id,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
            if((int)$id>0)
            {
                $res=$this->con->query("SELECT subcategories.seoText AS seoText,categories.id AS cId,subcategories.id,subcategories.action,categories.category".$language." AS category,subcategories.subcategory".$language." AS subcategory,subcategories.title".$language." AS title,subcategories.description".$language." AS description FROM subcategories LEFT JOIN categories ON categories.id=subcategories.id_category WHERE subcategories.id='".(int)$id."'");
                if($row=$res->fetch_assoc())
                {
                    return $row;
                }
            }  
            return false;
        }
        function getSubSubCategory($id,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
            if((int)$id>0)
            {
                $res=$this->con->query("SELECT id,id_subcategory,name".$language." AS name FROM subsubcategories WHERE id='".(int)$id."'");
                if($row=$res->fetch_assoc())
                {
                    return $row;
                }
            }  
            return false;
        }
        function getPropertyCategoriesBySubSubCategory($idSubSubCategory,$idProperty)
        {
            $res=$this->con->query("SELECT* FROM propertycategories WHERE id_subsubcategories='".(int)$idSubSubCategory."' AND id_property='".(int)$idProperty."'");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }
        function getPropertyHidden($idSubSubCategory,$idProperty)
        {
            $res=$this->con->query("SELECT DISTINCT hidden FROM propertycategories WHERE id_subsubcategories='".(int)$idSubSubCategory."' AND id_property='".(int)$idProperty."' LIMIT 1");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }
        function getSeoTextSubSubCategory($idSubSubCategory,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
            $res=$this->con->query("SELECT subsubcategories.seoText AS seoText,subsubcategories.title".$language." AS title,subsubcategories.description".$language." AS description FROM subsubcategories WHERE subsubcategories.id='".(int)$idSubSubCategory."' LIMIT 1");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }
        function getSubSubCategoriesProperiesAndSettings($idSubSubCategory,$limit,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
            $countRes=100;
            $limit = $limit>0 ? $limit : 0;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $res=$this->con->query("SELECT property.type AS pType,property.id AS pId, settings.id AS sId,settings.name".$language." AS sName,property.name".$language." AS pName FROM propertycategories LEFT JOIN propertiessettings ON propertiessettings.id_subsubcategory=propertycategories.id_subsubcategories LEFT JOIN property ON property.id=propertiessettings.id_property LEFT JOIN settings ON propertiessettings.id_settings=settings.id WHERE propertycategories.id_subsubcategories='".(int)$idSubSubCategory."' GROUP BY settings.id ORDER BY property.id,settings.name");
            //LIMIT ".$limit.",".$countRes."
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }
        function getSubSubCategoriesInfo($idSubSubCategory,$language="ru")
        {
            if($language=="ru") {
                $language="";
            } else {
                 $language="_".$language;
            }
            $res=$this->con->query("SELECT categories.id AS cId,categories.category".$language." AS category,subcategories.subcategory".$language." AS subName,subsubcategories.id_subcategory AS idSubCategory, subsubcategories.name".$language." AS sscName FROM subsubcategories LEFT JOIN subcategories ON subcategories.id=subsubcategories.id_subcategory LEFT JOIN categories ON categories.id=subcategories.id_category WHERE subsubcategories.id='".(int)$idSubSubCategory."'");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }
        function getSettingByIdPropertyAndIdSubSubCategory($idProperty,$idSubSubCategory)
        {
            if((int)$idProperty>0&&(int)$idSubSubCategory>0)
            {
                $massive[]=null;
                $res=$this->con->query("SELECT* FROM settings WHERE id IN(SELECT id_settings FROM propertiessettings WHERE id_property='".(int)$idProperty."' AND id_subsubcategory='".(int)$idSubSubCategory."')");
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
                return $massive;
            }
            return false;
        }
        function getPropertyByIdSubSubCategory($idSubSubCategory,$language="ru")
        {
            if((int)$idSubSubCategory>0)
            {
                if($language=="ru") {
                    $language="";
                } else {
                     $language="_".$language;
                }
                $massive[]=null;
                $res=$this->con->query("SELECT id,name".$language." AS name,type FROM property WHERE id IN(SELECT id_property FROM propertycategories WHERE id_subsubcategories='".(int)$idSubSubCategory."')");
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
                return $massive;
            }
            return false;
        }
        function getCountryByName($name,$limit)
        {
            if((int)$limit>0)
            {
                $res=$this->con->query("SELECT* FROM country_ WHERE country_name_en LIKE '%".mysqli_real_escape_string($this->con, $name)."%' LIMIT ".(int)$limit."");
                $massive[]=null;
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
                return $massive;
            }
            return false;
        }
        function getCountryById($id)
        {
            if((int)$id>0)
            {
                $res=$this->con->query("SELECT DISTINCT* FROM country_ WHERE id ='".(int)$id."' LIMIT 1");
                $massive[]=null;
                for($i=0;$row=$res->fetch_assoc();$i++)
                {
                    $massive[$i]=$row;
                }
                return $massive;
            }
            return false;
        }
        function getAllCountry()
        {
            $res=$this->con->query("SELECT* FROM country_ WHERE action='1'");
            $massive[]=null;
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            return $massive;
        }
        function getSettingsById($idSetting,$language="ru")
        {
            if($language=="ru") {
                    $language="";
                } else {
                     $language="_".$language;
                }
            $res=$this->con->query("SELECT id,name".$language." AS name FROM settings WHERE id='".(int)$idSetting."'");
            $row=$res->fetch_assoc();
            return $row;
        }
        function getPropertyById($idProperty,$language="ru")
        {
            if($language=="ru") {
                    $language="";
                } else {
                     $language="_".$language;
                }
            $res=$this->con->query("SELECT id,name".$language." AS name,type FROM property WHERE id='".(int)$idProperty."'");
            $row=$res->fetch_assoc();
            return $row;
        }
    }
?>
