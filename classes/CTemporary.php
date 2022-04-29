<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    session_start();
    class Temporary{
        private $con;
        function __construct($con) {
            $this->con=$con;
        }
        public function CountTemporaryGoods($login,$password,$token)
        {
            if($token!=$_SESSION['token'])
            {
                return "csrf_error";
            }
            $count=0;
            if($UserGet=$this->getUser($login,$password))
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
    }
?>