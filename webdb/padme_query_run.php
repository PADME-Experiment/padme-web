<?php
require 'padme_query_header.php';
require DAQDB_CONNECT_SCRIPT;

// Get run name
if (isset($_GET['name'])) {
    $qname = htmlspecialchars($_GET['name']);
} else {
    $qname = "";
}

// Get year
if (isset($_GET['year'])) {
    $qyear = htmlspecialchars($_GET['year']);
} else {
    $qyear = date("Y");
}

// Get type
if (isset($_GET['type'])) {
    $qtype = htmlspecialchars($_GET['type']);
} else {
    $qtype = "ALL";
}

// Get status
if (isset($_GET['status'])) {
    $qstatus = htmlspecialchars($_GET['status']);
} else {
    $qstatus = "ALL";
}

echo "<form name=DBQueryTool action=\"",RUN_SCRIPT,"\" method=GET>\n";
echo "\t<fieldset>\n";
echo "\t\t<legend>Run selection</legend>\n";

# Select year
echo "\t\t<label>Year: </label>\n";
echo "\t\t<select name=year>\n";
if ( $qyear == "ALL" ) {
    echo "\t\t\t<option value=ALL selected=selected>ALL</option>\n";
} else {
    echo "\t\t\t<option value=ALL>ALL</option>\n";
}
for ($y=2018;$y<=(int)date("Y");$y++) {
    if ( $y == (int)$qyear ) {
        echo "\t\t\t<option value=",$y," selected=selected>",$y,"</option>\n";
    } else {
        echo "\t\t\t<option value=",$y,">",$y,"</option>\n";
    }
}
echo "\t\t</select>\n";

# Select type
echo "\t\t<label>Type: </label>\n";
echo "\t\t<select name=type>\n";
if ( $qtype == "ALL" ) {
    echo "\t\t\t<option value=ALL selected=selected>ALL</option>\n";
} else {
    echo "\t\t\t<option value=ALL>ALL</option>\n";
}
$query = "SELECT type FROM run_type";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $type = $line["type"];
    if ( $type == $qtype ) {
        echo "\t\t\t<option value=",$type," selected=selected>",$type,"</option>\n";
    } else {
        echo "\t\t\t<option value=",$type,">",$type,"</option>\n";
    }
}
echo "\t\t</select>\n";

# Select status
echo "\t\t<label>Status: </label>\n";
echo "\t\t<select name=status>\n";
if ( $qstatus == "ALL" ) {
    echo "\t\t\t<option value=ALL selected=selected>ALL</option>\n";
} else {
    echo "\t\t\t<option value=ALL>ALL</option>\n";
}
$query = "SELECT status FROM run_status";
$result = mysql_query($query) or die('Query failed: ' . mysql_error());
while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
    $status = $line["status"];
    if ( $status == $qstatus ) {
        echo "\t\t\t<option value=",$status," selected=selected>",$status,"</option>\n";
    } else {
        echo "\t\t\t<option value=",$status,">",$status,"</option>\n";
    }
}
echo "\t\t</select>\n";

echo "\t\t<input type=submit value=Submit>\n";
echo "\t</fieldset>";
echo "</form>\n";

if ( $qname == "" ) {
    show_all_runs($qyear,$qtype,$qstatus);
} else {
    show_run($qname);
}
?>

</BODY>
</HTML>

