<?php

# Get DB connection info from local file
foreach( explode("\n",file_get_contents(".padme-dbaccess.sh")) as $data ) {
  if ( preg_match("/export\s+(\S+)=(\S+)/",$data,$matches) == 1 )
    $dbaccess[$matches[1]] = $matches[2];
}

# Connect to PADME DAQ DB on LNF MySQL database
$link_mcdb = mysql_connect($dbaccess["PADME_DB_HOST"].":".$dbaccess["PADME_DB_PORT"],$dbaccess["PADME_DB_USER"],$dbaccess["PADME_DB_PASSWD"]) or die('Could not connect: '.mysql_error());
mysql_select_db($dbaccess["PADME_DB_NAME"]) or die('Could not select database');

?>
