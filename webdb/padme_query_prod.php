<?php
# Show main header and define standard scripts and tools
require 'padme_query_header.php';

# Connect to the online DB
require MCDB_CONNECT_SCRIPT;

$month = array("1"=>"Jan","2"=>"Feb","3"=>"Mar","4"=>"Apr","5"=>"May","6"=>"Jun","7"=>"Jul","8"=>"Aug","9"=>"Sep","10"=>"Oct","11"=>"Nov","12"=>"Dec");

$job_status_name = array("0"=>"Created","1"=>"Active","2"=>"Successful","3"=>"Failed");

# Default call
$qname = "";
$qjobid = "";
$qyear = date("Y");
$qtype = "MC";
$qdel = "NO";

# These will be used in the future to select shorter time periods
$qstart_month =  1;
$qstart_day   =  1;
$qend_month   = 12;
$qend_day     = 31;

// Get production name
if (isset($_GET['name'])) { $qname = htmlspecialchars($_GET['name']); }

// Get job id
if (isset($_GET['jobid'])) { $qjobid = htmlspecialchars($_GET['jobid']); }

// Get year
if (isset($_GET['year'])) { $qyear = htmlspecialchars($_GET['year']); }

// Get type
if (isset($_GET['type'])) { $qtype = htmlspecialchars($_GET['type']); }

// Get deleted status
if (isset($_GET['deleted'])) { $qdel = htmlspecialchars($_GET['deleted']); }

echo "<form name=DBQueryTool action=\"",PROD_SCRIPT,"\" method=GET>\n";
echo "\t<fieldset>\n";
echo "\t\t<legend>Prod selection</legend>\n";

