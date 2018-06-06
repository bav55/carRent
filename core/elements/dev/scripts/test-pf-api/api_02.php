<?php
$api_server = 'https://apiru.planfix.ru/xml/';
$api_key = '2a69107453edb8812775265993d99cdc';// смотри http://dev.planfix.ru/
$api_secret = '1f04b7dd74aeb6e008f6174920df1c89';
$planfixAccount = 'bav55-test01';
$planfixUser = 'bav55';
$planfixUserPassword = 'Eybdthcjkjubz1';

include 'lib.php';

$requestXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request method="auth.login"><account></account><login></login><password></password></request>');

$requestXml->account = $planfixAccount;
$requestXml->login =  $planfixUser;
$requestXml->password = $planfixUserPassword;
$requestXml->signature = make_sign($requestXml, $api_secret);

$result = apiRequest($api_server, $api_key, $requestXml);
if(!$result['success']) {
	echo $result['response'];
	exit();
}
$apiResult = $result['response'];
parseAPIError($apiResult);

/**
 * Важно понимать:
 * 1 - что полученный идентификатор сессии необходим для вызова всех остальных функций;
 * 2 - время жизни сессии ограничено 20-ю минутами;
 * 3 - при каждом следующем вызове это время продлевается;
 * 4 - сессию не надо получать перед каждым вызовом функции (количество запросов ограничено);
 */
$api_sid = $apiResult->sid;
echo "sid is: $api_sid<br>";
/*
 * получаем список доступных нам проектов и выводим его
 * используем функции на: http://goo.gl/E41Vv
 */
$requestXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request method="project.getList"><account></account><sid></sid></request>');
$requestXml->account = $planfixAccount;
$requestXml->sid = $api_sid;
$requestXml->pageCurrent = 1;
// остальные параметры являются необязательными, поэкспериментируйте сами
$requestXml->signature = make_sign($requestXml, $api_secret);
$result = apiRequest($api_server, $api_key, $requestXml);
if(!$result['success']) {
	echo $result['response'];
	exit();
}
$apiResult = $result['response'];
parseAPIError($apiResult);
$totalCount = $apiResult->projects['totalCount'];
$count = $apiResult->projects['count'];
echo "Всего проектов $totalCount<br>";
echo "Получено проектов $count<br>";

foreach($apiResult->projects->project as $project) {
	//var_dump($project);
echo "Проект: ".iconv('UTF-8', 'WINDOWS-1251', $project->title)."(".$project->id.") создатель:".iconv('UTF-8', 'WINDOWS-1251', $project->owner->name);
	echo '<br>';
}
// now project's id = 562624
$tasksXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request method="task.getList"><account></account><sid></sid></request>');
$tasksXml->account = $planfixAccount;
$tasksXml->sid = $api_sid;
$tasksXml->pageCurrent = 1;
$tasksXml->project->id = 562624;
$tasksXml->pageSize = 100;
$tasksXml->target = "all";
// остальные параметры являются необязательными, поэкспериментируйте сами
$tasksXml->signature = make_sign($tasksXml, $api_secret);
$result_tasks = apiRequest($api_server, $api_key, $tasksXml);
if(!$result_tasks['success']) {
	echo $result_tasks['response'];
	exit();
}
$apiResult_tasks = $result_tasks['response'];
//var_dump($apiResult_tasks);
parseAPIError($apiResult_tasks);
$totalCount_tasks = $apiResult_tasks->tasks['totalCount'];
$count_tasks = $apiResult_tasks->tasks['count'];
echo "Всего задач $totalCount_tasks<br>";
echo "Получено задач $count_tasks<br>";
foreach($apiResult_tasks->tasks->task as $task) {
	echo iconv('UTF-8', 'WINDOWS-1251', $task->title.'('.$task->id.')');
	echo "<hr>";
	echo 'справочник ID='.$task->customData->customValue->field->id; //id справочника
	echo ' называется '.iconv('UTF-8', 'WINDOWS-1251', $task->customData->customValue->field->name); //название справочника
	echo ' значение записи справочника (номер): '.$task->customData->customValue->value.', значение элемента справочника (строка): '.iconv('UTF-8', 'WINDOWS-1251', $task->customData->customValue->text);
	echo var_dump($task->customData);
	echo "<br/>";
}	
//now updating task #14409820, set another car (value = 3)
$taskXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request method="task.update"><account></account><sid></sid></request>');
$taskXml->account = $planfixAccount;
$taskXml->sid = $api_sid;
$taskXml->task->id = 14409820;
$taskXml->task->customData->customValue->text = '';
$taskXml->task->customData->customValue->id = 86590;
$taskXml->task->customData->customValue->value = 2;
$taskXml->signature = make_sign($taskXml, $api_secret);
var_dump($taskXml);
$result_task = apiRequest($api_server, $api_key, $taskXml);
if(!$result_task['success']) {
	echo $result_task['response'];
	exit();
}
$apiResult_task = $result_task['response'];
var_dump($apiResult_task);
//now get info about updated task
$task2Xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request method="task.get"><account></account><sid></sid></request>');
$task2Xml->account = $planfixAccount;
$task2Xml->sid = $api_sid;
$task2Xml->task->id = 14409820;
$task2Xml->signature = make_sign($task2Xml, $api_secret);
var_dump($task2Xml);
$result_task2 = apiRequest($api_server, $api_key, $task2Xml);
if(!$result_task2['success']) {
	echo $result_task2['response'];
	exit();
}
$apiResult_task2 = $result_task2['response'];
var_dump($apiResult_task2);

?>