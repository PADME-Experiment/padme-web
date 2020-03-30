<?php
# Show main header and define standard scripts and tools
require 'padme_query_header.php';

# Connect to the online DB
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
if (isset($_GET['physics'])) {
    $qphysics = htmlspecialchars($_GET['physics']);
} else {
    $qphysics = "ALL";
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
$result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
while ($line = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
    $type = $line["type"];
    if ( $type == $qtype ) {
        echo "\t\t\t<option value=",$type," selected=selected>",$type,"</option>\n";
    } else {
        echo "\t\t\t<option value=",$type,">",$type,"</option>\n";
    }
}
mysqli_free_result($result);
echo "\t\t</select>\n";

# Select physics status
echo "\t\t<label>Physics: </label>\n";
echo "\t\t<select name=physics>\n";
if ( $qphysics == "ALL" ) {
    echo "\t\t\t<option value=ALL selected=selected>ALL</option>\n";
    echo "\t\t\t<option value=GOOD>GOOD</option>\n";
} else {
    echo "\t\t\t<option value=ALL>ALL</option>\n";
    echo "\t\t\t<option value=GOOD selected=selected>GOOD</option>\n";
}
echo "\t\t</select>\n";

echo "\t\t<input type=submit value=Submit>\n";
echo "\t</fieldset>";
echo "</form>\n";

if ( $qname == "" ) {
    show_all_runs($qyear,$qtype,$qphysics);
} else {
    show_run($qname);
}

# Close DB connection before exiting
mysqli_close($mysqli);
?>

</BODY>
</HTML>

<?php
function show_all_runs($year,$type,$phys) {

    # Import DB handle
    global $mysqli;

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
    $where_clause = "";
    for ($c=0; $c<sizeof($clause_list); $c++) {
        if ($c == 0) {
            $where_clause = "WHERE ".$clause_list[$c];
        } else {
            $where_clause .= " AND ".$clause_list[$c];
        }
    }

    // Get list of run statuses
    $status = array();
    $query = "SELECT id,status FROM run_status";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    while ($line = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
        $status[$line["id"]] = $line["status"];
    }
    mysqli_free_result($result);

    // Get list of existing Runs
    $query = "
SELECT r.name         AS name,
       rt.type        AS type,
       r.status       AS status,
       r.time_create  AS time_create,
       r.time_stop    AS time_stop,
       r.total_events AS total_events
FROM         run        r
  INNER JOIN run_type   rt ON r.run_type_id=rt.id
$where_clause
ORDER BY r.time_create
";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));

    // Printing results in HTML
    echo "<table cellpadding=3>\n";
    echo "\t<tr><th>Run name</th><th>Type</th><th>Status</th><th>Physics</th><th>Events</th><th>Created</th><th>Ended</th></tr>\n";
    while ($line = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
        $name = $line["name"];
        $runstat = ((int)$line["status"]) % 100;
        $physics = floor(((int)$line["status"])/100);
        $type = $line["type"];
        $time_create = $line["time_create"];
        $time_stop = $line["time_stop"];
        $total_events = $line["total_events"];
        if ($phys == "ALL" || ($phys == "GOOD" && $physics == 1)) {
            echo "\t<tr>\n";
            echo "\t\t<td><a href=\"",RUN_SCRIPT,"?name=$name\">$name</a></td>\n";
            echo "\t\t<td align=center>$type</a></td>\n";
            echo "\t\t<td align=center>$status[$runstat]</a></td>\n";
            if ($physics == 1) {
                echo "\t\t<td align=center>GOOD</a></td>\n";
            } else {
                echo "\t\t<td align=center>-</a></td>\n";
            }
            if (is_null($total_events)) {
                echo "\t\t<td align=right>-</a></td>\n";
            } else {
                echo "\t\t<td align=right>$total_events</a></td>\n";
            }
            echo "\t\t<td>$time_create</a></td>\n";
            echo "\t\t<td>$time_stop</a></td>\n";
            echo "\t</tr>\n";
        }
    }
    echo "</table>\n";

    // Free resultset
    mysqli_free_result($result);

}
?>

<?php
function show_run($run_name) {

    # Import DB handle
    global $mysqli;

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
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    $line = mysqli_fetch_array($result,MYSQLI_ASSOC);
    mysqli_free_result($result);
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
    $elog_msgs = array();
    $query = "SELECT type,level,time,text FROM log_entry WHERE run_number=$run_number";
    //echo "$query\n";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    echo "<table cellpadding=3>\n";
    echo "\t<tr><th>Log time</th><th>Type</th><th>Level</th><th>Message</th></tr>\n";
    while ($line = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
        $log_type = $line["type"];
        $log_level = $line["level"];
        $log_time = $line["time"];
        $log_text = $line["text"];
        # Check if this is a link to an eLogBook message
        if ($log_type == "ELOG" and preg_match("/\d+/",$log_text)) {
            $elog_msgs[] = $log_text;
        } else {
            $log_text = preg_replace("/ELOG (\d+)/","<A HREF=\"".ELOGBOOK_HREF."\${1}\">ELOG \${1}</A>",$log_text);
            echo "\t<tr>\n";
            echo "\t\t<td>$log_time</td>\n";
            echo "\t\t<td>$log_type</td>\n";
            echo "\t\t<td align=center>$log_level</td>\n";
            echo "\t\t<td>$log_text</td>\n";
            echo "\t</tr>\n";
        }
    }
    echo "</table>\n";
    mysqli_free_result($result);

    # Show links to elogbook messages
    for ($c=0; $c<sizeof($elog_msgs); $c++) {
        if ($c == 0) echo "Related eLogBook messages:";
        echo " <A HREF=\"",ELOGBOOK_HREF,$elog_msgs[$c],"\">",$elog_msgs[$c],"</A>";
    }
    echo "\n";
}
?>
