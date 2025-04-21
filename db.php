<?php

// Create connection
$conn = mysqli_connect('mysql', 'root', 'root', 'watch_store');

// Check connection
if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}
?>
