<?php
function get_run_number($name) {

    # Import DB handle
    global $mysqli;

    $query = "SELECT number FROM run WHERE name='$name'";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    if (mysqli_num_rows($result) == 0) {
        $run_number = -1;
    } else {
        $line = mysqli_fetch_array($result,MYSQLI_ASSOC);
        $run_number = $line["number"];
    }
    mysqli_free_result($result);

    return $run_number;

}
?>
<?php
function get_prod_id($name) {

    # Import DB handle
    global $mysqli;

    $query = "SELECT id FROM production WHERE name='$name'";
    $result = mysqli_query($mysqli,$query) or die('Query failed: '.mysqli_error($mysqli));
    if (mysqli_num_rows($result) == 0) {
        $prod_id = -1;
    } else {
        $line = mysqli_fetch_array($result,MYSQLI_ASSOC);
        $prod_id = $line["id"];
    }
    mysqli_free_result($result);

    return $prod_id;

}
?>