<?php
function show_all_runs($year,$type,$status) {

    echo "<h2>PADME DAQ Runs for year $year</h2>\n";

    $clause_list = array();
    if ( $year != "ALL" ) {
        $year_start = "$year-01-01 00:00:00";
        $year_end = "$year-12-31 23:59:59";
        $clause_list[] = "r.time_create >= \"$year_start\" AND r.time_create <= \"$year_end\"";
    }
    if ( $type != "ALL" ) {
        $clause_list[] = "rt.type = \"$type\"";
    }
    if ( $status != "ALL" ) {
        $clause_list[] = "rs.status = \"$status\"";
    }
    $where_clause = "";
    for ($c=0; $c<sizeof($clause_list); $c++) {
        if ($c == 0) {
            $where_clause = "WHERE ".$clause_list[$c];
        } else {
            $where_clause .= " AND ".$clause_list[$c];
        }
    }
    #echo "<pre>$where_clause</pre>\n";

    // Get list of existing Runs
    $query = "
SELECT r.name         AS name,
       rt.type        AS type,
       rs.status      AS status,
       r.time_create  AS time_create,
       r.time_stop    AS time_stop,
       r.total_events AS total_events
FROM         run        r
  INNER JOIN run_type   rt ON r.run_type_id=rt.id
  INNER JOIN run_status rs ON r.status=rs.id
$where_clause
ORDER BY r.time_create
";
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());

    // Printing results in HTML
    echo "<table cellpadding=3>\n";
    echo "\t<tr><th>Run name</th><th>Type</th><th>Status</th><th>Events</th><th>Created</th><th>Ended</th></tr>\n";
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $name = $line["name"];
        $status = $line["status"];
        $type = $line["type"];
        $time_create = $line["time_create"];
        $time_stop = $line["time_stop"];
        $total_events = $line["total_events"];
        echo "\t<tr>\n";
        echo "\t\t<td><a href=\"",RUN_SCRIPT,"?name=$name\">$name</a></td>\n";
        echo "\t\t<td align=center>$type</a></td>\n";
        echo "\t\t<td align=center>$status</a></td>\n";
        echo "\t\t<td align=right>$total_events</a></td>\n";
        echo "\t\t<td>$time_create</a></td>\n";
        echo "\t\t<td>$time_stop</a></td>\n";
        echo "\t</tr>\n";
    }
    echo "</table>\n";

    // Free resultset
    mysql_free_result($result);

}
?>

<?php
function show_run($run_name) {

    // **************** Run page ******************* //

    echo "<h2>Run ",$run_name,"</h2>\n";

    // Get run number from run name
    $run_number = get_run_number($run_name);
    if ($run_number < 0) {
        echo "ERROR: run ",$run_name," does not exist in the database<br>\n";
        //echo "<A HREF=\"",QUERY_SCRIPT,"\">Main Page</A>\n";
        //show_link_to_main_page();
        return;
    }

    // Get info about this run

    $query = "
SELECT rt.type        AS type,
       rs.status      AS status,
       r.user         AS user,
       r.time_create  AS time_create,
       r.time_init    AS time_init,
       r.time_start   AS time_start,
       r.time_stop    AS time_stop,
       r.total_events AS total_events
FROM         run        r
  INNER JOIN run_type   rt ON r.run_type_id=rt.id
  INNER JOIN run_status rs ON r.status=rs.id
WHERE r.name=\"$run_name\"
";
    //echo "$query\n";
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());
    $line = mysql_fetch_array($result, MYSQL_ASSOC);
    mysql_free_result($result);
    $run_type = $line["type"];
    $run_status = $line["status"];
    $user = $line["user"];
    $time_create = $line["time_create"];
    $time_init = $line["time_init"];
    $time_start = $line["time_start"];
    $time_stop = $line["time_stop"];
    $total_events = $line["total_events"];

    echo "<table cellpadding=3>\n";
    echo "<tr><td>Run number</td><td>",$run_number,"</td></tr>\n";
    echo "<tr><td>Run type</td><td>",$run_type,"</td></tr>\n";
    echo "<tr><td>User</td><td>",$user,"</td></tr>\n";
    echo "<tr><td>Run status</td><td>",$run_status,"</td></tr>\n";
    echo "<tr><td>Total events</td><td>",$total_events,"</td></tr>\n";
    echo "<tr><td>Time created</td><td>",$time_create,"</td></tr>\n";
    echo "<tr><td>Time initalized</td><td>",$time_init,"</td></tr>\n";
    echo "<tr><td>Time started</td><td>",$time_start,"</td></tr>\n";
    echo "<tr><td>Time stopped</td><td>",$time_stop,"</td></tr>\n";
    echo "</table>\n";

    echo "<br>\n";

    // Show log messages associated to this run
    $query = "SELECT type,level,time,text FROM log_entry WHERE run_number=$run_number";
    //echo "$query\n";
    $result = mysql_query($query) or die('Query failed: ' . mysql_error());
    echo "<table cellpadding=3>\n";
    echo "\t<tr><th>Log time</th><th>Type</th><th>Level</th><th>Message</th></tr>\n";
    while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $log_type = $line["type"];
        $log_level = $line["level"];
        $log_time = $line["time"];
        $log_text = $line["text"];
        echo "\t<tr>\n";
        echo "\t\t<td>$log_time</td>\n";
        echo "\t\t<td>$log_type</td>\n";
        echo "\t\t<td align=center>$log_level</td>\n";
        echo "\t\t<td>$log_text</td>\n";
        echo "\t</tr>\n";
    }
    echo "</table>\n";
    mysql_free_result($result);

}
?>