# Select year
echo "\t\t<label>Year: </label>\n";
echo "\t\t<select name=year>\n";
if ( $qyear == "ALL" ) {
    echo "\t\t\t<option value=ALL selected=selected>ALL</option>\n";
} else {
    echo "\t\t\t<option value=ALL>ALL</option>\n";
}
for ($y=2019;$y<=(int)date("Y");$y++) {
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
foreach ( array("MC","RECO") as $type ) {
    if ( $qtype == $type ) {
        echo "\t\t\t<option value=$type selected=selected>$type</option>\n";
    } else {
        echo "\t\t\t<option value=$type>$type</option>\n";
    }
}
echo "\t\t</select>\n";

# Select deleted (wether to show them or not)
echo "\t\t<label>Show deleted: </label>\n";
echo "\t\t<select name=deleted>\n";
foreach ( array("YES","NO") as $del ) {
    if ( $qdel == $del ) {
        echo "\t\t\t<option value=$del selected=selected>$del</option>\n";
    } else {
        echo "\t\t\t<option value=$del>$del</option>\n";
    }
}
echo "\t\t</select>\n";

echo "\t\t<input type=submit value=Submit>\n";
echo "\t</fieldset>\n";
echo "</form>\n";

if ( $qname != "" ) {
    show_prod($qname);
} elseif ( $qjobid != "" ) {
    show_job($qjobid);
} else {
    if ( $qyear == "ALL" ) {
        $time_start = "ALL";
        $time_end = "ALL";
    } else {
        $time_start = sprintf("%4.4d-%02d-%02d 00:00:00",$qyear,$qstart_month,$qstart_day);
        $time_end = sprintf("%4.4d-%02d-%02d 23:59:59",$qyear,$qend_month,$qend_day);
    }
    if ( $qtype == "MC" ) {
        show_all_mcprods($time_start,$time_end,$qdel);
    } else {
        show_all_recoprods($time_start,$time_end,$qdel);
    }
}

# Close DB connection before exiting
mysqli_close($mysqli);
?>

</BODY>
</HTML>

<?php
    function show_all_mcprods($time_start,$time_end,$del) {

    # Import DB handle
    global $mysqli;

    if ( $time_start == "ALL" ) {
        echo "<h2>PADME MC Productions</h2>\n";
    } else {
        echo "<h2>PADME MC Productions for period from ".substr($time_start,0,10)." to ".substr($time_end,0,10)."</h2>\n";
    }

    # Can easily add more clauses
    $clause_list = array();
    if ( $time_start != "ALL" ) {
        $clause_list[] = "p.time_create >= \"$time_start\" AND p.time_create <= \"$time_end\"";
    }
    if ( $del == "NO" ) {
        $clause_list[] = "NOT p.name LIKE \"%\_deleted\_%\"";
    }
    $where_clause = "";
    for ($c=0; $c<sizeof($clause_list); $c++) {
        if ($c == 0) {
            $where_clause = "WHERE ".$clause_list[$c];
        } else {
            $where_clause .= " AND ".$clause_list[$c];
        }
    }

    // Get list of existing Productions
    $query = "
SELECT p.name          AS name,
       p.time_create   AS time_create,
       p.time_complete AS time_complete,
       p.n_jobs        AS n_jobs,
       p.n_jobs_ok     AS n_jobs_ok,
       p.n_jobs_fail   AS n_jobs_fail,
       p.n_events      AS total_events,
       mp.user_req     AS req_user,
       mp.n_events_req AS req_events,
       mp.mc_version   AS mc_version
FROM         production p
  INNER JOIN mc_prod    mp ON p.id=mp.production_id
$where_clause
ORDER BY p.time_create
";
    #echo "<PRE>\n$query\n</PRE>\n";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));

    if (mysqli_num_rows($result)==0) {
        echo "<h2>No production was started in the selected period</h2>\n";
    } else {
        // Printing results in HTML
        echo "<table cellpadding=3>\n";
        echo "\t<tr><th>Production name</th><th>MC Version</th><th>User</th><th>Req Evts</th><th>Prod Evts</th><th>Created</th><th>Ended</th><th>Jobs</th></tr>\n";
        while ($line = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
            $name = $line["name"];
            $time_create = $line["time_create"];
            $time_complete = $line["time_complete"];
            $total_events = $line["total_events"];
            $mc_version = $line["mc_version"];
            $req_user = $line["req_user"];
            $req_events = $line["req_events"];
            $job_tot = $line["n_jobs"];
            $job_ok = $line["n_jobs_ok"];
            $job_fail = $line["n_jobs_fail"];
            echo "\t<tr>\n";
            echo "\t\t<td><a href=\"",PROD_SCRIPT,"?name=$name\">$name</a></td>\n";
            echo "\t\t<td>$mc_version</td>\n";
            echo "\t\t<td>$req_user</td>\n";
            echo "\t\t<td align=right>$req_events</td>\n";
            if (is_null($total_events)) {
                echo "\t\t<td align=right>-</td>\n";
            } else {
                echo "\t\t<td align=right>$total_events</td>\n";
            }
            echo "\t\t<td>$time_create</td>\n";
            echo "\t\t<td>$time_complete</td>\n";
            echo "\t\t<td align=center>$job_tot ($job_ok/$job_fail)</td>\n";
            echo "\t</tr>\n";
        }
        echo "</table>\n";
    }
    mysqli_free_result($result);

}
?>

<?php
    function show_all_recoprods($time_start,$time_end,$del) {

    # Import DB handle
    global $mysqli;

    if ( $time_start == "ALL" ) {
        echo "<h2>PADME Reco Productions</h2>\n";
    } else {
        echo "<h2>PADME Reco Productions for period from ".substr($time_start,0,10)." to ".substr($time_end,0,10)."</h2>\n";
    }

    # Can easily add more clauses
    $clause_list = array();
    if ( $time_start != "ALL" ) {
        $clause_list[] = "p.time_create >= \"$time_start\" AND p.time_create <= \"$time_end\"";
    }
    if ( $del == "NO" ) {
        $clause_list[] = "NOT p.name LIKE \"%\_deleted\_%\"";
    }
    $where_clause = "";
    for ($c=0; $c<sizeof($clause_list); $c++) {
        if ($c == 0) {
            $where_clause = "WHERE ".$clause_list[$c];
        } else {
            $where_clause .= " AND ".$clause_list[$c];
        }
    }

    // Get list of existing Productions
    $query = "
SELECT p.name          AS name,
       p.time_create   AS time_create,
       p.time_complete AS time_complete,
       p.n_jobs        AS n_jobs,
       p.n_jobs_ok     AS n_jobs_ok,
       p.n_jobs_fail   AS n_jobs_fail,
       p.n_events      AS total_events,
       rp.run          AS run,
       rp.reco_version AS reco_version
FROM         production p
  INNER JOIN reco_prod  rp ON p.id=rp.production_id
$where_clause
ORDER BY p.time_create
";
    #echo "<PRE>\n$query\n</PRE>\n";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));

    if (mysqli_num_rows($result)==0) {
        echo "<h2>No production was started in the selected period</h2>\n";
    } else {
        // Printing results in HTML
        echo "<table cellpadding=3>\n";
        echo "\t<tr><th>Production name</th><th>Reco Version</th><th>Run</th><th>Prod Evts</th><th>Created</th><th>Ended</th><th>Jobs</th></tr>\n";
        while ($line = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
            $name = $line["name"];
            $time_create = $line["time_create"];
            $time_complete = $line["time_complete"];
            $total_events = $line["total_events"];
            $reco_version = $line["reco_version"];
            $run = $line["run"];
            $job_tot = $line["n_jobs"];
            $job_ok = $line["n_jobs_ok"];
            $job_fail = $line["n_jobs_fail"];
            echo "\t<tr>\n";
            echo "\t\t<td><a href=\"",PROD_SCRIPT,"?name=$name\">$name</a></td>\n";
            echo "\t\t<td>$reco_version</td>\n";
            if ( preg_match("/^run_/",$run) ) {
                echo "\t\t<td><a href=\"",RUN_SCRIPT,"?name=$run\">$run</a></td>\n";
            } else {
                echo "\t\t<td><a href=\"",PROD_SCRIPT,"?name=$run\">$run</a></td>\n";
            }
            if (is_null($total_events)) {
                echo "\t\t<td align=right>-</td>\n";
            } else {
                echo "\t\t<td align=right>$total_events</td>\n";
            }
            echo "\t\t<td>$time_create</td>\n";
            echo "\t\t<td>$time_complete</td>\n";
            echo "\t\t<td align=center>$job_tot ($job_ok/$job_fail)</td>\n";
            echo "\t</tr>\n";
        }
        echo "</table>\n";
    }
    mysqli_free_result($result);

}
?>

<?php
function show_prod($prod_name) {

    # Import DB handle
    global $mysqli;

    # Import job status table
    global $job_status_name;

    // **************** Prod page ******************* //

    echo "<h2>Production ",$prod_name,"</h2>\n";

    // Get prod id from prod name
    $prod_id = get_prod_id($prod_name);
    if ($prod_id < 0) {
        echo "ERROR: production ",$prod_name," does not exist in the database<br>\n";
        return;
    }

    // Check if it is Simulation or Reconstruction
    $n_simulation = 0;
    $query = "SELECT description,user_req,n_events_req,mc_version FROM mc_prod WHERE production_id = $prod_id";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    if (mysqli_num_rows($result) != 0) {
        while ($line = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
            $n_simulation++;
            $mc_description = $line["description"];
            $mc_user_req = $line["user_req"];
            $mc_n_evts_req = $line["n_events_req"];
            $mc_version = $line["mc_version"];
            echo "<h3>Simulation information</h3>\n";
            echo "<table cellpadding=3>\n";
            echo "<tr><td>Requested by</td><td>",$mc_user_req,"</td></tr>\n";
            echo "<tr><td>Events requested</td><td>",$mc_n_evts_req,"</td></tr>\n";
            echo "<tr><td>PadmeMC version</td><td>",$mc_version,"</td></tr>\n";
            echo "<tr><td valign=top>Description</td><td>",$mc_description,"</td></tr>\n";
            echo "</table>\n";
        }
    }
    mysqli_free_result($result);

    $n_reconstruction = 0;
    $query = "SELECT description,run,reco_version FROM reco_prod WHERE production_id = $prod_id";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    if (mysqli_num_rows($result) != 0) {
        while ($line = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
            $n_reconstruction++;
            $reco_description = $line["description"];
            $reco_run = $line["run"];
            $reco_version = $line["reco_version"];
            echo "<h3>Reconstruction information</h3>\n";
            echo "<table cellpadding=3>\n";
            echo "<tr><td>Run/Simulation</td><td>",$reco_run,"</td></tr>\n";
            echo "<tr><td>PadmeReco version</td><td>",$reco_version,"</td></tr>\n";
            echo "<tr><td valign=top>Description</td><td>",$reco_description,"</td></tr>\n";
            echo "</table>\n";
        }
    }
    mysqli_free_result($result);

    if ($n_simulation == 0 && $n_reconstruction == 0) {
        echo "<h3>*** WARNING *** Production is neither a Simulation nor a Reconstruction</h3>\n";
    }
    if ($n_simulation > 0 && $n_reconstruction > 0) {
        echo "<h3>*** WARNING *** Production is both a Simulation and a Reconstruction</h3>\n";
    }
    if ($n_simulation > 1) {
        echo "<h3>*** WARNING *** Production is a multiply defined Simulation</h3>\n";
    }
    if ($n_reconstruction > 1) {
        echo "<h3>*** WARNING *** Production is a multiply defined Reconstruction</h3>\n";
    }

    // Get info about this production

    $query = "
SELECT prod_ce,
       prod_dir,
       storage_uri,
       storage_dir,
       time_create,
       time_complete,
       n_jobs,
       n_jobs_ok,
       n_jobs_fail,
       n_events
FROM production p
WHERE p.name=\"$prod_name\"
";
    //echo "$query\n";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    $line = mysqli_fetch_array($result,MYSQLI_ASSOC);
    mysqli_free_result($result);
    $prod_ce = preg_replace("/ /","<BR>",$line["prod_ce"]);
    $prod_dir = $line["prod_dir"];
    $storage_uri = $line["storage_uri"];
    $storage_dir = $line["storage_dir"];
    $time_create = $line["time_create"];
    $time_complete = $line["time_complete"];
    $n_jobs = $line["n_jobs"];
    $n_jobs_ok = $line["n_jobs_ok"];
    $n_jobs_fail = $line["n_jobs_fail"];
    $n_events = $line["n_events"];

    echo "<table cellpadding=3>\n";
    echo "<tr><td valign=top>CE List</td><td>",$prod_ce,"</td></tr>\n";
    echo "<tr><td>Storage URI</td><td>",$storage_uri,"</td></tr>\n";
    echo "<tr><td>Storage_dir</td><td>",$storage_dir,"</td></tr>\n";
    echo "<tr><td>Total events</td><td>",$n_events,"</td></tr>\n";
    echo "<tr><td>Jobs</td><td>",$n_jobs," (",$n_jobs_ok,"/",$n_jobs_fail,")</td></tr>\n";
    echo "<tr><td>Time created</td><td>",$time_create,"</td></tr>\n";
    echo "<tr><td>Time complete</td><td>",$time_complete,"</td></tr>\n";
    echo "</table>\n";

    # Get info about jobs
    $query = "
SELECT id,
       name,
       status,
       time_create,
       time_complete,
       n_files,
       n_events
FROM job
WHERE production_id = $prod_id
";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    if (mysqli_num_rows($result) == 0) {
        echo "<h3>*** WARNING *** No Jobs associted to this Production</h3>\n";
    } else {
        echo "<h3>Production Jobs</h3>\n";
        echo "<table cellpadding=3>\n";
        echo "\t<tr><th>Job name</th><th>Status</th><th>Files</th><th>Events</th><th>Created</th><th>Ended</th></tr>\n";
        while ($line = mysqli_fetch_array($result,MYSQLI_ASSOC)) {
            $job_id = $line["id"];
            $job_name = $line["name"];
            $job_status = $line["status"];
            $job_time_create = $line["time_create"];
            $job_time_complete = $line["time_complete"];
            $job_n_files = $line["n_files"];
            $job_n_events = $line["n_events"];
            echo "\t<tr>\n";
            echo "\t\t<td><a href=\"",PROD_SCRIPT,"?jobid=$job_id\">$job_name</a></td>\n";
            echo "\t\t<td>$job_status_name[$job_status] ($job_status)</td>\n";
            echo "\t\t<td align=right>$job_n_files</td>\n";
            echo "\t\t<td align=right>$job_n_events</td>\n";
            echo "\t\t<td>$job_time_create</td>\n";
            echo "\t\t<td>$job_time_complete</td>\n";
            echo "\t</tr>\n";
        }
        echo "</table>\n";
    }
    mysqli_free_result($result);
}
?>

