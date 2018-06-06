<?
require 'Planfix_API.php';
$PF = new Planfix_API(array('apiKey' => '2a69107453edb8812775265993d99cdc', 'apiSecret' => '1f04b7dd74aeb6e008f6174920df1c89'));
$PF->setAccount('bav55-test01');
session_start();
    $PF->setUser(array('login' => 'bav55', 'password' => 'Eybdthcjkjubz1'));
    $PF->authenticate();

$method = 'task.get';
$params = array(
    'pageCurrent' => 1,
    'task' => array('id' => 14618790)
);
$projects = $PF->api($method, $params);
var_dump($projects);
echo '<pre>'.print_r($projects, 1).'</pre>';
unset($PF);