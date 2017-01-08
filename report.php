<?php

/**
 * @author James V. Kotoff
 * @copyright 2016
 */

require_once(dirname(__FILE__) . '/core/init.php');
require_once(dirname(__FILE__) . '/core/common.php');


if (!Core_Auth::logged())
{
	return sendContent(array('Доступ запрещен'), array('Content-Type: text/plain; charset=utf-8'), 403);
}

return makeExcelLocalReport(FALSE);
