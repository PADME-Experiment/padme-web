<?php

# Get DB connection info from local file
foreach( explode("\n",file_get_contents(".padme-dbaccess.sh")) as $data ) {
  if ( preg_match("/export\s+(\S+)=(\S+)/",$data,$matches) == 1 )
    $dbaccess[$matches[1]] = $matches[2];
}

# Connect to PADME offline DB on LNF MySQL database
$link_mcdb = mysql_connect($dbaccess["PADME_MCDB_HOST"].":".$dbaccess["PADME_MCDB_PORT"],$dbaccess["PADME_MCDB_USER"],$dbaccess["PADME_MCDB_PASSWD"]) or die('Could not connect: ' . mysql_error());
mysql_select_db($dbaccess["PADME_MCDB_NAME"]) or die('Could not select database');

?>
