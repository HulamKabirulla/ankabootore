<?php
/**
 * @author Gulam Kabirulla
 * @copyright 2016
 */
?>
<?php
    session_start();
    include_once("classes/CCheck.php");
    class Friends{
        use Check;
        private $con;
        function __construct($con) {
            $this->con=$con;
        }
    }
?>