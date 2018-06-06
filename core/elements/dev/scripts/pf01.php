<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Отладка кода</title>
</head>
<body>

<?php
// Подключаем
define('MODX_API_MODE', true);
require '../../../../index.php';
require 'Planfix_API.php';
session_start();
// Включаем обработку ошибок
$modx->getService('error','error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');

// Подгрузить пакет carRent
$modx->addPackage('carRent', MODX_CORE_PATH . 'components/carrent/model/');
// Получить список компаний-агентов, у которых pf_account непусто, положить в массив.
$pdo = $modx->getService('pdoFetch');
$pdo->setConfig(array(
	'class' => 'Company',
	'loadModels' => 'carRent',
	'select' => array(
		'Company' => '*',
	),
));

$log_level = 2;
$companies = $pdo->getCollection('Company', array('pf_account:!=' => ''));
//echo '<pre>';
//print_r($companies);
//Отладка - лог выполнения запроса
//echo '<hr/>';
//print_r($modx->getPlaceholder('pdoTools.log'));
//echo '</pre>';
$handbook_cars_map = array(
  // Cars field                  => array(Handbook field name  => Handbook field id)
    'pf_handbook_fulltitle'      => array('Марка, Модель (гос. номер)' => ''),
    'car_mark'                   => array('Марка автомобиля' => ''),
    'car_model'                  => array('Модель' => ''),
    'car_year'                   => array('Год выпуска' => ''),
    'car_nomer'                  => array('Госномер' => ''),
    'car_fuel'                   => array('Тип топлива' => ''),
    'car_type'                   => array('Тип автомобиля' => ''),
    'car_policy_company'         => array('Страховая компания' => ''),
    'car_policy_number'          => array('Номер страхового полиса' => ''),
    'car_policy_end_datetime'    => array('Дата окончания страховки' => ''),
    'car_tracker_phone'          => array('Номер маяка' => ''),
    'car_tracker_imei'           => array('IMEI маяка' => ''),
    'car_tracker_sms_on_engine'  => array('смс включить двигатель' => ''),
    'car_tracker_sms_off_engine' => array('смс выключить двигатель' => ''),
    'car_sts_nomer'              => array('Техпаспорт (серия и номер)' => ''),
    'car_sts_date'               => array('Дата выдачи техпаспорта' => ''),
    'car_pts_nomer'              => array('ПТС серия и номер' => ''),
    'car_pts_name'               => array('ПТС наименование ТС' => ''),
    'car_pts_power'              => array('ПТС мощность двигателя' => ''),
    'car_pts_vin'                => array('ПТС VIN-код' => ''),
    'car_city'                   => array('Город' => ''),
);
$handbook_clients_map = array(
    // Clients field            => array(field name (or Custom Field name) => empty (or Custom field id))
    'pf_client_userid'          => array('userid' => ''),
    'passport_number'           => array('Серия и номер паспорта' => ''),
    'passport_date'             => array('Когда выдан паспорт' => ''),
    'passport_vydan'            => array('Кем выдан паспорт' => ''),
    'passport_address_reg'      => array('Адрес регистрации' => ''),
    'passport_kod_podr'         => array('Код подразделения' => ''),
    'passport_birthplace'       => array('Место рождения' => ''),
    'client_phone1'             => array('mobilePhone' => ''),
    'client_email'              => array('email' => ''),
    'client_birthday'           => array('birthdate' => ''),
    'client_fname'              => array('name' => ''),
    'client_mname'              => array('midName' => ''),
    'client_lname'              => array('lastName' => ''),
    'prava_number'              => array('Серия и номер ВУ' => ''),
    'prava_date_begin'          => array('Дата выдачи ВУ' => ''),
    'prava_photo'               => array('Фотография ВУ' => ''),
    'have_private_car'          => array('Есть личный автомобиль' => ''),
    'private_car'               => array('Личный автомобиль' => ''),
    'passport_photo1_fullsize'  => array('Фото паспорта №1' => ''),
    'passport_photo2_fullsize'  => array('Фото паспорта №2' => ''),
);


