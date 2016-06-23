<?php
//$con=mysqli_connect("clinicabrinckmann.com.br","clinicab_webmail","clinicab_webmail","clinicab_webmail");
$con=mysqli_connect("192.185.215.181","clinicab_webmail","clinicab_webmail","clinicab_webmail");


// Check connection
if (mysqli_connect_errno($con))
  {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  }

mysqli_close($con);
?> 
