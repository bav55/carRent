<?php
$api_server = 'https://apiru.planfix.ru/xml/';
$api_key = '2a69107453edb8812775265993d99cdc';// ������ http://dev.planfix.ru/
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
 * ����� ��������:
 * 1 - ��� ���������� ������������� ������ ��������� ��� ������ ���� ��������� �������;
 * 2 - ����� ����� ������ ���������� 20-� ��������;
 * 3 - ��� ������ ��������� ������ ��� ����� ������������;
 * 4 - ������ �� ���� �������� ����� ������ ������� ������� (���������� �������� ����������);
 */
$api_sid = $apiResult->sid;
echo "sid is: $api_sid<br>";
/*
 * �������� ������ ��������� ��� �������� � ������� ���
 * ���������� ������� ��: http://goo.gl/E41Vv
 */
$requestXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request method="project.getList"><account></account><sid></sid></request>');
$requestXml->account = $planfixAccount;
$requestXml->sid = $api_sid;
$requestXml->pageCurrent = 1;
// ��������� ��������� �������� ���������������, ������������������� ����
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
echo "����� �������� $totalCount<br>";
echo "�������� �������� $count<br>";

foreach($apiResult->projects->project as $project) {
	//var_dump($project);
echo "������: ".iconv('UTF-8', 'WINDOWS-1251', $project->title)."(".$project->id.") ���������:".iconv('UTF-8', 'WINDOWS-1251', $project->owner->name);
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
// ��������� ��������� �������� ���������������, ������������������� ����
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
echo "����� ����� $totalCount_tasks<br>";
echo "�������� ����� $count_tasks<br>";
foreach($apiResult_tasks->tasks->task as $task) {
	echo iconv('UTF-8', 'WINDOWS-1251', $task->title.'('.$task->id.')');
	echo "<hr>";
	echo '���������� ID='.$task->customData->customValue->field->id; //id �����������
	echo ' ���������� '.iconv('UTF-8', 'WINDOWS-1251', $task->customData->customValue->field->name); //�������� �����������
	echo ' �������� ������ ����������� (�����): '.$task->customData->customValue->value.', �������� �������� ����������� (������): '.iconv('UTF-8', 'WINDOWS-1251', $task->customData->customValue->text);
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