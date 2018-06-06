<?
//=============================================================
            //Читаем справочник по одной записи...
            while(true){
                $method = 'handbook.getRecord';
                $params = array(
                    'key' => $recordId,
                    'handbook' => array('id' => $company['pf_id_handbook_cars']));
                $car_planfix = $PF->api($method, $params);
                if(!isset($car_planfix['data']['record'])) break; //если записи в справочнике закончились - выйти.
                if($log_level > 1) echo 'Handbook#'.$company['pf_id_handbook_cars'].', Record #'.$recordId.', Data: '.$car_planfix['data']['record']['customData']['customValue'][0]['text'].'<br>';
                //var_dump($car_planfix);

                $recordId++;
                usleep(1100000); //Planfix Restriction
            }
//=============================================================