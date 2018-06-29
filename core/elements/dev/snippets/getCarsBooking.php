<?php
$company_id = $modx->getOption('company_id', $scriptProperties, '');
$user_id = $modx->getOption('user_id', $scriptProperties, '');

$modx->addPackage('carRent', MODX_CORE_PATH . 'components/carrent/model/');
$pdo = $modx->getService('pdoFetch');
$pdo->setConfig(array(
	'class' => 'CarsBooking',
	'loadModels' => 'carRent',
));
$user = $pdo->getArray('modUserProfile', array('internalKey' => $user_id));
if($user['company_id'] != $company_id) return(0);

$company = $pdo->getArray('Company', $company_id);
$carsBooking = $pdo->getCollection('CarsBooking', array('company_id' => $company_id));
$result = array();
foreach ($carsBooking as $kod => $carBooking){
	$car = $pdo->getArray('Cars', array('company_id' => $company_id, 'pf_handbook_key' => $carBooking['car_id']));
	$client = $pdo->getArray('Clients', array('pf_client_userid' => $carBooking['client_id']));
	$result[] =  array(
		'start' => $carBooking['datetime_begin'],
		'end' => $carBooking['datetime_end'],
		'content' => '<a href="https://'.$company['pf_account'].'.planfix.ru/?action=planfix&task='.$carBooking['pf_task_id'].'" target="_blank">'.$client['client_fname'].' '.$client['client_lname'].'</a>',
		'group' => $car['car_mark'].' '.$car['car_model'].' ('.$car['car_nomer'].')',
		'className' => 'timeLine_class',
		'car_id' => $carBooking['id'],
		'pf_task_id' => $carBooking['pf_task_id'],
		'client_id' => $carBooking['client_id'],
		'action_id' => $carBooking['pf_action_id'],
        'id' => $carBooking['id']
	);
}
$modx->setPlaceholder('carsBooking', json_encode($result));
return json_encode($result);