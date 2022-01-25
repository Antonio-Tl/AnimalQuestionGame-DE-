<?php
$dbh = null;

function connect()
{
    global $dbh;

    if (!$dbh) {
        $dbh = mysqli_connect('127.0.0.1', 'root', '');
    }
}


function query($sql)
{
    global $dbh;
    if (!$dbh) {
        connect();
    }

    $result = array();
    $rh = @mysqli_query($dbh, $sql);
    while ($result[] = @mysqli_fetch_assoc($rh)) ;
    array_pop($result);

    return $result;
}