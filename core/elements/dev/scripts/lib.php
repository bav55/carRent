<?php

/*
 * список вспомогательных функций для примеров
 */

/**
 *
 * @param SimpleXMLElement $requestXml
 * @param string $api_secret
 * @return string
 */
function make_sign($requestXml, $api_secret) {
	return md5(getStringForSign($requestXml) . $api_secret);
}

/**
 *
 * @param SimpleXMLElement $XmlElement
 * @return string
 */
function getStringForSign($XmlElement) {
	$result = '';
	$list = (array) $XmlElement;
	ksort($list);
	foreach ($list as $node) {
		if (is_array($node)) {
			$result .= implode('', array_map('getStringForSign', $node));
		} else if (is_object($node)) {
			$result .= getStringForSign($node);
		} else {
			$result .= (string) $node;
		}
	}
	return $result;
}

/**
 * Выполняет отправку запроса на сервер
 * @param string $api_server
 * @param string $api_key
 * @param SimpleXMLElement $requestXml
 * @return array  success - говорит об успешности выполнения http-запроса,
 * сам результат будет передан в response как объект класса SimpleXMLElement
 */
function apiRequest($api_server, $api_key, $requestXml) {
	$result = array('success' => true, 'response' => null);
	$ch = curl_init($api_server);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // не выводи ответ на stdout
	curl_setopt($ch, CURLOPT_HEADER, 1);   // получаем заголовки
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);   // устанавливам максимальное время ожидания
	// не проверять SSL сертификат
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	// не проверять Host SSL сертификата
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, $api_key . ':X');

	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $requestXml->asXML());

	$response = curl_exec($ch);
	$error = curl_error($ch);

	if ($error != "") {
		$result['success'] = false;
		$result['response'] = $error;
		return $result;
	}

	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$responseBody = substr($response, $header_size);

	try {
		$result['response'] = new SimpleXMLElement($responseBody);
	} catch (Exception $e) {
		// пришел поломанный XML
		$result['success'] = false;
		$result['response'] = 'broken xml';
	}
	return $result;
}

/**
 * проверяем, является ли ответ-ошибка
 * @param SimpleXMLElement $responseXml
 */
function parseAPIError($responseXml) {
	if ($responseXml['status'] == 'error') {
		// рассшифровку кода ошибки можно посмотреть http://goo.gl/GWa1c
		echo 'code :' . $responseXml->code;
		echo ' message: ' . $responseXml->message;
		exit();
	}
}

?>