<?php
function show_job($job_id) {

    # Import DB handle
    global $mysqli;

    # Import job status table
    global $job_status_name;

    // **************** Job page ******************* //

    # Get production information for this job
    $query = "
SELECT p.name        AS prod_name,
       p.storage_uri AS storage_uri,
       p.storage_dir AS storage_dir
FROM job j INNER JOIN production p ON j.production_id = p.id
WHERE j.id = $job_id
";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    if (mysqli_num_rows($result) != 0) {
        $line = mysqli_fetch_array($result,MYSQLI_ASSOC);
        $prod_name = $line["prod_name"];
        $storage_uri = $line["storage_uri"];
        $storage_dir = $line["storage_dir"];
    }
    mysqli_free_result($result);

    # Get number of submissions for this job
    $query = "SELECT id FROM job_submit WHERE job_id=$job_id";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    $job_n_submit = mysqli_num_rows($result);
    mysqli_free_result($result);

    # Get job information
    $query = "SELECT name,configuration,input_list,random,status,time_create,time_complete,n_files,n_events FROM job WHERE id=$job_id";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    $line = mysqli_fetch_array($result,MYSQLI_ASSOC);
    mysqli_free_result($result);
    $job_name = $line["name"];
    $job_configuration = $line["configuration"];
    $job_input_list = $line["input_list"];
    $job_random = $line["random"];
    $job_status = $line["status"];
    $job_time_create = $line["time_create"];
    $job_time_complete = $line["time_complete"];
    $job_n_files = $line["n_files"];
    $job_n_events = $line["n_events"];

    echo "<h2>Production ",$prod_name," Job ",$job_name,"</h2>\n";

    echo "<table cellpadding=3>\n";
    echo "<tr><td>Status</td><td>$job_status_name[$job_status] ($job_status)</td></tr>\n";
    echo "<tr><td>Job Started</td><td>",$job_time_created,"</td></tr>\n";
    echo "<tr><td>Job Ended</td><td>",$job_time_complete,"</td></tr>\n";
    echo "<tr><td>Files produced</td><td>",$job_n_files,"</td></tr>\n";
    echo "<tr><td>Events processed</td><td>",$job_n_events,"</td></tr>\n";
    echo "</table>\n";


} 
