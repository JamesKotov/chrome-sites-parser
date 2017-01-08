<?php

/**
 * @author James V. Kotoff
 * @copyright 2016
 */


function sendContent($aContentParts = array(), $aHeaders = array(), $iStatus = 200)
{
	$aDefaultHeaders = array(
		'Expires: 0',
		'Cache-Control: no-store, no-cache, must-revalidate, max-age=0',
		'Pragma: no-cache'
	);

	$aAllHeaders = array_merge($aDefaultHeaders, $aHeaders);

	$oCore_Response = new Core_Response();

	foreach($aAllHeaders as $sHeader)
	{
		$aHeaderParts = explode(':', $sHeader);
		$oCore_Response->header($aHeaderParts[0], $aHeaderParts[1]);
	}
	$sContent = implode('', $aContentParts);

	$oCore_Response
		->status(intval($iStatus))
		->header('Content-Length', strlen($sContent))
		->body($sContent)
		->sendHeaders()
		->showBody();

	exit();
}

function makeExcelLocalReport($bAddProducerToReport = FALSE)
{
	$aCellWidth = array(
		'producer_name' => '108',
		'group_name' => '129',
		'name' => '341',
		'marking' => '280',
		'values' => '196'
	);

	$iShopId = 1;
	$iPropertyId = 61;
	$iListId = 191;

	$iTopShift = 3;
	$sListName = 'List1';


	$date = date('Y-m-d H-i-s');
	$aDataFields = Array('group_name', 'name', 'marking', 'values');
	$iFiltersWidth = 1;

	$oQueryBuilder = Core_QueryBuilder::select('shop_items.id')
		->select(array('shop_groups.name', 'group_name'))
		->select('shop_items.name')
		->select('shop_items.marking')
		->select('property_value_ints.property_id')
		->select(array(Core_Querybuilder::expression('GROUP_CONCAT(DISTINCT `list_items`.`value` ORDER BY `list_items`.`value` ASC SEPARATOR \',\')'), 'values'))
		->from('shop_items')
		->join('shop_groups', 'shop_groups.id', '=', 'shop_items.shop_group_id')
		->join('property_value_ints', 'property_value_ints.entity_id', '=', 'shop_items.id')
		->join('list_items', 'list_items.id', '=', 'property_value_ints.value')
		->where('shop_items.shop_id', '=', $iShopId)
		->where('shop_items.active', '=', '1')
		->where('shop_items.deleted', '=', '0')
		->where('shop_groups.active', '=', '1')
		->where('shop_groups.deleted', '=', '0')
		->where('list_items.list_id', '=', $iListId)
		->where('list_items.active', '=', '1')
		->where('list_items.deleted', '=', '0')
		->groupBy('shop_items.id')
		->having('property_value_ints.property_id', '=', $iPropertyId);

	if ($bAddProducerToReport) {
		$oQueryBuilder
			->select(array('shop_producers.name', 'producer_name'))
			->join('shop_producers', 'shop_producers.id', '=', 'shop_items.shop_producer_id')
			->where('shop_producers.active', '=', '1')
			->where('shop_producers.deleted', '=', '0')
			->orderBy('shop_producers.name', 'ASC');

		array_unshift($aDataFields, 'producer_name');
		$iFiltersWidth++;
	}
	$oQueryBuilder
		->orderBy('shop_groups.name', 'ASC')
		->orderBy('shop_items.name', 'ASC');

	$data = $oQueryBuilder->execute()->asAssoc()->result();


	$iFoundItems = sizeof($data);
	$iWholeRows = $iTopShift + $iFoundItems;

	$result = array();
	$result[] = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?>';
	$result[] = '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
	$result[] = '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Created>2016-02-13T21:49:37Z</Created><Version>12.00</Version></DocumentProperties>';
	$result[] = '<Styles>';
	$result[] = '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Bottom"/><Borders/><Font x:CharSet="204" /><Interior/><NumberFormat/><Protection/></Style>';
	$result[] = '<Style ss:ID="s62"><Font ss:Bold="1"/></Style>';
	$result[] = '</Styles>';
	$result[] = '<Worksheet ss:Name="' . $sListName . '">';
	$result[] = '<Names><NamedRange ss:Name="_FilterDatabase" ss:RefersTo="=' . $sListName . '!R' . $iTopShift . 'C1:R' . $iWholeRows . 'C' . $iFiltersWidth . '" ss:Hidden="1"/></Names>';
	$result[] = '<Table ss:ExpandedColumnCount="5" ss:ExpandedRowCount="' . $iWholeRows . '" x:FullColumns="1" x:FullRows="1" ss:DefaultRowHeight="15">';
	foreach($aDataFields AS $key => $field)
	{
		$result[] = '<Column ss:Width="' . $aCellWidth[$field] . '"/>';
	}
   	$result[] = '<Row>';
	$result[] = '<Cell><Data ss:Type="String">Сайт: egeriya.ru</Data></Cell>';
	$result[] = '<Cell><Data ss:Type="String">Дата: ' . $date . '</Data></Cell>';
	$result[] = '<Cell><Data ss:Type="String">Найдено товаров: ' . $iFoundItems . '</Data></Cell>';
	$result[] = '</Row>';
	$result[] = '<Row><Cell><Data ss:Type="String"></Data></Cell></Row>';
	$result[] = '<Row>';
	foreach($aDataFields AS $key => $field)
	{
		$result[] = '<Cell ss:StyleID="s62"><Data ss:Type="String">' .  getName($field) . '</Data>';
		if ($key < $iFiltersWidth)
		{
			$result[] = '<NamedCell ss:Name="_FilterDatabase"/>';
		}
		$result[] = '</Cell>';
	}
	$result[] = '</Row>';

	if ($iFoundItems)
	{
		// строки данных
		foreach($data AS $row)
		{
			$result[] = '<Row>';
			foreach($aDataFields AS $key => $field)
			{
				$result[] = '<Cell><Data ss:Type="String">' . $row[$field] . '</Data>';
				if ($key < $iFiltersWidth)
				{
					$result[] = '<NamedCell ss:Name="_FilterDatabase"/>';
				}
				$result[] = '</Cell>';
			}
			$result[] = '</Row>';
		}
	} else {
		$result[] = '<Row><Cell><Data ss:Type="String">Товаров не найдено</Data></Cell></Row>';
	}
	$result[] = '</Table>';
	$result[] = '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><Unsynced/><Selected/><FreezePanes/><FrozenNoSplit/><SplitHorizontal>' . $iTopShift . '</SplitHorizontal><TopRowBottomPane>' . $iTopShift . '</TopRowBottomPane><ActivePane>2</ActivePane><Panes><Pane><Number>' . $iTopShift . '</Number></Pane><Pane><Number>2</Number><ActiveRow>0</ActiveRow></Pane></Panes><ProtectObjects>False</ProtectObjects><ProtectScenarios>False</ProtectScenarios></WorksheetOptions>';
	$result[] = '<AutoFilter x:Range="R' . $iTopShift . 'C1:R' . $iWholeRows . 'C' . $iFiltersWidth . '" xmlns="urn:schemas-microsoft-com:office:excel"></AutoFilter>';
	$result[] = '</Worksheet></Workbook>';

	return sendContent($result, array(
		//'Content-Description: File Transfer',
		'Content-Type: application/vnd.ms-excel',
		'Content-Disposition: attachment; filename=' . basename('egeriya.ru' . ' report ' . $date . '.xml'),
		'Content-Transfer-Encoding: binary'
	));
}

