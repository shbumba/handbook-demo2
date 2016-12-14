<?php
require_once 'vendor/autoload.php';

error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
date_default_timezone_set('Europe/Samara');
setlocale(LC_ALL, 'ru_RU.UTF-8');

try {
    $result = null;

    $sendData = isset($_REQUEST['sendParam']) ? (array) $_REQUEST['sendParam'] : null;

    $data = array(
        'total' => 366, //Total pages
        'pageSize' => 2, //Pages on one sheet
        'sheetSize' => 2, //The number of sides of the paper used
        'notebookSheet' => 4 //The number of sheets in a notebook
    );

    if (null !== $sendData) {
        $data = array_merge($data, $sendData);
    }

    $result = (new HandBook\Book($data))->run();

    $doc = new HandBook\DocGenerate($result);

    $fileHands = $doc->createByHands();
    $fileAll = $doc->createAll();

    foreach (array_merge($fileHands, array($fileAll)) as $file) {
        $src = ltrim($file, './');
        echo '<a href="'.$src.'">'.$src.'</a><br/>';
    }
} catch (HandBook\Exception $e) {
    echo $e->getMessage() . "\n";
    echo implode("\n", $e->getErrors());
}