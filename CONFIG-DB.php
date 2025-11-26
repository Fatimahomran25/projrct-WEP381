<?php
    define("DBHOST", "localhost");
    define("DBUSER", "root");
    define("DBPWD", "");
    define("DBNAME", "faculty_load_db");


$conn = new mysqli(DBHOST , DBUSER , DBPWD , DBNAME);
if($conn-> connect_error){


die("Connection failed : " . $conn->connect_error);

}

?>
