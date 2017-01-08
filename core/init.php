<?php

/**
 * @author James V. Kotoff
 * @copyright 2016
 */

/* using some core features from HostCMS */

require_once(dirname(__FILE__) . '/../../' . 'bootstrap.php');
$oSite = Core_Entity::factory('Site', 1);
define('CURRENT_SITE', $oSite->id);
Core::initConstants($oSite);
