<?
function importParams($requestXml, $params) {
	foreach($params as $key => $val) {
		if (is_array($val)) {
			if(is_numeric($key)){
				echo 'key is numeric'; //несколько однотипных значений параметров
				//$requestXml = new SimpleXMLElement("<$val/>");
                /*foreach($val as $key2 => $val2) {
                    if(!is_array($val2)){
                     $requestXml->addChild($key2, $val2);
                    }
                    else{ //val2 is array
                         importParams($requestXml->$key, array($key2 => $val2));
                    }
                }
                */
			}
			else{ //одно значение параметра
                if(array_key_exists(0, $val)){ //есть несколько значений внутри вложенного массива
                    foreach($val as $key3 => $val3){
                        $requestXml->$key = new SimpleXMLElement("<$key/>");
                        $requestXml->addChild($key, importParams($requestXml->$key, $val3));
                    }
                }
                else{
				    $requestXml->$key = new SimpleXMLElement("<$key/>");
                }
			}

			foreach($val as $key2 => $val2) {
				if (is_array($val2)) {
					if(!is_numeric($key2)){
						importParams($requestXml->$key, array($key2 => $val2));
					}
					else{
                        $requestXml->$key = new SimpleXMLElement("<$key/>");
					    foreach($val2 as $key3 => $val3){
                            $requestXml->$key->addChild($key3,$val3);
                        }
					}
				} else {
					if(!is_numeric($key)){ $requestXml->$key->addChild($key2, $val2);
					}
				}
			}
		} else {
			$requestXml->addChild($key, $val);
		}
	}
	return $requestXml;
}
$params = array(
	'action' => array(
		'task' => array(
			'id' => 14958098
		),
		'analitics' => array(
			'analitic' => array(
				'id' => 29640,
				'analiticData' => array(
					'key' => 17,
					'itemData' => array(
						0 => array(
							'fieldId' => 110950,
							'value' => "28-06-2018 12:00"
						),
						1 => array(
							'fieldId' => 110951,
							'value' => "30-06-2018 12:00"
						),
					),
				),
			),
		),
	),
);
$requestXml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><request></request>');
$result1 = importParams($requestXml, $params);
$params2 = array(
	'action' => array(
		'task' => array(
			'id' => 14958098
		),
		'analitics' => array(
			0 => array(
				'analitic' => array(
					'id' => 29640,
					'analiticData' => array(
						'key' => 17,
						'itemData' => array(
							0 => array(
								'fieldId' => 110950,
								'value' => "28-06-2018 12:00"
							),
							1 => array(
								'fieldId' => 110951,
								'value' => "30-06-2018 12:00"
							),
						),
					),
				),
			),
			1 => array(
				'analitic' => array(
					'id' => 29641,
					'analiticData' => array(
						'key' => 19,
						'itemData' => array(
							0 => array(
								'fieldId' => 110950,
								'value' => "28-07-2018 12:00"
							),
							1 => array(
								'fieldId' => 110951,
								'value' => "30-07-2018 12:00"
							),
						),
					),
				),
			),
		),
	),
);
