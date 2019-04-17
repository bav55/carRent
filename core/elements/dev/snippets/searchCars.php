<?php
/**
 * Created by PhpStorm.
 * User: bav55
 * Date: 17.09.2018
 * Time: 19:49
 *
 * SELECT c.*, cb.* FROM `9b55fB_agg_cars` c
left join `9b55fB_agg_cars_booking` cb on c.id = cb.car_id
WHERE
('2018-07-04 00:05:00' < cb.datetime_begin AND '2018-07-04 08:00:00' < cb.datetime_begin)
OR
('2018-07-04 00:05:00' > cb.datetime_end AND '2018-07-04 08:00:00' > cb.datetime_end)
or (cb.datetime_begin is null and cb.datetime_end is null)
 *
 */
$culture = $modx->getOption('cultureKey');
$ctx = $modx->resource->context_key;
$modx->setPlaceholder('culture',$culture);

$city_id = $modx->getOption('city', $_REQUEST);
//$mark_model = $modx->getOption('mark_model', $_REQUEST);
$type = $modx->getOption('type', $_REQUEST);
$dateBegin = $modx->getOption('dateBegin', $_REQUEST);
$dateEnd = $modx->getOption('dateEnd', $_REQUEST);
$limit = $modx->getOption('limit', $scriptProperties, $_REQUEST['limit']);
$offset = $modx->getOption('offset', $scriptProperties, $_REQUEST['offset']);
$totalVar = $modx->getOption('totalVar', $scriptProperties, 'total');
$tpl = $modx->getOption('tpl', $scriptProperties, 'cars.item.tpl');
$tpl = '@FILE '.$ctx.'/chunks/'.$tpl;
$whereStr = '1 = 1 ';
if($city_id == ''){ //если установлена tv "city"
    $tv_city = $modx->resource->getTVValue('city');
    if($tv_city != '') $city_id = $tv_city;
}
$whereStr .= ' AND Cars.car_city = '.$city_id.' ';
if($type != '0') $whereStr .= ' AND Cars.car_type LIKE "%'.strip_tags($type).'%" ';
//if($mark_model != '0') $whereStr .= ' AND Cars.car_mark LIKE "%'.strip_tags($mark_model).'%" ';
$dateFrom = ($dateBegin == '')? $dateBegin = date("Y-m-d H:i:s", strtotime("+1 day"))
                              : DateTime::createFromFormat('d.m.Y H:i', $dateBegin)->format('Y-m-d H:i:s');
$dateTo = ($dateEnd == '')? $dateEnd = date("Y-m-d H:i:s", strtotime("+14 day"))
                          :DateTime::createFromFormat('d.m.Y H:i', $dateEnd)->format('Y-m-d H:i:s');
//$whereStr = '("'.$dateFrom.'" < CarsBooking.datetime_begin AND "'.$dateTo.'" < CarsBooking.datetime_begin) OR ("'.$dateFrom.'" > CarsBooking.datetime_end AND "'.$dateTo.'" > CarsBooking.datetime_end) or (CarsBooking.datetime_begin is null and CarsBooking.datetime_end is null)';
$whereStr .= 'and Cars.id not in (select car_id from `9b55fB_agg_cars_booking` CarsBooking where ("'.$dateFrom.'" > CarsBooking.datetime_begin AND "'.$dateTo.'" < CarsBooking.datetime_end) OR ("'.$dateFrom.'" between CarsBooking.datetime_begin and CarsBooking.datetime_end) OR ("'.$dateTo.'" between CarsBooking.datetime_begin and CarsBooking.datetime_end) OR (CarsBooking.datetime_begin is null and CarsBooking.datetime_end is null))';
$modx->addPackage('carRent', MODX_CORE_PATH . 'components/carrent/model/');
$pdo = $modx->getService('pdoFetch');
$pdo->setConfig(array_merge( $scriptProperties,array(
    'loadModels' => 'carRent',
    'class' => 'Cars',
    'leftJoin' => array(
        'CarsBooking' => array(
            'class' => 'CarsBooking',
            'on' => 'Cars.id = CarsBooking.car_id'
        )
    ),
    'select' => array(
        'Cars' => '*',
        'CarsBooking' => '*'
    ),
    'where' => $whereStr,
    'groupby' => 'Cars.id',
)));
/*
$pdo->makeQuery();
$pdo->addJoins();
$pdo->addGrouping();
$pdo->addSelects();
$pdo->addWhere();
$query = $pdo->prepareQuery();
$query->execute();
$cars = $query->fetchAll(PDO::FETCH_ASSOC);
$modx->setPlaceholder('sql', $query->queryString);
$modx->setPlaceholder($totalVar, count($cars));
$result = '';
foreach($cars as $key => $car){
    $result .= $pdo->getChunk($tpl,$car);
}*/
$result = $pdo->run();
//$modx->setPlaceholder('cars_list', $result);
echo $result;
