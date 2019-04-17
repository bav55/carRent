<?php
/**
 * Created by PhpStorm.
 * User: bav55@yandex.ru
 * Date: 14.09.2018
 * Time: 11:55
 * MODX Revo Snippet. Prepare data for filter
  */
//1. Если страница города - tv:city - то подготовить options для городов и сделать selected
//2. Марка
//3. Модель
//4. Тип
//5. Дата начала (если не указана - то завтрашний день
//6. Дата окончания (если не указана - то завтрашний день+2 недели
$culture = $modx->getOption('cultureKey');
$modx->setPlaceholder('culture',$culture);
$lex = $culture == 'ru'? array(
    'allCities' => 'Все города',
    'allTypes' => 'Любой тип',
    'allMark' => 'Любая марка',

) : array(
    'allCities' => 'All cities',
);

$city_id = $modx->getOption('city', $scriptProperties, $_REQUEST['city']);
$city_pls = $modx->getOption('city_pls', $scriptProperties, 'cities');
$mark_model = $modx->getOption('mark_model', $scriptProperties, $_REQUEST['mark_model']);
$mark_model_pls = $modx->getOption('mark_model_pls', $scriptProperties, 'mark_models');

$type = $modx->getOption('type', $scriptProperties, $_REQUEST['type']);
$type_pls = $modx->getOption('type_pls', $scriptProperties, 'types');
$dateBegin = $modx->getOption('dateBegin', $scriptProperties, $_REQUEST['dateBegin']);
$dateBegin_pls = $modx->getOption('dateBegin_pls', $scriptProperties, 'dateBegin');
$dateEnd = $modx->getOption('dateEnd', $scriptProperties, $_REQUEST['dateEnd']);
$dateEnd_pls = $modx->getOption('dateEnd_pls', $scriptProperties, 'dateEnd');

$modx->addPackage('carRent', MODX_CORE_PATH . 'components/carrent/model/');
$pdo = $modx->getService('pdoFetch');
$pdo->setConfig(array(
    'loadModels' => 'carRent',
));
//cities
$cities = $pdo->getCollection('Cars', array(), array(
        'innerJoin' => array(
            'Cities' => array(
                'class' => 'Cities',
                'on' => 'Cars.car_city = Cities.id'
            )
        ),
        'select' => array(
            'Cars' => 'car_city',
            'Cities' => '*'
        ),
        'sortby' => array(
            'city_name' => 'ASC'
        ),
        'groupby' => 'car_city'
    )
);
//print_r($modx->getPlaceholder('pdoTools.log'));

$opt_cities = '<option value="0">' . $lex['allCities'] . '</option>';
$id = $modx->resource->id;
$tv_city = $modx->resource->getTVValue('city');
if($tv_city != '') //сео страница города, tv заполнена
    $city_id = $tv_city;
foreach ($cities as $key => $city){
    $selected = '';
    if($city_id == $city['id']) {
        $selected = 'selected="selected"';
    }
    $opt_cities .= '<option value="'.$city['id'].'" '. $selected .'>'.$city['city_name'].'</option>';
}

//mark and model
$markModels = $pdo->getCollection('Cars', array(), array(
        'select' => array(
            'Cars' => 'car_mark',
        ),
        'sortby' => array(
            'car_mark' => 'ASC'
        ),
        'groupby' => 'car_mark'
    )
);
$opt_markModels = '<option value="0">' . $lex['allMark'] . '</option>';
foreach ($markModels as $key => $markModel){
    $selected = '';
    if($mark_model == $markModel['car_mark']) {
        $selected = 'selected="selected"';
    }
    $opt_markModels .= '<option value="'.$markModel['car_mark'].'" '. $selected .'>'.$markModel['car_mark'].'</option>';
}

//types
$types = $pdo->getCollection('Cars', array(), array(
        'select' => array(
            'Cars' => 'car_type',
        ),
        'sortby' => array(
            'car_type' => 'ASC'
        ),
        'groupby' => 'car_type'
    )
);
$opt_types = '<option value="0">' . $lex['allTypes'] . '</option>';
foreach ($types as $key => $type_value){
    $selected = '';
    if($type == $type_value['car_type']) {
        $selected = 'selected="selected"';
    }
    $opt_types .= '<option value="'.$type_value['car_type'].'" '. $selected .'>'.$type_value['car_type'].'</option>';
}

//dateBegin
if($dateBegin == ''){
    $dateBegin = date("d.m.Y", strtotime("+1 day"));
}

//dateEnd
if($dateEnd == ''){
    $dateEnd = date("d.m.Y", strtotime("+14 day"));
}

$modx->setPlaceholder($city_pls, $opt_cities);
$modx->setPlaceholder($mark_model_pls, $opt_markModels);
$modx->setPlaceholder($type_pls, $opt_types);
$modx->setPlaceholder($dateBegin_pls, $dateBegin);
$modx->setPlaceholder($dateEnd_pls, $dateEnd);
return;