function getName($sKey)
{
	$aTexts = array(
		'id' => 'Идентификатор',
		'path' => 'Путь',
		'marking' => 'Артикул',
		'name' => 'Название',
		'sizes' => 'Размеры',
		'producer_name' => 'Производитель',
		'group_name' => 'Группа',
		'values' => 'Размеры'
	);
	if ($sKey && isset($aTexts[$sKey]))
	{
		return $aTexts[$sKey];
	}
	return $sKey;
};


function replacer($sText, $aReplaceRules)
{
	foreach($aReplaceRules as $sFrom => $sTo)
	{
		$sText = str_replace('{{' . $sFrom . '}}', $sTo, $sText);
	}
	return $sText;
}


function sendExcelReport($sRequestedSite = '', $sData = '', $sCharset, $iTotalTime)
{
	if ($sCharset !== 'UTF-8')
	{
		$sData = iconv($sCharset, 'UTF-8', $sData);
	}
	$data = json_decode($sData, TRUE);

	$date = date('Y-m-d H-i-s');

	$result = array();
	$result[] = '<?xml version="1.0"?><?mso-application progid="Excel.Sheet"?>';
	$result[] = '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
	$result[] = '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Created>2016-02-13T21:49:37Z</Created><Version>12.00</Version></DocumentProperties>';
	$result[] = '<Worksheet ss:Name="Лист1"><Table>';
	$result[] = '<Row><Cell><Data ss:Type="String">Сайт: ' . $sRequestedSite . '</Data></Cell>';
	$result[] = '<Cell><Data ss:Type="String">Дата: ' . $date . '</Data></Cell>';
	$result[] = '<Cell><Data ss:Type="String">Найдено товаров: ' . sizeof($data) . '</Data></Cell>';
	$result[] = '<Cell><Data ss:Type="String">Время парсинга: ' . $iTotalTime . ' сек.</Data></Cell></Row>';
	$result[] = '<Row><Cell><Data ss:Type="String"></Data></Cell></Row>';

	if (sizeof($data))
	{
		// строка заголовков
		$aColumnHeaders = $data[0];
		if (is_array($aColumnHeaders))
		{
			$result[] = '<Row>';
			foreach($aColumnHeaders as $sFieldName => $sFieldvalue)
			{
				$result[] = '<Cell><Data ss:Type="String">' . getName($sFieldName) . '</Data></Cell>';
			}
			$result[] = '</Row>';
		}

		// строки данных
		foreach($data AS $row)
		{
			$result[] = '<Row>';
			foreach($row AS $cell)
			{
				$result[] = '<Cell><Data ss:Type="String">' . $cell . '</Data></Cell>';
			}
			$result[] = '</Row>';
		}
	}
	$result[] = '</Table></Worksheet></Workbook>';

	return sendContent($result, array(
		//'Content-Description: File Transfer',
		'Content-Type: application/vnd.ms-excel',
		'Content-Disposition: attachment; filename=' . basename($sRequestedSite . ' report ' . $date . '.xml'),
		'Content-Transfer-Encoding: binary'
	));
}


