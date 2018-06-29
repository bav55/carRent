<?php
// Откликаться будет ТОЛЬКО на ajax запросы
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {return;}

// Сниппет будет обрабатывать не один вид запросов, поэтому работать будем по запрашиваемому действию
// Если в массиве POST нет действия - выход
if (empty($_POST['action'])) {return;}

// А если есть - работаем
$res = '';
switch ($_POST['action']) {
	case 'createBooking':
		//print_r($_POST,1);
        $company_id = $modx->getOption('company_id', $_REQUEST, '');
        $user_id = $modx->getOption('user_id', $_REQUEST, '');

        $modx->addPackage('carRent', MODX_CORE_PATH . 'components/carrent/model/');
        $pdo = $modx->getService('pdoFetch');
        $pdo->setConfig(array(
            'class' => 'CarsBooking',
            'loadModels' => 'carRent',
        ));
        $user = $pdo->getArray('modUserProfile', array('internalKey' => $user_id));
        if($user['company_id'] != $company_id) return(0);
        $company = $pdo->getArray('Company', $company_id);
        $car = $pdo->getArray('Cars', array('pf_handbook_key' => $_POST['selectCar'], 'company_id' => $company_id));
        $client = $pdo->getArray('Clients', array('pf_client_userid' => $_POST['selectClient']));
        //including Planfix api client
        $filepath = 'scripts/Planfix_API.php';
        $context = $modx->context->get('key');
        $pf_file = $modx->getOption('pdotools_elements_path') . "$context/$filepath";
        require $pf_file;

        $PF = new Planfix_API(array('apiKey' => $company['pf_apikey'], 'apiSecret' => $company['pf_privatekey']));
        $PF->setAccount($company['pf_account']);
        $PF->setUser(array('login' => $company['pf_login'], 'password' => $company['pf_password']));
        try{
            $response = $PF->authenticate();
        }
        catch (Exception $ex) {
            echo 'Log: Auth Error. Company #'.$company['id'].', planfix-account: "'.$company['pf_account'].'". '.$ex->getMessage();
            die($ex);
        }
        //get required ids from Planfix
        $projectRent_id     = -1; //ID of "Rent" project
        $projectRepair_id   = -1; //ID of "Repair" project
        $analiticBooking_id = -1; //ID of "Car Booking" analitic
        $cf_auto_id         = -1; //ID of custom field "Car" from task's template "Rent"
        $dateBegin_id       = -1; //ID of custom field of analitic (date_begin)
        $dateEnd_id         = -1; //ID of custom field of analitic (date_end)

        //1. get ID of projects
        $method = 'project.getList';
        $params = array(
            'account' => $company['pf_account'],
            'pageSize' => 100,
            'pageCurrent' => 1
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until get Projects from Planfix.';
        else{
            foreach($result['data']['projects']['project'] as $key => $project){
                if($project['title'] == $company['pf_name_project_rent']){$projectRent_id = $project['id']; continue;}
                if($project['title'] == $company['pf_name_project_repair']){$projectRepair_id = $project['id']; continue;}
            }
            if(($projectRent_id == -1) || ($projectRepair_id == -1)) echo 'Log: Error. Not exist ID of Project(s) from Planfix. (projectRent_id = '.$projectRent_id.'), (projectRepair_id = '.$projectRepair_id.')';
        }
        //2. get ID of analitic and fields
        $method = 'analitic.getList';
        $params = array(
            'account' => $company['pf_account']
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until get Analitic from Planfix.';
        else {
            foreach ($result['data']['analitics']['analitic'] as $key => $analitic) {
                if ($analitic['name'] == $company['pf_name_analitic_booking']) {
                    $analiticBooking_id = $analitic['id'];
                    //get options by this analitic
                    if ($log_level > 1) echo 'Log: Try get options for Analitic "' . $company['pf_name_analitic_booking'] . '" from Planfix.<br>';
                    $method = 'analitic.getOptions';
                    $params = array(
                        'analitic' => array('id' => $analiticBooking_id)
                    );
                    $result_options = $PF->api($method, $params);
                    if ($result_options['success'] != 1) echo 'Log: Error until get Analitic from Planfix.';
                    else {
                        $dateBegin_id = $result_options['data']['analitic']['fields']['field'][0]['id'];
                        $dateEnd_id = $result_options['data']['analitic']['fields']['field'][1]['id'];
                    }
                    if (($dateBegin_id == -1) || ($dateEnd_id == -1)) echo 'Log: Error get options for analitic (dateBegin and dateEnd).<br>';
                    break;
                }
            }
        }
        //3. get IDs of custom fields from task
        $method = 'task.getList';
        $params = array(
            'pageSize' => 1,
            'pageCurrent' => 1,
            'project' => array('id' => $projectRent_id),
            'target' => 'all'
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until get Task (Rent Project) for getting IDx of Custom Fields from Planfix.';
        else{
            if(isset($result['data']['tasks']['task']['customData']['customValue']['field'])){//if only one custom field in the task
                if($result['data']['tasks']['task']['customData']['customValue']['field']['name'] == $company['pf_name_customField_car'])
                    $cf_auto_id = $result['data']['tasks']['task']['customData']['customValue']['field']['id'];
            }
            else{ //if more than one custom fields in the task
                foreach($result['data']['tasks']['task']['customData']['customValue'] as $key => $cField){
                    if($cField['field']['name'] == $company['pf_name_customField_car'])     {$cf_auto_id = $cField['field']['id'];}
                }
                if($cf_auto_id == -1) echo 'Log: Error. Not exist ID of custom fields from task #'.$result['tasks']['task']['id'];
            }
        }

        //add a new task to Planfix
        $method = 'task.add';
        $params = array(
            'task' => array(
                //'account' => $company['pf_account'],
                'template' => $company['pf_taskTemplate_id'],
                'project' => array('id' =>  $projectRent_id),
                'importance' => 'AVERAGE',
                'status' => 1,
                'customData' => array(
                    'customValue' => array('id' => $cf_auto_id, 'value' => $car['pf_handbook_key']),
                ),
                'workers' => array( 'users' => array('id' => 287310)),
                'owner' => array('id' => $_POST['selectClient']),
                'title' => 'Аренда '.$car['pf_handbook_fulltitle'].' - '.$client['client_fname'].' '.$client['client_lname'],
            ),
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until add a Task. ';
        else{
            //Задача создана. ID есть. Добавим действие с аналитикой
            //convert dates from js to php format
            $dateBegin_value = new DateTime(substr($_POST['dateBegin'],0,33));
            $dateEnd_value = new DateTime(substr($_POST['dateEnd'],0,33));
            $taskId = $result['data']['task']['id'];
            $method = 'action.add';
            $params = array(
                'action' => array(
                    'task' => array('id' =>  $taskId),
                    'analitics' => array(
                        'analitic' => array(
                            'id' => $analiticBooking_id,
                            'analiticData' => array(
                                'itemData' => array(
                                    0 => array(
                                    'fieldId' => $dateBegin_id,
                                    'value' => $dateBegin_value->format('d-m-Y H:i'),
                                    ),
                                    1 => array(
                                        'fieldId' => $dateEnd_id,
                                        'value' => $dateEnd_value->format('d-m-Y H:i'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            );
            $result = $PF->api($method, $params);
            if($result['success'] != 1) echo 'Log: Error until add a Task. ';
            else{
                //Задача создана. Действие с аналитикой добавлено.
                $taskId = $result['data']['task']['id'];

            }
        }
		//запускаем сниппет для создания задачи на бронирование, предварительно получив исходные данные из $_POST
		//получаем ответ от Планфикса, в случае успеха берем task_id,
        //затем, добавляем к созданной задаче действие с аналитикой, получаем в ответ action_id
        // и выполняем процессор carsBooking/create
		//после этого на странице надо запустить снова функцию DrawVisualisation чтобы отрефрешить timeline
		//еще наверно надо как-то запоминать масштаб и дату, на которой timeline был до отправки запроса
		$res = 'Hello World!';
		break;
	case 'modifyBooking':
		echo 'modifyBooking';
        $company_id = $modx->getOption('company_id', $_REQUEST, '');
        $user_id = $modx->getOption('user_id', $_REQUEST, '');

        $modx->addPackage('carRent', MODX_CORE_PATH . 'components/carrent/model/');
        $pdo = $modx->getService('pdoFetch');
        $pdo->setConfig(array(
            'class' => 'CarsBooking',
            'loadModels' => 'carRent',
        ));
        $user = $pdo->getArray('modUserProfile', array('internalKey' => $user_id));
        if($user['company_id'] != $company_id) return(0);
        $company = $pdo->getArray('Company', $company_id);

        //including Planfix api client
        $filepath = 'scripts/Planfix_API.php';
        $context = $modx->context->get('key');
        $pf_file = $modx->getOption('pdotools_elements_path') . "$context/$filepath";
        require $pf_file;

        $PF = new Planfix_API(array('apiKey' => $company['pf_apikey'], 'apiSecret' => $company['pf_privatekey']));
        $PF->setAccount($company['pf_account']);
        $PF->setUser(array('login' => $company['pf_login'], 'password' => $company['pf_password']));
        try{
            $response = $PF->authenticate();
        }
        catch (Exception $ex) {
            echo 'Log: Auth Error. Company #'.$company['id'].', planfix-account: "'.$company['pf_account'].'". '.$ex->getMessage();
            die($ex);
        }
        //convert dates from js to php format
        $dateBegin_value = new DateTime(substr($_POST['dateBegin'],0,33));
        $dateEnd_value = new DateTime(substr($_POST['dateEnd'],0,33));
        //get required IDs from Planfix
        $analiticBooking_id = -1; //ID of "Car Booking" analitic
        $dateBegin_id       = -1; //ID of custom field of analitic (date_begin)
        $dateEnd_id         = -1; //ID of custom field of analitic (date_end)
        //1. get ID of analitic and fields
        $method = 'analitic.getList';
        $params = array(
            'account' => $company['pf_account']
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until get Analitic from Planfix.';
        else {
            foreach ($result['data']['analitics']['analitic'] as $key => $analitic) {
                if ($analitic['name'] == $company['pf_name_analitic_booking']) {
                    $analiticBooking_id = $analitic['id'];
                    //get options by this analitic
                    if ($log_level > 1) echo 'Log: Try get options for Analitic "' . $company['pf_name_analitic_booking'] . '" from Planfix.<br>';
                    $method = 'analitic.getOptions';
                    $params = array(
                        'analitic' => array('id' => $analiticBooking_id)
                    );
                    $result_options = $PF->api($method, $params);
                    if ($result_options['success'] != 1) echo 'Log: Error until get Analitic from Planfix.';
                    else {
                        $dateBegin_id = $result_options['data']['analitic']['fields']['field'][0]['id'];
                        $dateEnd_id = $result_options['data']['analitic']['fields']['field'][1]['id'];
                    }
                    if (($dateBegin_id == -1) || ($dateEnd_id == -1)) echo 'Log: Error get options for analitic (dateBegin and dateEnd).<br>';
                    break;
                }
            }
        }
        //get the action from Planfix and get key of analitic's string
        $method = 'action.get';
        $params = array(
                'action' => array(
                    'id' => $_POST['action_id']
                ),
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until get Analitic from Planfix.';
        else {
            $analiticKey = $result['data']['action']['analitics']['analitic']['key'];
        }
        $method = 'action.update';
        $params = array(
            'action' => array(
                'id' => $_POST['action_id'],
                'analitics' => array(
                    'analitic' => array(
                        'id' => $analiticBooking_id,
                        'analiticData' => array(
                            'key' => $analiticKey,
                            'itemData' => array(
                                'fieldId' => $dateBegin_id,
                                'value' => $dateBegin_value->format('d-m-Y H:i'),
                                )
                            )
                        ),
                    ),
                ),
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until update Analitic (DateBegin) in Planfix.';
        else {
            $method = 'action.update';
            $params = array(
                'action' => array(
                    'id' => $_POST['action_id'],
                    'analitics' => array(
                        'analitic' => array(
                            'id' => $analiticBooking_id,
                            'analiticData' => array(
                                'key' => $analiticKey,
                                'itemData' => array(
                                    'fieldId' => $dateEnd_id,
                                    'value' => $dateEnd_value->format('d-m-Y H:i')
                                )
                            )
                        ),
                    ),
                ),
            );
            $result = $PF->api($method, $params);
            if($result['success'] != 1) echo 'Log: Error until update Analitic (DateEnd) in Planfix.';
            else {
                //все прошло успешно, аналитика в Планфиксе изенилась. Теперь нужно изменить CarsBooking для этой задачи
                $carBooking = $pdo->getArray('CarsBooking', array('id' => $_POST['id']));
                $carBooking['datetime_begin'] = $dateBegin_value->format('d-m-Y H:i');
                $carBooking['datetime_end'] = $dateEnd_value->format('d-m-Y H:i');
                $context = 'dev';
                $action = 'carsBooking/update';
                if(!$response = $modx->runProcessor($action, $carBooking
                    , array(
                        'processors_path' => $modx->getOption('pdotools_elements_path') . $context.'/processors/',
                    ))){
                    print "Не удалось выполнить процессор ".$action;
                    return;
                }
                $res = $result;
            }
        }
		break;
	// А вот сюда потом добавлять новые методы
}

// Если у нас есть, что отдать на запрос - отдаем и прерываем работу парсера MODX
if (!empty($res)) {
	die($res);
}