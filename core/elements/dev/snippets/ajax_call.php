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
        $dateBegin_value = new DateTime(substr($_POST['dateBegin'],0,33));
        $dateEnd_value = new DateTime(substr($_POST['dateEnd'],0,33));
        $projectRent_id     = -1; //ID of "Rent" project
        $projectRepair_id   = -1; //ID of "Repair" project
        $analiticBooking_id = -1; //ID of "Car Booking" analitic
        $cf_auto_id         = -1; //ID of custom field "Car" from task's template "Rent"
        $dateBegin_id       = -1; //ID of custom field of analitic (date_begin)
        $dateEnd_id         = -1; //ID of custom field of analitic (date_end)
        $statusSet_id       = -1;
        $status_id          = -1;
        $worker_id          = -1;
        $workerGroup_id     = -1;

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

        //2. get IDs of custom fields from task
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
            $statusSet_id = $result['data']['tasks']['task']['statusSet'];
            if(isset($result['data']['tasks']['task']['customData']['customValue']['field'])){//if only one custom field in the task
                if($result['data']['tasks']['task']['customData']['customValue']['field']['name'] == $company['pf_name_customField_car'])
                    $cf_auto_id = $result['data']['tasks']['task']['customData']['customValue']['field']['id'];
                if($result['data']['tasks']['task']['customData']['customValue']['field']['name'] == $company['pf_name_customField_dateBegin'])
                    $cf_dateBegin_id = $result['data']['tasks']['task']['customData']['customValue']['field']['id'];
                if($result['data']['tasks']['task']['customData']['customValue']['field']['name'] == $company['pf_name_customField_dateEnd'])
                    $cf_dateEnd_id = $result['data']['tasks']['task']['customData']['customValue']['field']['id'];

            }
            else{ //if more than one custom fields in the task
                foreach($result['data']['tasks']['task']['customData']['customValue'] as $key => $cField){
                    if($cField['field']['name'] == $company['pf_name_customField_car'])     {$cf_auto_id = $cField['field']['id'];}
                    if($cField['field']['name'] == $company['pf_name_customField_dateBegin'])     {$cf_dateBegin_id = $cField['field']['id'];}
                    if($cField['field']['name'] == $company['pf_name_customField_dateEnd'])       {$cf_dateEnd_id = $cField['field']['id'];}
                }
                if($cf_auto_id == -1) echo 'Log: Error. Not exist ID of custom fields from task #'.$result['tasks']['task']['id'];
            }
            //3. get status_id (statusSet)
            $method = 'taskStatus.getListOfSet';
            $params = array(
                'taskStatusSet' =>
                    array('id' => $statusSet_id),
            );
            $result = $PF->api($method, $params);
            if($result['success'] != 1) echo 'Log: Error until get statusSet from Planfix.';
            else{
                foreach($result['data']['taskStatuses']['taskStatus'] as $key => $status){
                    if($status['name'] == $company['pf_taskStatus_name']){
                        $status_id = $status['id'];
                        break;
                    }
                }
            }
            //4. get user_id (worker)
            $method = 'user.getList';
            $params = array(
                'pageSize' => 100,
                'pageCurrent' => 1,
                'status' => 'ACTIVE'
            );
            $result = $PF->api($method, $params);
            if($result['success'] != 1) echo 'Log: Error until get list of users from Planfix.';
            else{
                foreach($result['data']['users']['user'] as $key => $user){
                    if($user['name'].' '.$user['lastName'] == $company['pf_worker_name']){
                        $worker_id = $user['id'];
                        break;
                    }
                }
            }

        }

            //5. add a new task to Planfix
        $method = 'task.add';
        $params = array(
            'task' => array(
                //'account' => $company['pf_account'],
                'template' => $company['pf_taskTemplate_id'],
                'project' => array('id' =>  $projectRent_id),
                'importance' => 'AVERAGE',
                'status' => $status_id,
                'customData' => array(
                    'customValue' => array(
                        0 => array(
                            'id' => $cf_auto_id,
                            'value' => $car['pf_handbook_key']
                        ),
                        1 => array(
                            'id' => $cf_dateBegin_id,
                            'value' => $dateBegin_value->format('d-m-Y H:i')
                        ),
                        2 => array(
                            'id' => $cf_dateEnd_id,
                            'value' => $dateEnd_value->format('d-m-Y H:i')
                        ),
                    ),
                ),
                'workers' => array( 'users' => array('id' => $worker_id)),
                'owner' => array('id' => $_POST['selectClient']),
                'title' => 'Аренда '.$car['pf_handbook_fulltitle'].' - '.$client['client_fname'].' '.$client['client_lname'],
            ),
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until add a Task. ';
        else{
            $task_id = $result['data']['task']['id'];
            //6. add new CarsBooking
            $carBooking = array();
            $carBooking['datetime_begin'] = $dateBegin_value->format('d-m-Y H:i');
            $carBooking['datetime_end'] = $dateEnd_value->format('d-m-Y H:i');
            $carBooking['pf_task_id'] = $task_id;
            //$carBooking['car_id'] = $car['pf_handbook_key'];
            $carBooking['car_id'] = $car['id'];
            $carBooking['company_id'] = $company_id;
            $carBooking['client_id'] = $_POST['selectClient'];
            $context = 'dev';
            $action = 'carsBooking/create';
            if(!$response = $modx->runProcessor($action, $carBooking
                , array(
                    'processors_path' => $modx->getOption('pdotools_elements_path') . $context.'/processors/',
                ))){
                print "Не удалось выполнить процессор ".$action;
                return;
            }
            $res = $result;
        }
		break;
	case 'modifyBooking':
		echo 'modifyBooking';
        $company_id = $modx->getOption('company_id', $_REQUEST, '');
        $user_id = $modx->getOption('user_id', $_REQUEST, '');
        $task_id = $modx->getOption('task_id', $_REQUEST, '');

        $modx->addPackage('carRent', MODX_CORE_PATH . 'components/carrent/model/');
        $pdo = $modx->getService('pdoFetch');
        $pdo->setConfig(array(
            'class' => 'CarsBooking',
            'loadModels' => 'carRent',
        ));
        $user = $pdo->getArray('modUserProfile', array('internalKey' => $user_id));
        if($user['company_id'] != $company_id) return(0);
        $company = $pdo->getArray('Company', $company_id);
        $task = $pdo->getArray('CarsBooking', array('pf_task_id' => $task_id));

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
        $cf_dateBegin_id       = -1; //ID of custom field of analitic (date_begin)
        $cf_dateEnd_id         = -1; //ID of custom field of analitic (date_end)
        //1. get IDs of custom fields
        $method = 'task.get';
        $params = array(
            'task' => array(
                'id' => $task_id,
            ),
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until task (#'.$task_id.') update in Planfix.';
        else {
            foreach($result['data']['task']['customData']['customValue'] as $key => $cField){
                if($cField['field']['name'] == $company['pf_name_customField_dateBegin'])     {$cf_dateBegin_id = $cField['field']['id'];}
                if($cField['field']['name'] == $company['pf_name_customField_dateEnd'])       {$cf_dateEnd_id = $cField['field']['id'];}
            }
        }
        $method = 'task.update';
        $params = array(
            'task' => array(
                'id' => $task_id,
                'customData' => array(
                    'customValue' => array(
                        0 => array(
                            'id' => $cf_dateBegin_id,
                            'value' => $dateBegin_value->format('d-m-Y H:i')
                        ),
                        1 => array(
                            'id' => $cf_dateEnd_id,
                            'value' => $dateEnd_value->format('d-m-Y H:i')
                        ),
                    ),
                ),
            ),
        );
        $result = $PF->api($method, $params);
        if($result['success'] != 1) echo 'Log: Error until task (#'.$task_id.') update in Planfix.';
        else {
            //все прошло успешно, задача в Планфиксе изенилась. Теперь нужно изменить CarsBooking для этой задачи
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
		break;
	// Сюда потом добавлять новые методы
}

// Если у нас есть, что отдать на запрос - отдаем и прерываем работу парсера MODX
if (!empty($res)) {
	die($res);
}