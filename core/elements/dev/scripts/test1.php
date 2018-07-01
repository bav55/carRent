<?
function importParams($requestXml, $params) {
    foreach($params as $key => $val) {
        if (is_array($val)) {
            if (array_key_exists(0, $val)) {
                echo 'wait';
                foreach ($val as $key_num => $val_num) {
                    $newElem[$key_num] = $requestXml->addChild($key);
                    foreach ($val_num as $key2 => $val2) {
                        if(is_array($val2)){
                            if (is_array($val2)) {
                                importParams($newElem[$key_num]->$key2, array($key2 => $val2));
                            } else {
                                $newElem[$key_num]->$key2->addChild($key2, $val2);
                            }
                            echo 'is array';
                        }
                        else{
                            $newElem[$key_num]->addChild($key2, $val2);
                        }
                    }
                }
                //echo $requestXml->asXML();
                return $requestXml;
            }
                $requestXml->$key = new SimpleXMLElement("<$key/>");
                foreach ($val as $key2 => $val2) {
                    if (is_array($val2)) {
                        importParams($requestXml->$key, array($key2 => $val2));
                    } else {
                        $requestXml->$key->addChild($key2, $val2);
                    }
                }
        }
        else {
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
echo '#1\n';
$result1 = importParams($requestXml, $params);
echo '<pre>'.$result1->asXML().'</pre>';
$params2 = array(
	'action' => array(
		'task' => array(
			'id' => 14958098
		),
		'analitics' => array(
				'analitic' => array(
				    0 => array(
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
                1 => array(
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
echo '#2\n';
//$result2 = importParams($requestXml, $params2);
//echo '<pre>'.$result2->asXML().'</pre>';