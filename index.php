<?php
/**
 * Game mechanics
 * The game consists of a B-Tree holding question on all nodes and animals on all leaves
 * The game starts by asking the question at the root node. according to the answer of
 * the user the user traverses down the B-Tree until he ends at a leave.
 *
 * If the leave consists of the animal the user had in mind - the computer wins the game
 * In case the user ends up on another animal than he thought about - the user wins the game.
 * In that case the user is asked about his animal (which is inserted as new leave into the B-Tree)
 * After the Animal is inserted the user has to provide a question that differentiates his animal
 * from the animal in the leave his questions lead to.
 * In a final step the user is asked how this question should be answered for his new animal.
 * Then the B-Tree is modified to move the question on the position of the old animal having
 * the old animal and the new animal as final new leaves in the new B-Tree.
 *
 */
session_start();
$dbh = null;
require_once "database.php";

/**
 * GAME MODES:
 *      question: - user still travels along the question nodes down the B-Tree
 *                  game mode changes either to win or lose
 *
 *      win:      - user ended up on a leave and the animal is correct (computer wins)
 *                  game mode can only change back for a new game to question
 *
 *      lose:     - user ended up on a wrong animal - now the inputs have to be done
 *                  game mode changes to newAnimal
 *
 *      newAnimal:- user gabe a new animal - now we need to ask the question and how
 *                  it is answered for the new animal
 *                  game mode changes to newQuestion
 *
 *      newQuestion: finalize the B-Tree and ask if the user wants to play again
 *                  game mode changes to question to start a new game at the root node
 *
 */

if (!isset($_SESSION['mode'])) {
    // new player - no mode is set - set mode to question and start at node 1 (root)
    echo 'Hallo neuer Spieler! Hier sind die Spielregeln....';
    $_SESSION['mode'] = "question";
    $_SESSION['id'] = 1;
    $_SESSION['computer'] = 0;
    $_SESSION['human'] = 0;
}


//print_r($_SESSION);

