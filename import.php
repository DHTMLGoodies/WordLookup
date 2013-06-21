<?php
/**
 *
 * User: Alf Magne
 * Date: 21.06.13
 * Time: 07:50
 */
error_reporting(E_ALL);
ini_set('display_errors', 'on');
require_once __DIR__ . "/autoload.php";

echo "<html><head></head><body><div id='counter'></div></body></html>";

LudoDB::setDb("dictionary");
LudoDB::setHost("127.0.0.1");
LudoDB::setPassword("administrator");
LudODB::setUser("root");

function increment($cat){
    static $inc;
    if(!isset($inc))$inc = 0;
    $inc++;

    ?>
    <script>document.getElementById('counter').innerHTML = 'Count: <?php echo $inc; ?> (<?php echo $cat; ?>';</script>
    <?php


}

function addWord($word, $cat)
{
    $word = trim($word);
    if (!preg_match("/[0-9\-A-Z]/s", $word)) {
        $row = LudoDB::getInstance()->one("select * from dictionary where word = ? and category=?", array($word, $cat));
        if (!isset($row)) {
            LudoDB::getInstance()->query("insert into dictionary(word,category,word_length)values(?,?,?)", array($word,$cat,strlen($word)));

            increment($cat);
        }
    }

}

function addLine($line, $cat)
{
    $line = preg_replace("/\s+/", " ", $line);
    $tokens = preg_split("/\s/s", $line);
    array_shift($tokens);

    addWord($tokens[0], $cat);
    addWord($tokens[1], $cat);

}

if(!isset($_GET['iterateFrom'])){
    LudoDB::getInstance()->query("drop table dictionary");
    LudoDB::getInstance()->query("create table dictionary(id int auto_increment not null primary key, word varchar(255),word_length int, category varchar(15))");
    LudoDB::getInstance()->query("delete from dictionary");
    LudoDB::getInstance()->query("commit");
}



function importFile($file, $cat){

    $startFrom = isset($_GET['iterateFrom']) ? $_GET['iterateFrom'] : 0;
    $max = $startFrom + 10000;
    $counter = 0;

    $handle = @fopen($file, "r");
    if ($handle) {
        while (($buffer = fgets($handle, 4096)) !== false) {
            if($counter >= $startFrom){
                addLine($buffer, $cat);

            }
            $counter ++;

            if($counter > $max){
                ?>
                <script>location.href='<?php echo $_SERVER['PHP_SELF']; ?>?iterateFrom=<?php echo $counter; ?>&rnd=' + Math.random();</script>
                <?php
                exit;
            }
        }
        if (!feof($handle)) {
            echo "Error: unexpected fgets() fail\n";
        }
        fclose($handle);
    }

}

$cat = isset($_GET['cat']) ? $_GET['cat'] : 'no_bm';


switch($cat){
    case 'no_nn':
        importFile('txt/fullform_nn.txt', 'no_nn');
        break;
    default:
        importFile('txt/fullform_bm.txt', 'no_bm');
        break;

}


LudoDB::getInstance()->query("create index dic_len on dictionary(word_length)");
LudoDB::getInstance()->query("create index dic_word on dictionary(word)");
LudoDB::getInstance()->query("create index dic_cat on dictionary(category)");



