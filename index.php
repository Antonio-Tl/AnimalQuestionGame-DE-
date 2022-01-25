<?php
session_start();
$dbh = null;
require_once "database.php";


if (!isset($_GET['mode'])) {
    // neuer spieler
    header('Location: index.php?mode=question&id=1');


    die();
}


if ($_GET['mode'] == 'newQuestion') {

    $sql = "select * from minigame.nodes where yes=" . (int)$_GET['myAnimal'] . " OR no=" . (int)$_GET['myAnimal'];
    $result = query($sql);
    $oldQuestion = $result[0]['id'];

    // neue Frage speichern
    if ($_GET['userAnswer'] == 'YES') {
        $yesAnimal = $_GET['userAnimal'];
        $noAnimal = $_GET['myAnimal'];
    } else {
        $noAnimal = $_GET['userAnimal'];
        $yesAnimal = $_GET['myAnimal'];
    }
    // beide Tiere unter die neue Frage hängen
    $sql = "insert into minigame.nodes values(NULL, '" . $_GET['userQuestion'] . "', " . (int)$yesAnimal . ", " . (int)$noAnimal . ")";
    query($sql);
    $questionId = mysqli_insert_id($dbh);

    // alten pfeil von alter Frage an richtiger Stelle auf neue Frage umbiegen
    if ($result[0]['yes'] == (int)$_GET['myAnimal']) {
        $sql = "UPDATE minigame.nodes set yes=" . $questionId . " where id=" . $oldQuestion;
        query($sql);
    } else {
        $sql = "UPDATE minigame.nodes set no=" . $questionId . " where id=" . $oldQuestion;
        query($sql);
    }

    echo "Ich habe mir das gemerkt. Willst Du noch einmal spielen?";
    echo '<br>';
    echo '<A href="?"> JA </A>';
    echo ' NÖ - Ich bin doof - du hast gewonnen';
    die();
}


if ($_GET['mode'] == 'newAnimal') {
    // neues Tier in DB eintragen
    $sql = "select name from minigame.nodes where id = " . (int)$_GET['myAnimal'];
    $result = query($sql);
    $myAnimalName = $result[0]['name'];
    $sql = "insert into minigame.nodes values (NULL, '" . $_GET['animalName'] . "', null, null)";
    query($sql);
    $animalId = mysqli_insert_id($dbh);
    echo "Ich habe mir das Tier gemerkt. Im Merkkästelchen mit der Nummer " . $animalId;
    echo '<br>';
    echo "Stelle eine Frage, die Dein Tier (" . $_GET['animalName'] . ") von meinem Tier (" . $myAnimalName . ") unterscheidet!";
    echo '<br>';
    echo '<form method="GET" action="?">
<input type="hidden" name="mode" value="newQuestion">
<input type="hidden" name="userAnimal" value="' . $animalId . '">
<input type="hidden" name="myAnimal" value="' . $_GET['myAnimal'] . '">
<input name="userQuestion" ><br>
Wie ist die Frage für Dein Tier (' . $_GET['animalName'] . ') zu beantworten?<br>
<input type="radio" name="userAnswer" checked value="YES"> JA - KLAR<br>
<input type="radio" name="userAnswer" value="NO"> NEE - NatÜRLICH NICHT!!<br>
<input type="submit" value="Diese Frage unterscheidet die Tiere! Merk Dir das!">
</form>';
    die();
}

if ($_GET['mode'] == 'lose') {
    // user nach Tier fragen
    echo "Ok, ich hab verloren! An welches Tier hast Du gedacht?";
    echo '<form method="GET" action="?">
<input type="hidden" name="mode" value="newAnimal">
<input type="hidden" name="myAnimal" value="' . $_GET['myAnimal'] . '">
<input name="animalName">
<input type="submit" value="Merk Dir das!">
</form>';

    die();
}

if ($_GET['mode'] == 'question') {
    $id = max(1, (int)$_GET['id']);

    $node = loadNode($id);

    if (hasNodeExits($node)) {
        // Frage stellen
        askQuestion($node);
    } else {
        // Tier raten
        guessAnimal($node);
    }


    die('ürks');
}

if ($_GET['mode'] == 'win') {
    echo "Ätsch - ich habe gewonnen. Du bist doof!";
    echo "Willst Du noch einmal spielen?";
    echo '<A href="?"> JA </A>';
    echo ' NÖ - Ich bin doof - du hast gewonnen';
    die();
}

die('mode ' . $_GET['mode'] . ' wurde noch nicht implementiert.');


function askQuestion($node)
{
    echo $node['name'] . '<br>';
    echo '<A href="?mode=question&id=' . $node['yes'] . '"> JA </A>';
    echo '<A href="?mode=question&id=' . $node['no'] . '"> NÖ </A>';

    die();
}

function guessAnimal($node)
{
    echo 'Denkst Du vielleicht an ' . $node['name'] . '?<br>';
    echo '<A href="?mode=win"> JA </A>';
    echo '<A href="?mode=lose&myAnimal=' . $node['id'] . '"> NÖ </A>';

    die();
}


function loadNode($id)
{
    $sql = "select * from minigame.nodes where id = " . (int)$id;
    $result = query($sql);

    return $result[0];
}

function hasNodeExits($node)
{
    if ($node['yes'] && $node['no']) {
        return true;
    } else {
        return false;
    }

}