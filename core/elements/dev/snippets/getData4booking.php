<?php
$company_id = $modx->getOption('company_id', $scriptProperties, '');
$user_id = $modx->getOption('user_id', $scriptProperties, '');

$modx->addPackage('carRent', MODX_CORE_PATH . 'components/carrent/model/');
$pdo = $modx->getService('pdoFetch');
$pdo->setConfig(array(
	'loadModels' => 'carRent',
));
$user = $pdo->getArray('modUserProfile', array('internalKey' => $user_id));
if($user['company_id'] != $company_id) return(0);

$company = $pdo->getArray('Company', $company_id);
$clients = $pdo->getCollection('Clients', array('company_id' => $company_id));
$cars = $pdo->getCollection('Cars', array('company_id' => $company_id));
$pls_clients ='<option value="-1" selected>Укажите клиента</option>';
$pls_cars ='<option value="-1" selected>Укажите автомобиль</option>';
foreach($clients as $id1 => $client)
	$pls_clients .= '<option value="'.$client['pf_client_userid'].'">'.$client['client_fname'].' '.$client['client_lname'].'</option>';
$modx->setPlaceholder('optionClient', $pls_clients);	
foreach($cars as $id2 => $car)
	$pls_cars .= '<option value="'.$car['pf_handbook_key'].'">'.$car['pf_handbook_fulltitle'].'</option>';
$modx->setPlaceholder('optionCar', $pls_cars);	
return(1);