// Поочередно работаем с каждой компанией.
	foreach ($companies as $kod => $company){
        $PF = new Planfix_API(array('apiKey' => $company['pf_apikey'], 'apiSecret' => $company['pf_privatekey']));
        $PF->setAccount($company['pf_account']);
        $PF->setUser(array('login' => $company['pf_login'], 'password' => $company['pf_password']));
        try{
            $response = $PF->authenticate();
        }
        catch (Exception $ex) {
            echo 'Log: Auth Error. Company #'.$company['id'].', planfix-account: "'.$company['pf_account'].'". '.$ex->getMessage();
            continue;
        }
        if($log_level > 0) echo 'Log: Successfully Authentication in Planfix. Account: '.$company['pf_account'].'<br>';
        //Successfully Authentication in Planfix (agent's account).
        //Check ID of Handbook "Автомобили". (pf_id_handbook_cars)
        if(isset($company['pf_id_handbook_cars'])){ //work with cars
        //Getting the structure of Handbook
            if($log_level > 1) echo 'Log: Try get structure of Handbook #'.$company['pf_id_handbook_cars'].'<br>';
            $method = 'handbook.getStructure';
            $params = array(
                'handbook' => array('id' => $company['pf_id_handbook_cars']));
            $struct_handbook = $PF->api($method, $params);
            if($struct_handbook['success'] != 1) echo 'Log: Error until getting the structure of Handbook #'.$company['pf_id_handbook_cars'];
            else{
                //Filling $handbook_cars_map array. Paste id of Handbook's fields
                foreach ($struct_handbook['data']['handbook']['fields']['field'] as $key => $field){
                    foreach ($handbook_cars_map as $dbField => &$handbookField){
                        if(key($handbookField) == $field['name']){
                            $handbookField[key($handbookField)] = $field['id'];
                            break;
                        }
                    }
                }
             //   echo '<pre>';
             //   print_r($handbook_cars_map);
             //   echo '</pre>';
            }
            if($log_level > 0) echo 'Log: Try select from Handbook #'.$company['pf_id_handbook_cars'].'. Try get Cars<br>';
            $pageNum = 1;
            $cars_planfix = array();
            while(true){
                $method = 'handbook.getRecords';
                $params = array(
                    'pageCurrent' => $pageNum,
                    'pageSize' => 100,
                    'handbook' => array('id' => $company['pf_id_handbook_cars']));
                $result = $PF->api($method, $params);
                if(!isset($result['data']['records']['record'])) break; //если записи в справочнике закончились - выйти.
                $cars_planfix = array_merge($cars_planfix, $result['data']['records']['record']);
                $pageNum++;
                usleep(1100000); //Planfix Restriction
            }
            //echo '<pre>';
            //print_r($cars_planfix);
            //echo '</pre>';
            if($log_level > 0) echo 'Log: End work with Handbook #'.$company['pf_id_handbook_cars']. ', Fetched '.count($cars_planfix).' records from Handbook.<br>';
            //Getting cars of company from db
            $carsDb = $pdo->getCollection('Cars', array('company_id' => $company['id']));
            //echo 'carsDb<pre>';
            //print_r($carsDb);
            //echo '</pre>';
            //print_r($modx->getPlaceholder('pdoTools.log'));

        //synchronize Cars data
            foreach($cars_planfix as $key_car => $car_planfix){
                $param = array(
                    'pf_handbook_key' => $car_planfix['key'], //key of car in handbook
                    'company_id' => $company['id'],
                    'pf_handbook_id' => $company['pf_id_handbook_cars'],
                );
                foreach($car_planfix['customData']['customValue'] as $key => $car_fields){
                    foreach($handbook_cars_map as $db_field => $pf_field){
                        if(is_array($pf_field) && in_array($car_fields['field']['id'],$pf_field)){
                            $param[$db_field] = !is_array($car_fields['value'])? $car_fields['value']: '';
                            break;
                        }
                    }
                }
                //echo '<pre>';
                //print_r($param);
                //echo '</pre>';
                //use class based processor for adding a car to DB
                $action = 'cars/create';
                $context = 'dev';
                //select needed processor (create or update)
                foreach($carsDb as $key => $carDb){
                    if(($carDb['company_id'] == $company['id'] && ($carDb['pf_handbook_id'] == $company['pf_id_handbook_cars']) && ($carDb['pf_handbook_key'] == $car_planfix['key']))){
                        $action = 'cars/update';
                        $param = array_merge($carDb,$param);
                        break;
                    }
                }
                if(!$response = $modx->runProcessor($action, $param
                    , array(
                        'processors_path' => $modx->getOption('pdotools_elements_path') . $context.'/processors/',
                    ))){
                    print "Не удалось выполнить процессор ".$action;
                    return;
                }
                if($log_level > 1) echo 'Log: Car is processed (action: "'.$action.'", car: "'.$param['pf_handbook_fulltitle'].'"<br>';
                //echo '<pre>';
                //print_r($response->getResponse());
                //echo '</pre>';

            }
            if($log_level > 0) echo 'Log: Cars - is completed.<br>';
        } //обработали автомобили.
//		Отдельный момент - машины, которых больше нет в Планфикс. Как с ними быть? Удалить с сайта? А связанные ресурсы?

        if(isset($company['pf_name_group_clients'])){ // work with clients
            if($log_level > 0) echo 'Log: Try get Clients.<br>';
                $pageNum = 1;
                $contacts_planfix = array();
                while(true) {
                    $method = 'contact.getList';
                    $params = array(
                        'target' => 'contact',
                        'pageCurrent' => $pageNum,
                        'pageSize' => 100,
                    );
                    $result = $PF->api($method, $params);
                    if ($result['success'] != 1){
                        echo 'Log: Error until getting clients';
                        break;
                    }
                    if(!isset($result['data']['contacts']['contact'])) break; //если записи в справочнике закончились - выйти.
                    $contacts_planfix = array_merge($contacts_planfix, $result['data']['contacts']['contact']);
                    $pageNum++;
                    usleep(1100000); //Planfix Restriction
                }
                //echo '<pre>';
                //print_r($contacts_planfix);
                //echo '</pre>';
                //define Clients Group ID
                $method = 'contact.getGroupList';
                $params = array(
                    'account' => $company['pf_account'],
                );
                $result = $PF->api($method, $params);
                if ($result['success'] != 1){
                    echo 'Log: Error until getting clients groups';
                    break;
                }
                $contactGroupId = -1;
                foreach($result['data']['contactGroups']['group'] as $key => $group){
                    if($group['name'] == $company['pf_name_group_clients']){
                        $contactGroupId = $group['id'];
                        break;
                    }
                }
            //synchronize Clients data
            foreach($contacts_planfix as $key_client => $client_planfix) {
                if($client_planfix['group']['id'] == $contactGroupId){
                    $param = array(
                        'company_id' => $company['id'],
                    );
                    if(is_array($client_planfix['customData']['customValue'])){
                        foreach ($client_planfix['customData']['customValue'] as $key => $client_fields) { //work with custom fields...
                            foreach ($handbook_clients_map as $db_field => $pf_field) {
                                if (is_array($pf_field) && array_key_exists($client_fields['field']['name'], $pf_field)) {
                                    $param[$db_field] = !is_array($client_fields['value']) ? $client_fields['value'] : '';
                                    break;
                                }
                            }
                        }
                    }
                    foreach ($client_planfix as $key => $client_fields) {
                        foreach ($handbook_clients_map as $db_field => $pf_field) {
                            if (is_array($pf_field) && array_key_exists($key, $pf_field)) {
                                $param[$db_field] = !is_array($client_fields) ? $client_fields : '';
                                break;
                            }
                        }
                    }
                    //echo '<pre>';
                    //print_r($param);
                    //echo '</pre>';
                    //use class based processor for adding a car to DB
                    $action = 'clients/create';
                    $context = 'dev';
                    //select needed processor (create or update)
                    $clientsDb = $pdo->getCollection('Clients', array('company_id' => $company['id']));
                    foreach($clientsDb as $key => $clientDb){
                        if(($clientDb['company_id'] == $company['id']) && ($clientDb['pf_client_userid'] == $param['pf_client_userid'])){
                            $action = 'clients/update';
                            $param = array_merge($clientDb,$param);
                            break;
                        }
                    }
                    if(!$response = $modx->runProcessor($action, $param
                        , array(
                            'processors_path' => $modx->getOption('pdotools_elements_path') . $context.'/processors/',
                        ))){
                        print "Не удалось выполнить процессор ".$action;
                        return;
                    }
                    if($log_level > 1) echo 'Log: Clients is processed (action: "'.$action.'", client: "'.$param['pf_client_userid'].'"<br>';
                }
            }
            if($log_level > 0) echo 'Log: Clients - is completed.<br>';
        } //клиенты
    } //компания
        unset($PF);


?>
</body>
</html>