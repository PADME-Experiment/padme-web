<?php

# Get DB connection info from local file
foreach ( explode("\n",file_get_contents(".padme-dbaccess.sh")) as $data ) {
    if ( preg_match("/^\s*#/",$data) == 1 ) continue;
    if ( preg_match("/export\s+(\S+)=(\S+)/",$data,$matches) == 1 )
        $dbaccess[$matches[1]] = $matches[2];
}

# Connect to PADME DAQ DB on l0padme1
$mysqli = mysqli_connect($dbaccess["PADME_DB_HOST"],$dbaccess["PADME_DB_USER"],$dbaccess["PADME_DB_PASSWD"],$dbaccess["PADME_DB_NAME"],$dbaccess["PADME_DB_PORT"]) or die('Could not connect: '.mysqli_connect_error());

?>
