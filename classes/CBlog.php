<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */

?>
<?php
    include_once("classes/CString.php");
    require_once("classes/CDatabase.php");
    class Blog{
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
        function getBlogReviews($idPost,$limit)
        {
            $countReviews=3;
            $limit = $limit>0 ? $limit-1 : 0;
            if($limit>0)
            {
                $limit=$limit*($countReviews);
            }
            $res=$this->con->query("SELECT id,name,id_post,text,rating,created_at FROM reviewsPost WHERE isVisible=1 AND id_post='".(int)$idPost."' ORDER BY id DESC LIMIT ".$limit.",".$countReviews."");
            $resRating=$this->con->query("SELECT CEIL(SUM(rating)/COUNT(id)) AS grRes FROM reviewsPost WHERE isVisible=1 AND id_post='".(int)$idPost."' GROUP BY id_post ORDER BY id DESC LIMIT ".$limit.",".$countReviews."")->fetch_assoc()['grRes'];
            $resCount=$this->con->query("SELECT id FROM reviewsPost WHERE isVisible=1 AND id_post='".(int)$idPost."'");
            
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
        function getBlog($limit,$language="ru")
        {
            $limit = $limit>0 ? $limit-1 : 0;
            $countRes=$this->countRes;
            if($limit>0)
            {
                $limit=$limit*($countRes);
            }
            $res=$this->con->query("SELECT CEIL(SUM(reviewsPost.rating)/COUNT(reviewsPost.id)) AS grRes,blog.id,blog.name,blog.text,blog.imageTitle,blog.created_at FROM blog LEFT JOIN reviewsPost ON reviewsPost.id_post=blog.id WHERE blog.isVisible=1 AND reviewsPost.isVisible=1 GROUP BY blog.id ORDER BY blog.id DESC LIMIT ".$limit.",".$countRes."");
            $resCount=$this->con->query("SELECT id,name,imageTitle,created_at FROM blog WHERE isVisible=1");
            for($i=0;$row=$res->fetch_assoc();$i++)
            {
                $massive[$i]=$row;
            }
            $countRows=$resCount->num_rows;
            $massive['countRows']=$countRows;
            
            return $massive;
        }
        function AddReviewToPost($idPost,$nameUser,$emailUser,$text,$rating,$token)
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
            $this->con->query("INSERT INTO reviewsPost(name,email,id_post,text,rating,isVisible) VALUES('".mysqli_real_escape_string($this->con,$nameUser)."','".mysqli_real_escape_string($this->con,$emailUser)."','".mysqli_real_escape_string($this->con,$idPost)."','".mysqli_real_escape_string($this->con,$text)."','".mysqli_real_escape_string($this->con,$rating)."','0')");
            return true;
        }
        function getBlogById($id,$language="ru")
        {
            $res=$this->con->query("SELECT id,titleSeo,descriptionSeo,name,text,imageTitle,created_at FROM blog WHERE isVisible=1 AND id='".(int)$id."'");
            
            $massive=$res->fetch_assoc();
            $massive['reviews']=$this->getBlogReviews($id,0);
            //var_dump($massive['reviews'][0]);
            return $massive;
        }
    }
?>