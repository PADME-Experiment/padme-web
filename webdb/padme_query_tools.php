<?php
function get_run_number($name) {
  $query = "SELECT number FROM run WHERE name='$name'";
  $result = mysql_query($query) or die('Query failed: ' . mysql_error());
  if (mysql_num_rows($result) == 0) {
    return -1;
  } else {
    $line = mysql_fetch_array($result, MYSQL_ASSOC);
    return $line["number"];
  }
}
?>
