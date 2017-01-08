<?php

/**
 * @author James V. Kotoff
 * @copyright 2016
 */

/* Настройки */

$sLibFolder = 'lib';
$sScenarioFolder = 'scenario';

/* Конец настроек */

require_once(dirname(__FILE__) . '/core/init.php');

require_once(dirname(__FILE__) . '/core/common.php');


if (!Core_Auth::logged())
{
	return sendContent(array('Доступ запрещен'), array('Content-Type: text/plain; charset=utf-8'), 403);
}

$sLibFolder .= DIRECTORY_SEPARATOR;
$sScenarioFolder .= DIRECTORY_SEPARATOR;
$sScriptFolder = (end(explode(DIRECTORY_SEPARATOR, dirname(__FILE__)))) . DIRECTORY_SEPARATOR ;

$sScriptUrl = 'http://' . $_SERVER['HTTP_HOST'] . DIRECTORY_SEPARATOR . $sScriptFolder;

$sData = Core_Array::getPost('data');
$sCharset = strval(Core_Array::getPost('charset', 'UTF-8'));
$iTotalTime = intval(Core_Array::getPost('totalTime', -1));

$sRequestedSite = strval(Core_Array::getRequest('siteid', ''));
$aSites = getScenarios($sScenarioFolder, $sScriptFolder);
$bIsSiteSupported = in_array($sRequestedSite, array_keys($aSites));

// запрос сохранения данных для неподдерживаемого сайта
if ($sData && !$bIsSiteSupported)
{
	return sendContent(array(), array("Location: {$sScriptUrl}"), 302);
}

// формированиe Excel
if ($sData)
{
	return sendExcelReport($sRequestedSite, $sData, $sCharset, $iTotalTime);
}

// список букмарклетов
if (!$sRequestedSite)
{
	return sendBookmarkletsList($aSites, $sScriptUrl);
}

// отправка клиентских скриптов
return sendScripts($sScriptFolder, $sLibFolder, $sScenarioFolder, $sRequestedSite, $bIsSiteSupported, $sScriptUrl);