// redirects
switch ($_SESSION['mode']) {
    case 'guessAnimalRedirect':
        if (isset($_GET['answer'])) {
            // user clicked yes or no on this question
            if ($_GET['answer'] == 'yes') {
                $_SESSION['mode'] = 'win';
            } elseif ($_GET['answer'] == 'no') {
                $_SESSION['mode'] = 'lose';
            } else {
                $_SESSION['mode'] = 'question';
            }
        }
        break;
    default:
        break;
}
//print_r($_SESSION);
switch ($_SESSION['mode']) {
    case 'question':
        $_SESSION['id'] = max(1, $_SESSION['id']);

        $questionNode = loadNode($_SESSION['id']);
        if (isset($_GET['answer'])) {
            // user clicked yes or no on this question
            //var_dump($_GET['answer']);
            if ($_GET['answer'] == 'YES') {
                $_SESSION['id'] = $questionNode['yes'];
                $questionNode = loadNode($_SESSION['id']);
            } elseif ($_GET['answer'] == 'NO') {
                $_SESSION['id'] = $questionNode['no'];
                $questionNode = loadNode($_SESSION['id']);
            }
        }

        if (hasNodeExits($questionNode)) {
            // we are in the B-Tree at a node
            askQuestion($questionNode);
        } else {
            // we are on a leave
            $_SESSION['mode'] = 'guessAnimalRedirect';
            guessAnimal($questionNode);
        }

        break;


    case 'win':
        $_SESSION['mode'] = 'question';
        $_SESSION['id'] = 1;
        echo "Ätsch - ich habe gewonnen. Du bist doof!";
        echo "Willst Du noch einmal spielen?";
        echo '<A href="?"> JA </A>';
        echo ' NÖ - Ich bin doof - du hast gewonnen';
        @$_SESSION['computer']++;
        break;


    case 'lose':
        // user nach Tier fragen
        echo "Ok, ich hab verloren! An welches Tier hast Du gedacht?";
        echo '<form method="GET" action="?">';
        $_SESSION['mode'] = 'newAnimal';
        echo ' <input name="animalName"> <input type="submit" value="Merk Dir das!"> </form>';
        @$_SESSION['human']++;

        break;

    case 'newAnimal':
        // neues Tier in DB eintragen
        $_SESSION['userAnimalName'] = $_GET['animalName'];
        $_SESSION['mode'] = 'newQuestion';
        $node = loadNode($_SESSION['id']);
        $myAnimalName = $node['name'];
        $sql = "insert into minigame.nodes values (NULL, '" . $_SESSION['userAnimalName'] . "', null, null)";
        query($sql);
        $animalId = mysqli_insert_id($dbh);
        $_SESSION['userAnimalId'] = $animalId;
        echo "Ich habe mir das Tier gemerkt. Im Merkkästelchen mit der Nummer " . $animalId;
        echo '<br>';
        echo "Stelle eine Frage, die Dein Tier (" . $_SESSION['userAnimalName'] . ") von meinem Tier (" . $myAnimalName . ") unterscheidet!";
        echo '<br>';
        echo '<form method="GET" action="?">
                <input name="userQuestion" ><br>
                Wie ist die Frage für Dein Tier (' . $_SESSION['userAnimalName'] . ') zu beantworten?<br>
                <input type="radio" name="userAnswer" checked value="YES"> JA - KLAR<br>
                <input type="radio" name="userAnswer" value="NO"> NEE - NatÜRLICH NICHT!!<br>
                <input type="submit" value="Diese Frage unterscheidet die Tiere! Merk Dir das!">
                </form>';
        break;

    case 'newQuestion':

        $sql = "select * from minigame.nodes where yes=" . $_SESSION['id'] . " OR no=" . $_SESSION['id'];
        $result = query($sql);
        $oldQuestion = $result[0]['id'];

        // neue Frage speichern
        if ($_GET['userAnswer'] == 'YES') {
            $yesAnimal = $_SESSION['userAnimalId'];
            $noAnimal = $_SESSION['id'];
        } elseif ($_GET['userAnswer'] == 'NO') {
            $noAnimal = $_SESSION['userAnimalId'];
            $yesAnimal = $_SESSION['id'];
        } else {
            //invalid input
            die('invalid user input - game ends here');
        }
        // beide Tiere unter die neue Frage hängen
        $sql = "insert into minigame.nodes values(NULL, '" . $_GET['userQuestion'] . "', " . (int)$yesAnimal . ", " . (int)$noAnimal . ")";
        query($sql);
        $questionId = mysqli_insert_id($dbh);

        // alten pfeil von alter Frage an richtiger Stelle auf neue Frage umbiegen
        if ($result[0]['yes'] == $_SESSION['id']) {
            $sql = "UPDATE minigame.nodes set yes=" . $questionId . " where id=" . $oldQuestion;
            query($sql);
        } else {
            $sql = "UPDATE minigame.nodes set no=" . $questionId . " where id=" . $oldQuestion;
            query($sql);
        }

        echo "Ich habe mir das gemerkt. Willst Du noch einmal spielen?";
        echo '<br>';
        $_SESSION['mode'] = 'question';
        $_SESSION['id'] = 1;
        $_SESSION['userAnimalId'] = null;
        $_SESSION['userAnimalName'] = 1;
        echo '<A href="?"> JA </A>';
        echo ' NÖ - Ich bin doof - du hast gewonnen';
        break;

    default:
        die('Game mode unknown!');
}


die();


function askQuestion($node)
{
    echo $node['name'] . '<br>';
    echo '<A href="?answer=YES"> JA </A>';
    echo '<A href="?answer=NO"> NÖ </A>';

    die();
}

function guessAnimal($node)
{
    echo 'Denkst Du vielleicht an ' . $node['name'] . '?<br>';
    echo '<A href="?answer=yes"> JA </A>';
    echo '<A href="?answer=no"> NÖ </A>';

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