function sendBookmarkletsList($aSites, $sScriptUrl)
{
	$result = array();
	$result[] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	$result[] = '<html xmlns="http://www.w3.org/1999/xhtml">';
	$result[] = '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Букмарклеты и отчеты</title></head>';
	$result[] = '<body><h3>Список букмарклетов для сбора данных</h3><ul>';
	foreach($aSites as $sSiteName => $sSiteParams)
	{
		$result[] = "<li><a href=\"javascript:(function(){var l=window.location,a='{$sSiteParams['url']}',d=document,t=d.createElement('script');if(l.href!=a)return l.href=a;t.src='{$sScriptUrl}?siteid={$sSiteName}',d.getElementsByTagName('body')[0].appendChild(t)})();\">Сбор данных с {$sSiteName}</a></li>";
	}
	$result[] = '</ul>';
	$result[] = '<h3>Отчеты по сайту egeriya.ru</h3><ul>';
	$result[] = '<li><a href="report.php">Отчет по группам и товарам</a></li>';
	$result[] = '<li><a href="report2.php">Отчет по производителям и товарам</a></li>';
	$result[] = '</ul></body></html>';
	return sendContent($result, array('Content-Type: text/html; charset=utf-8'));
}


function sendScripts($sScriptFolder, $sLibFolder, $sScenarioFolder, $sRequestedSite, $bIsSiteSupported, $sScriptUrl)
{
	$aLibs = array(
		'lodash.min.js' => array(),
		'URI.js' => array(),
		'q.min.js' => array(),
		'collect.js' => array(
			'sScriptUrl' => $sScriptUrl,
			'sRequestedSite' => $sRequestedSite,
		)
	);
	$sScenarioFileName = $sRequestedSite . '.js';
	$sScenarioFile = CMS_FOLDER . $sScriptFolder . $sScenarioFolder . $sScenarioFileName;
	$bIsSiteSupported = $bIsSiteSupported && is_file($sScenarioFile);

	$result = array();
	$result[] = '(function() {';
	if ($bIsSiteSupported)
	{
		foreach($aLibs as $sLibName => $aReplaceRules)
		{
			$result[] = getScriptContent(replacer(file_get_contents(CMS_FOLDER . $sScriptFolder . $sLibFolder . $sLibName), $aReplaceRules), $sLibName);
		}
		$result[] = getScriptContent(file_get_contents($sScenarioFile), $sScenarioFileName);
	}
	else
	{
		$result[] = 'alert("Запрашиваемый сайт не поддерживается");';
	}
	$result[] = '})();';
	return sendContent($result, array('Content-Type: text/javascript; charset=utf-8'));
}

function getScriptContent($sScriptStr, $sFileName)
{
	if (!Core::moduleIsActive('compression'))
	{
		return $sScriptStr;
	}
	$oCompression_Controller = compression_Controller::instance('js');
	return $oCompression_Controller->compress($sScriptStr, $sFileName);
}


function getScenarios($sScenariosDir, $sScriptFolder)
{
	$aSites = array();
	$aFiles = array_diff(scandir(CMS_FOLDER . $sScriptFolder . DIRECTORY_SEPARATOR . $sScenariosDir), array('..', '.'));
	Foreach($aFiles AS $sFilename)
	{
		$aPathParts = pathinfo($sFilename);
		if ($aPathParts['extension'] !== 'js')
		{
			continue;
		}
		$baseName = $aPathParts['filename'];
		$aSites[$baseName] = array('url' => 'http://' . $baseName . '/');
	}
	return $aSites;

}