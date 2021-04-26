<?php

class osC_Payment_Zibal extends osC_Payment_Admin
{
	var $_title;
	var $_code = 'zibal';
	var $_author_name = 'Yahya Kangi';
	var $_author_www = 'https://zibal.ir/';
	var $_status = false;

	function osC_Payment_Zibal()
	{
		global $osC_Language;

		$this->_title = $osC_Language->get('payment_zibal_title');
		$this->_description = $osC_Language->get('payment_zibal_description');
		$this->_method_title = $osC_Language->get('payment_zibal_method_title');
		$this->_status = (defined('MODULE_PAYMENT_ZIBAL_STATUS') && (MODULE_PAYMENT_ZIBAL_STATUS == '1') ? true : false);
		$this->_sort_order = (defined('MODULE_PAYMENT_ZIBAL_SORT_ORDER') ? MODULE_PAYMENT_ZIBAL_SORT_ORDER : null);
	}

	function isInstalled() {

		return (bool)defined('MODULE_PAYMENT_ZIBAL_STATUS');
	}

    function install()
	{
		global $osC_Database, $osC_Language;

		parent::install();

		$osC_Database->simpleQuery("CREATE TABLE IF NOT EXISTS `" . DB_TABLE_PREFIX . "online_transactions` (`id` int(10) unsigned NOT NULL auto_increment, `orders_id` int(10) default NULL, `receipt_id` varchar(60) default NULL, `transaction_method` varchar(60) default NULL, `transaction_date` datetime default NULL, `transaction_amount` varchar(20) default NULL, `transaction_id` varchar(60) default NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) VALUES ('" . $osC_Language->get('payment_zibal_status_title') . "', 'MODULE_PAYMENT_ZIBAL_STATUS', '-1', '" . $osC_Language->get('payment_zibal_status_description') . "', '6', '0', 'osc_cfg_use_get_boolean_value', 'osc_cfg_set_boolean_value(array(1, -1))', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_zibal_api_title') . "', 'MODULE_PAYMENT_ZIBAL_API', '', '" .  $osC_Language->get('payment_zibal_api_description') . "', '6', '0', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_zibal_sendurl_title') . "', 'MODULE_PAYMENT_ZIBAL_SENDURL', 'https://gateway.zibal.ir/v1/request', '" .  $osC_Language->get('payment_zibal_sendurl_description') . "', '6', '0', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_zibal_verifyurl_title') . "', 'MODULE_PAYMENT_ZIBAL_VERIFYURL', 'https://gateway.zibal.ir/v1/verify', '" .  $osC_Language->get('payment_zibal_verifyurl_description') . "', '6', '0', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_zibal_gatewayurl_title') . "', 'MODULE_PAYMENT_ZIBAL_GATEWAYURL', 'https://gateway.zibal.ir/start/', '" .  $osC_Language->get('payment_zibal_gatewayurl_description') . "', '6', '0', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('" . $osC_Language->get('payment_zibal_currency_title') . "', 'MODULE_PAYMENT_ZIBAL_CURRENCY', 'IRR', '" . $osC_Language->get('payment_zibal_currency_description') . "', '6', '0', 'osc_cfg_set_boolean_value(array(\'Selected Currency\',\'IRR\'))', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) VALUES ('" . $osC_Language->get('payment_zibal_zone_title') . "', 'MODULE_PAYMENT_ZIBAL_ZONE', '0', '" . $osC_Language->get('payment_zibal_zone_description') . "', '6', '0', 'osc_cfg_use_get_zone_class_title', 'osc_cfg_set_zone_classes_pull_down_menu', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('" . $osC_Language->get('payment_zibal_order_title') . "', 'MODULE_PAYMENT_ZIBAL_ORDER_STATUS_ID', '0', '" . $osC_Language->get('payment_zibal_order_description') . "', '6', '0', 'osc_cfg_set_order_statuses_pull_down_menu', 'osc_cfg_use_get_order_status_title', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_zibal_sort_title') . "', 'MODULE_PAYMENT_ZIBAL_SORT_ORDER', '0', '" . $osC_Language->get('payment_zibal_sort_description') . "', '6', '0', now())");
	}

	function getKeys()
	{
		if (!isset($this->_keys)) {

        $this->_keys = array (
			'MODULE_PAYMENT_ZIBAL_STATUS',
			'MODULE_PAYMENT_ZIBAL_API',
			'MODULE_PAYMENT_ZIBAL_SENDURL',
			'MODULE_PAYMENT_ZIBAL_VERIFYURL',
			'MODULE_PAYMENT_ZIBAL_GATEWAYURL',
			'MODULE_PAYMENT_ZIBAL_CURRENCY',
			'MODULE_PAYMENT_ZIBAL_ZONE',
			'MODULE_PAYMENT_ZIBAL_ORDER_STATUS_ID',
			'MODULE_PAYMENT_ZIBAL_SORT_ORDER');
		}

		return $this->_keys;
	}	
}