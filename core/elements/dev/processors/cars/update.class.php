<?php
class CarsUpdateProcessor extends modObjectUpdateProcessor {
    public $classKey = 'Cars';
    public $objectType = 'Cars';

    public function beforeSet()
    {
        $pdo = $this->modx->getService('pdoFetch');
        $pdo->setConfig(array(
            'class' => 'Cities',
            'loadModels' => 'carRent',
            'select' => array(
                'Cities' => '*',
            ),
        ));
		//check city into DB and save ID of city
        $cities_db = $pdo->getCollection('Cities', array('city_name:LIKE' => '%' . $this->getProperty('car_city') . '%'));
        if (isset($cities_db[0]['id']) && $cities_db[0]['id'] <> '') {
            $this->setProperty('car_city', $cities_db[0]['id']);
        }
        return parent::beforeSet();
    }
}
return 'CarsUpdateProcessor';