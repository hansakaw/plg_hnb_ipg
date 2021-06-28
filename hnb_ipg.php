<?php
defined('_JEXEC') or die('Restricted access');

/**
 * @author Hansaka Weerasingha
 * @version $Id: hnb_ipg.php 8675 2016-11-27 1:16:45Z hansaka $
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2016 Hansaka Weerasingha. All rights reserved.   - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
if (!class_exists('vmPSPlugin')) {
	require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}

class plgVmPaymentHnb_Ipg extends vmPSPlugin {
	const REDIRECT_URL = 'https://www.hnbpg.hnb.lk/SENTRY/PaymentGateway/Application/ReDirectLink.aspx';

	function __construct(& $subject, $config) {

		parent::__construct($subject, $config);

		$this->_loggable = TRUE;
		$this->tableFields = array_keys($this->getTableSQLFields());
		$this->_tablepkey = 'id'; //virtuemart_hnb_ipg_id';
		$this->_tableId = 'id'; //'virtuemart_hnb_ipg_id';
		$varsToPush = $this->getVarsToPush();

		$this->setConfigParameterable($this->_configTableFieldName, $varsToPush);

	}

	/**
	 * @return string
	 */
	public function getVmPluginCreateTableSQL() {

		return $this->createTableSQL('Payment HNB IPG Table');
	}

	/**
	 * @return array
	 */
	function getTableSQLFields() {

		$SQLfields = array(
			'id' => 'int(11) UNSIGNED NOT NULL AUTO_INCREMENT',
			'virtuemart_order_id' => 'int(1) UNSIGNED',
			'order_number' => 'char(64)',
			'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED',
			'payment_name' => 'varchar(1000)',
			'payment_order_total' => 'decimal(15,5) NOT NULL',
			'payment_currency' => 'smallint(1)',
			'cost_per_transaction' => 'decimal(10,2)',
			'cost_percent_total' => 'decimal(10,2)',
			'tax_id' => 'smallint(1)',
			'hnb_ipg_custom' => 'varchar(255)',
			'hnb_ipg_response_code' => 'varchar(255)',
			'hnb_ipg_reason_code' => 'varchar(255)',
			'hnb_ref' => 'varchar(255)',
			'hnb_auth_code' => 'varchar(255)',
			'hnb_tx_stain' => 'varchar(255)'
		);
		return $SQLfields;
	}


	/**
	 * This shows the plugin for choosing in the payment list of the checkout process.
	 *
	 * @author Valerie Cartan Isaksen
	 */
	function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {

		if ($this->getPluginMethods($cart->vendorId) === 0) {
			if (empty($this->_name)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage(vmText::_('COM_VIRTUEMART_CART_NO_' . strtoupper($this->_psType)));
				return false;
			} else {
				return false;
			}
		}
		$htmla = array();
		$html = '';
		vmdebug('methods', $this->methods);
		VmConfig::loadJLang('com_virtuemart');
		$currency = CurrencyDisplay::getInstance();
		foreach ($this->methods as $method) {
			if ($this->checkConditions($cart, $method, $cart->cartPrices)) {
				$methodSalesPrice = $this->calculateSalesPrice($cart, $method, $cart->cartPrices);
				//$method->payment_name = $method->payment_name
				if (empty($method->ipg_merchant_id) or empty($method->ipg_password) or empty($method->acquirer_id)) {
					vmError(vmText::sprintf('VMPAYMENT_HNB_IPG_CONFIG_ERROR', $method->payment_name, $method->virtuemart_paymentmethod_id));
					continue;
				}
				$logo = $this->displayLogos($method->payment_logos);
				$payment_cost = '';
				if ($methodSalesPrice) {
					$payment_cost = $currency->priceDisplay($methodSalesPrice);
				}
				if ($selected == $method->virtuemart_paymentmethod_id) {
					$checked = 'checked="checked"';
				} else {
					$checked = '';
				}
				$html = $this->renderByLayout('display_payment', array(
					'plugin' => $method,
					'checked' => $checked,
					'payment_logo' => $logo,
					'payment_cost' => $payment_cost				));

				$htmla[] = $html;
			}
		}
		if (!empty($htmla)) {
			$htmlIn[] = $htmla;
		}

		return true;
	}

	/**
	 * This is for checking the input data of the payment method within the checkout
	 *
	 * @author Valerie Cartan Isaksen
	 */
	function plgVmOnCheckoutCheckDataPayment(VirtueMartCart $cart) {
		return null;
	}


	/**
	 * @param $cart
	 * @param $order
	 * @return bool','null
	 */
	function plgVmConfirmedOrder($cart, $order) {

		if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return FALSE;
		}
		$this->setInConfirmOrder($cart);
		$session = JFactory::getSession();
		$return_context = $session->getId();

		$this->_debug = $method->debug;
		$this->logInfo('plgVmConfirmedOrder order number: ' . $order['details']['BT']->order_number, 'message');
		vmdebug('HNB_IPG plgVmConfirmedOrder');
		if (!class_exists('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}
		if (!class_exists('VirtueMartModelCurrency')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'currency.php');
		}

		if (!class_exists('TableVendors')) {
			require(VMPATH_ADMIN . DS . 'tables' . DS . 'vendors.php');
		}

		$this->getPaymentCurrency($method);
		$currency_numeric_code = shopFunctions::getCurrencyByID($method->payment_currency, 'currency_numeric_code');
		$payment_currency = shopFunctions::getCurrencyByID($method->payment_currency, 'currency_code_3');
		$email_currency = $this->getEmailCurrency($method);
		
		$totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total, $method->payment_currency);
		$cd = CurrencyDisplay::getInstance($cart->pricesCurrency);

		$address = ((isset($order['details']['ST'])) ? $order['details']['ST'] : $order['details']['BT']);

		if ($totalInPaymentCurrency <= 0) {
			vmInfo(vmText::sprintf('VMPAYMENT_HNB_IPG_AMOUNT_INCORRECT', $order['details']['BT']->order_total, $totalInPaymentCurrency['value'], $payment_currency));
			return FALSE;
		}
		$payment_info = '';
		if (!empty($method->payment_info)) {
			$lang = JFactory::getLanguage ();
			if ($lang->hasKey ($method->payment_info)) {
				$payment_info = vmText::_ ($method->payment_info);
			} else {
				$payment_info = $method->payment_info;
			}
		}
		// Prepare data that should be stored in the database
		$dbValues['order_number'] = $order['details']['BT']->order_number;
		$dbValues['payment_name'] = $this->renderPluginName($method, 'order') . '<br />' . $payment_info;
		$dbValues['virtuemart_paymentmethod_id'] = $cart->virtuemart_paymentmethod_id;
		$dbValues['cost_per_transaction'] = $method->cost_per_transaction;
		$dbValues['cost_percent_total'] = $method->cost_percent_total;
		$dbValues['payment_currency'] = $currency_numeric_code;
		$dbValues['payment_order_total'] = $totalInPaymentCurrency['value'];
		$dbValues['tax_id'] = $method->tax_id;
		$dbValues['hnb_ipg_custom'] = $return_context;
		$this->storePSPluginInternalData($dbValues);

		//$mainframe = JFactory::getApplication();
		//$mainframe->redirect($url);

		// Send Data to IPG
		$this->_submitToIPG($method, $dbValues, $cart, $order);
	}

	function displayErrors($errors) {

		foreach ($errors as $error) {
			// TODO
			vmInfo(vmText::sprintf('VMPAYMENT_HNB_IPG_ERROR_FROM', $error ['message'], $error ['field'], $error ['code']));
			if ($error ['message'] == 401) {
				vmdebug('check you payment parameters: merchant_id, password, acquirer_id');
			}
		}
	}

	/**
	 * @param $html : Clients information
	 * @return bool','null','string
	 */
	function plgVmOnPaymentResponseReceived(&$html) {

		VmConfig::loadJLang('com_virtuemart_orders', TRUE);
		if (!class_exists('CurrencyDisplay')) {
			require(VMPATH_ADMIN . DS . 'helpers' . DS . 'currencydisplay.php');
		}
		if (!class_exists('VirtueMartCart')) {
			require(VMPATH_SITE . DS . 'helpers' . DS . 'cart.php');
		}
		if (!class_exists('shopFunctionsF')) {
			require(VMPATH_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
		}
		if (!class_exists('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}

		$virtuemart_paymentmethod_id = vRequest::getInt('pm', 0);
		$order_number = vRequest::getString('on', 0);
		$response_code = vRequest::getString('ResponseCode', -1);
		$reason_code = vRequest::getString('ReasonCode', -1);
		$ref_no = vRequest::getString('ReferenceNo', null);
		$auth_code = vRequest::getString('AuthCode', null);
		$tx_stain = vRequest::getString('TransactionStain', null);

		if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			return NULL;
		}

		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			return NULL;
		}
		if (!($paymentTables = $this->getDatasByOrderId($virtuemart_order_id))) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return '';
		}
		VmConfig::loadJLang('com_virtuemart');
		// Update order
		$orderModel = VmModel::getModel('orders');
		$order = $orderModel->getOrder($virtuemart_order_id);
		if ($response_code == 1) {
			$order['order_status'] = 'U';
		} else {
			if ($reason_code == 36) {
				$order['order_status'] = 'X';
			}
		}
		$orderModel->updateStatusForOneOrder($virtuemart_order_id, $order, false);

		$paymentCurrency = CurrencyDisplay::getInstance($order['details']['BT']->order_currency);
		$totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total, $method->payment_currency);

		// Update plugin data
		$dbValues = json_decode(json_encode($paymentTables[0]), true);
		$dbValues['hnb_ipg_response_code'] = $response_code;
		$dbValues['hnb_ipg_reason_code'] = $reason_code;
		$dbValues['hnb_ref'] = $ref_no;
		$dbValues['hnb_auth_code'] = $auth_code;
		$dbValues['hnb_tx_stain'] = $tx_stain;

		$this->storePSPluginInternalData($dbValues);

		$cart = VirtueMartCart::getCart();
		$currencyDisplay = CurrencyDisplay::getInstance($cart->pricesCurrency);

		$html = $this->renderByLayout('post_payment', array(
			'order' => $order,
			'displayTotalInPaymentCurrency' => $totalInPaymentCurrency['display'],
			'responseCode' => $response_code,
			'reasonCode' => $reason_code,
			'refNo' => $ref_no
		));
		vmdebug('_getPaymentResponseHtml', $paymentTables);

		// Remove vmcart
		if (isset($paymentTables[0]->hnb_ipg_custom) && $response_code == 1) {
			$this->emptyCart($paymentTables[0]->hnb_ipg_custom, $order_number);
		}

		return $html;
	}

	/**
	 * @return bool|null
	 */
	function plgVmOnUserPaymentCancel() {

		$order_number = vRequest::getString('on', '');
		// cancel / abort link must be insterted in the HNB IPG
		// must be http://mysite.com/index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&on=-REASON1-
		$virtuemart_paymentmethod_id = vRequest::getInt('pm', '');
		if (empty($order_number) or empty($virtuemart_paymentmethod_id) or !$this->selectedThisByMethodId($virtuemart_paymentmethod_id)) {
			return NULL;
		}
		$response_code = vRequest::getString('ResponseCode', -1);
		$reason_code = vRequest::getString('ReasonCode', -1);
		if ($response_code == 2 || $response_code == 3) {
			$lang = JFactory::getLanguage();
			$lang_key = 'VMPAYMENT_HNB_IPG_REASON_CODE_' . $reason_code;
			if ($lang->hasKey($lang_key)) {
				vmInfo(vmText::_($lang_key));
			} else {
				vmInfo(vmText::sprintf('VMPAYMENT_HNB_IPG_REASON_CODE_UNKNOWN_CODE', $error));
			}
			//return false;
		}
		if (!class_exists('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}
		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			return NULL;
		}
		if (!($paymentTable = $this->getDataByOrderId($virtuemart_order_id))) {
			return NULL;
		}
		if (!($method = $this->getVmPluginMethod($paymentTable->virtuemart_paymentmethod_id))) {
			return NULL; // Another method was selected, do nothing
		}
		if (!$this->selectedThisElement($method->payment_element)) {
			//vmdebug('IDEAL plgVmOnPaymentResponseReceived NOT selectedThisElement'  );
			return NULL;
		}
		vmdebug(__CLASS__ . '::' . __FUNCTION__, 'VMPAYMENT_HNB_IPG_PAYMENT_CANCELLED', $error_codes);
		if ($response_code == 2 || $response_code == 3) {
			VmInfo(vmText::_('VMPAYMENT_HNB_IPG_PAYMENT_CANCELLED'));
			$comment = '';
		} else {
			$comment = vmText::_($lang_key);
		}
		$session = JFactory::getSession();
		$return_context = $session->getId();
		vmDebug('handlePaymentUserCancel', $virtuemart_order_id, $paymentTable->hnb_ipg_custom, $return_context);
		if (strcmp($paymentTable->hnb_ipg_custom, $return_context) === 0) {
			vmDebug('handlePaymentUserCancel', $virtuemart_order_id);
			$this->handlePaymentUserCancel($virtuemart_order_id, $method->status_canceled, $comment);
		} else {
			vmDebug('Return context', $paymentTable->hnb_ipg_custom, $return_context);
		}
		return TRUE;
	}

	/*
	 *  plgVmOnPaymentNotification() - This event is fired by Offline Payment. It can be used to validate the payment data as entered by the user.
	 *  Return:
	 *  Parameters:
	 *  None
	 *  @author Valerie Isaksen
	 *  @return bool','null
	 */
	function plgVmOnPaymentNotification() {

		/*
		 $this->_debug = true;

		 $this->logInfo('plgVmOnPaymentNotification '.var_export($_POST, true) , 'message')	;
		 $this->logInfo('plgVmOnPaymentNotification  '.var_export($_REQUEST, true) , 'message');
		 // $paymentmethod_id = vRequest::getString('reason_2');
		*/

		$order_number = vRequest::getString('OrderID'); // is order number
		$response_code = vRequest::getString('ResponseCode', -1);
		$reason_code = vRequest::getString('ReasonCode', -1);

		if (!class_exists('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}
		if (empty($order_number) || ($response_code == 2 || $response_code == 3)) {
			return FALSE;
		}
		if (!($virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number))) {
			return FALSE;
		}
		if (!($payments = $this->getDatasByOrderId($virtuemart_order_id))) {
			return FALSE;
		}

		$method = $this->getVmPluginMethod($payments[0]->virtuemart_paymentmethod_id);
		if (!$this->selectedThisElement($method->payment_element)) {
			return false;
		}

		$hash_keys = array(
			'Password',
			'MerID',
			'AcqID',
			'OrderID',
			'PurchaseAmt',
			'PurchaseCurrency'
		);

		foreach ($hash_keys as $key) {
			$hash_data[$key] = vRequest::getString($key, '');
		}
		$hash_data['Password'] = $method->ipg_password;
		$hash_data['MerID'] = $method->ipg_merchant_id;
		$hash_data['AcqID'] = $method->acquirer_id;
		$hash_data['OrderID'] = $order_number;

		if (!$this->checkHash($hash_data)) {
			return false;
		}

		if (!class_exists('VirtueMartModelOrders')) {
			require(VMPATH_ADMIN . DS . 'models' . DS . 'orders.php');
		}

		$db = JFactory::getDBO();
		$query = 'SHOW COLUMNS FROM `' . $this->_tablename . '` ';
		$db->setQuery($query);
		$columns = $db->loadColumn(0);
		$prefix = 'hnb_ipg_response_';
		$prefix_hidden = 'hnb_ipg_reason_';
		$prefix_len = strlen($prefix);
		$prefix_hidden_len = strlen($prefix_hidden);
		foreach ($columns as $key) {
			if (substr($key, 0, $prefix_len) == $prefix) {
				$postKey = substr($key, $prefix_len);
				$dbvalues[$key] = vRequest::getString($postKey, '');
			} elseif (substr($key, 0, $prefix_hidden_len) == $prefix_hidden) {
				$postKey = substr($key, $prefix_hidden_len);
				$dbvalues[$key] = vRequest::getString($postKey, '');
			}
		}
		//$dbvalues['hidden_hash'] = vRequest::getString('hash', '');
		$dbvalues['virtuemart_paymentmethod_id'] = $payments[0]->virtuemart_paymentmethod_id;
		$dbvalues['virtuemart_order_id'] = $virtuemart_order_id;
		$dbvalues['order_number'] = $order_number;

		$modelOrder = VmModel::getModel('orders');
		$order = array();
		$this->logInfo('before getNewOrderStatus   ' . var_export($dbvalues, true), 'message');
		$status = $this->getNewOrderStatus($dbvalues);

		$order['order_status'] = $method->$status;
		$order['comments'] = vmText::_('VMPAYMENT_HNB_IPG_RESPONSE_' . $status);
		$order['customer_notified'] = 1;

		//$this->logInfo('before storePSPluginInternalData   ' , 'message');
		$this->storePSPluginInternalData($dbvalues);
		$this->logInfo('after storePSPluginInternalData   ' . var_export($dbvalues, true), 'message');

		$this->logInfo('plgVmOnPaymentNotification return new_status:' . $order['order_status'], 'message');

		$modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, false);
		//// remove vmcart
		if (isset($payments[0]->hnb_ipg_custom)) {
			$this->emptyCart($payments[0]->hnb_ipg_custom, $order_number);
		}
	}

	private function checkHash($data) {
		$hash_received = vRequest::getString('hash');
		$data_implode = implode('|', $data);
		$hash_calculated = sha1($data_implode);

		if ($hash_calculated != $hash_received) {
			$this->logInfo('HNB IPG plgVmOnPaymentNotification Incorrect HASH calculated value:' . $hash_calculated . ' received value:' . $hash_received, 'message');
			$this->logInfo(' data:' . var_export($data, true), 'message');
		}
		return true;
	}

	/*
	 * Not documented functionality
	 *
	 */
	private function getNewOrderStatus($dbvalues) {
		$newOrderStatus = array(
			'pending' => array('not_credited_yet' => 'status_pending'),
			'received' => array('credited' => 'status_confirmed'),
			'loss' => array('not_credited' => 'status_canceled'),
			'refunded' => array('refunded' => 'status_refunded', 'compensation' => 'status_compensation'),
			// Special case is the following status that can occur (only with iDEAL payments),
			//if after a timeout in our system the payment is marked as loss and then iDEAL reports (too late) a successful iDEAL payment.
		);
		$this->logInfo('IN getNewOrderStatus   ' . $dbvalues['hnb_ipg_response_code'] . ":" . $dbvalues['hnb_ipg_reason_code'] . 'message');

		if (!(array_key_exists($dbvalues['hnb_ipg_response_code'], $newOrderStatus) AND
			array_key_exists($dbvalues['hnb_ipg_reason_code'], $newOrderStatus[$dbvalues['hnb_ipg_response_code']]))
		) {
			// received an unknown combination.
			//
			$this->logInfo('IN 1 getNewOrderStatus   array_key_exists PROBLEM', 'message');

			$this->sendEmailToVendorAndAdmins(vmText::_('VMPAYMENT_HNB_IPG_ERROR_ORDER_STATUS_SUB'), vmText::sprintf('VMPAYMENT_HNB_IPG_ERROR_ORDER_STATUS_BODY', $dbvalues['hnb_ipg_response_code'], $dbvalues['hnb_ipg_reason_code'], $dbvalues['order_number']));
			$this->logInfo('IN 1 sendEmailToVendorAndAdmins   ' . $dbvalues['hnb_ipg_response_code'] . '/' . $dbvalues['hnb_ipg_reason_code'], 'message');
			$this->logInfo('  ' . array_key_exists($dbvalues['hnb_ipg_reason_code'], $newOrderStatus) . '/' . array_key_exists($dbvalues['hnb_ipg_response_code'], $newOrderStatus[$dbvalues['hnb_ipg_reason_code']]), 'message');

			return 'pending';
		}
		$this->logInfo('IN xx getNewOrderStatus   ' . $newOrderStatus[$dbvalues['hnb_ipg_response_code']][$dbvalues['hnb_ipg_reason_code']], 'message');

		return $newOrderStatus[$dbvalues['hnb_ipg_response_code']][$dbvalues['hnb_ipg_reason_code']];
	}


	/**
	 * Display stored payment data for an order
	 * @param  int $virtuemart_order_id
	 * @param  int $payment_method_id
	 * @see components/com_virtuemart/helpers/vmPSPlugin::plgVmOnShowOrderBEPayment()
	 */
	function plgVmOnShowOrderBEPayment($virtuemart_order_id, $payment_method_id) {
		if (!$this->selectedThisByMethodId($payment_method_id)) {
			return NULL; // Another method was selected, do nothing
		}

		if (!($payments = $this->getDatasByOrderId($virtuemart_order_id))) {
			// JError::raiseWarning(500, $db->getErrorMsg());
			return '';
		}

		$html = '<table class="adminlist table" >' . "\n";
		$html .= $this->getHtmlHeaderBE();
		$code = "hnb_ipg_response_";
		$first = TRUE;
		foreach ($payments as $payment) {
			$html .= '<tr class="row1"><td>' . vmText::_('COM_VIRTUEMART_DATE') . '</td><td align="left">' . $payment->created_on . '</td></tr>';
			// Now only the first entry has this data when creating the order
			if ($first) {
				$html .= $this->getHtmlRowBE('COM_VIRTUEMART_PAYMENT_NAME', $payment->payment_name);
				if ($payment->payment_order_total and $payment->payment_order_total != 0.00) {
					$html .= $this->getHtmlRowBE('HNB_IPG_PAYMENT_ORDER_TOTAL', $payment->payment_order_total . " " . shopFunctions::getCurrencyByID($payment->payment_currency, 'currency_code_3'));
				}
				$html .= $this->getHtmlRowBE('HNB_IPG_PAYMENT_EMAIL_CURRENCY', shopFunctions::getCurrencyByID($payment->payment_currency, 'currency_code_3'));

				$first = FALSE;
			} else {
				foreach ($payment as $key => $value) {
					// only displays if there is a value or the value is different from 0.00 and the value
					if ($value) {
						if (substr($key, 0, strlen($code)) == $code) {
							$html .= $this->getHtmlRowBE($key, $value);
						}
					}
				}
			}
		}
		$html .= '</table>' . "\n";
		return $html;
	}


	/**
	 * @param $method
	 * @param $order
	 * @return string
	 */
	function _getPaymentResponseHtml($method, $order) {
		VmConfig::loadJLang('com_virtuemart_orders', TRUE);
		if (!class_exists('CurrencyDisplay')) {
			require(VMPATH_ADMIN . DS . 'helpers' . DS . 'currencydisplay.php');
		}

		if (!class_exists('VirtueMartCart')) {
			require(VMPATH_SITE . DS . 'helpers' . DS . 'cart.php');
		}

		$cart = VirtueMartCart::getCart();

		$totalInPaymentCurrency = vmPSPlugin::getAmountInCurrency($order['details']['BT']->order_total, $method->payment_currency);
		$cart = VirtueMartCart::getCart();
		$currencyDisplay = CurrencyDisplay::getInstance($cart->pricesCurrency);


		$pluginName = $this->renderPluginName($method, 'post_payment');
		$html = $this->renderByLayout('post_payment', array(
			'order' => $order,
			'pluginName' => $pluginName,
			'displayTotalInPaymentCurrency' => $totalInPaymentCurrency['display']
		));
		//vmdebug('_getPaymentResponseHtml', $html,$pluginName,$paypalTable );

		return $html;
	}

	/*
	 * @param $method plugin
	 * @param $where from where this function is called
	 */
	protected function renderPluginName($method, $where = 'checkout') {

		$display_logos = "";
		$session_params = self::_getFromSession();
		if (empty($session_params)) {
			$payment_param = self::getEmptyPaymentParams($method->virtuemart_paymentmethod_id);
		} else {
			foreach ($session_params as $key => $session_param) {
				$payment_param[$key] = json_decode($session_param);
			}
		}

		$logos = $method->payment_logos;
		if (!empty($logos)) {
			$display_logos = $this->displayLogos($logos) . ' ';
		}
		$payment_name = $method->payment_name;
		vmdebug('renderPluginName', $payment_param);
		$html = $this->renderByLayout('render_pluginname', array(
			'where' => $where,
			'logo' => $display_logos,
			'payment_name' => $payment_name,
			'payment_description' => $method->payment_desc,
		));

		return $html;
	}

	/**
	 * @param VirtueMartCart $cart
	 * @param                $method
	 * @param                $cart_prices
	 * @return int
	 */
	/*	function getCosts (VirtueMartCart $cart, $method, $cart_prices) {

			if (preg_match('/%$/', $method->cost_percent_total)) {
				$cost_percent_total = substr($method->cost_percent_total, 0, -1);
			} else {
				$cost_percent_total = $method->cost_percent_total;
			}
			return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
		}*/

	/**
	 * Check if the payment conditions are fulfilled for this payment method
	 *
	 * @author: Valerie Isaksen
	 *
	 * @param $cart_prices : cart prices
	 * @param $payment
	 * @return true: if the conditions are fulfilled, false otherwise
	 *
	 */
	protected function checkConditions($cart, $method, $cart_prices) {

		$this->convert_condition_amount($method);
		$amount = $this->getCartAmount($cart_prices);
		$address = (($cart->ST == 0) ? $cart->BT : $cart->ST);

		$amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
			OR
			($method->min_amount <= $amount AND ($method->max_amount == 0)));

		$countries = array();
		if (!empty($method->countries)) {
			if (!is_array ($method->countries)) {
				$countries[0] = $method->countries;
			} else {
				$countries = $method->countries;
			}
		}

		// probably did not gave his BT:ST address
		if (!is_array ($address)) {
			$address = array();
			$address['virtuemart_country_id'] = 0;
		}

		if (!isset($address['virtuemart_country_id'])) {
			$address['virtuemart_country_id'] = 0;
		}
		if (in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
			if ($amount_cond) {
				return TRUE;
			}
		}

		return FALSE;
	}


	/**
	 * We must reimplement this triggers for joomla 1.7
	 */

	/**
	 * Create the table for this plugin if it does not yet exist.
	 * This functions checks if the called plugin is active one.
	 * When yes it is calling the standard method to create the tables
	 *
	 * @author Valérie Isaksen
	 *
	 */
	function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {

		return $this->onStoreInstallPluginTable($jplugin_id);
	}

	/**
	 * This event is fired after the payment method has been selected. It can be used to store
	 * additional payment info in the cart.
	 *
	 * @author Valérie isaksen
	 *
	 * @param VirtueMartCart $cart : the actual cart
	 * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
	 *
	 */
	public function plgVmOnSelectCheckPayment(VirtueMartCart $cart, &$msg) {

		return $this->OnSelectCheck ($cart);
	}

	/*
		 * plgVmonSelectedCalculatePricePayment
		 * Calculate the price (value, tax_id) of the selected method
		 * It is called by the calculator
		 * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
		 * @author Valerie Isaksen
		 * @cart: VirtueMartCart the current cart
		 * @cart_prices: array the new cart prices
		 * @return null if the method was not selected, false if the payment is not valid any more, true otherwise
		 *
		 *
		 */

	/**
	 * @param VirtueMartCart $cart
	 * @param array $cart_prices
	 * @param                $cart_prices_name
	 * @return bool','null
	 */

	public function plgVmOnSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {

		return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
	}


	/**
	 * plgVmOnCheckAutomaticSelectedPayment
	 * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
	 * The plugin must check first if it is the correct type
	 *
	 * @author Valerie Isaksen
	 * @param VirtueMartCart cart: the cart object
	 * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
	 *
	 */
	function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array(), &$paymentCounter) {

		$virtuemart_pluginmethod_id = 0;
		$nbMethod = $this->getSelectable($cart, $virtuemart_pluginmethod_id, $cart_prices);

		if ($nbMethod == NULL) {
			return NULL;
		} else {
			return 0;
		}
	}

	/**
	 * This method is fired when showing the order details in the frontend.
	 * It displays the method-specific data.
	 *
	 * @param integer $order_id The order ID
	 * @return mixed Null for methods that aren't active, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {


		if (!($this->selectedThisByMethodId($virtuemart_paymentmethod_id))) {
			return NULL;
		}
		$payments = $this->getDatasByOrderId($virtuemart_order_id);
		$nb = count($payments);

		$payment_name = $this->renderByLayout('order_fe', array(
			'paymentInfos' => $payments[$nb - 1],
			'paymentName' => $payments[0]->payment_name,
		));
	}


	/**
	 * This method is fired when showing when priting an Order
	 * It displays the the payment method-specific data.
	 *
	 * @param integer $_virtuemart_order_id The order ID
	 * @param integer $method_id method used for this order
	 * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
	 * @author Valerie Isaksen
	 */
	function plgVmonShowOrderPrintPayment($order_number, $method_id) {

		return $this->onShowOrderPrint($order_number, $method_id);
	}

	/**
	 * Save updated order data to the method specific table
	 *
	 * @param array $_formData Form data
	 * @return mixed, True on success, false on failures (the rest of the save-process will be
	 * skipped!), or null when this method is not actived.
	 *
	 * public function plgVmOnUpdateOrderPayment(  $_formData) {
	 * return null;
	 * }
	 */
	/**
	 * Save updated orderline data to the method specific table
	 *
	 * @param array $_formData Form data
	 * @return mixed, True on success, false on failures (the rest of the save-process will be
	 * skipped!), or null when this method is not actived.
	 *
	 * public function plgVmOnUpdateOrderLine(  $_formData) {
	 * return null;
	 * }
	 */
	/**
	 * plgVmOnEditOrderLineBE
	 * This method is fired when editing the order line details in the backend.
	 * It can be used to add line specific package codes
	 *
	 * @param integer $_orderId The order ID
	 * @param integer $_lineId
	 * @return mixed Null for method that aren't active, text (HTML) otherwise
	 *
	 * public function plgVmOnEditOrderLineBE(  $_orderId, $_lineId) {
	 * return null;
	 * }
	 */

	/**
	 * This method is fired when showing the order details in the frontend, for every orderline.
	 * It can be used to display line specific package codes, e.g. with a link to external tracking and
	 * tracing systems
	 *
	 * @param integer $_orderId The order ID
	 * @param integer $_lineId
	 * @return mixed Null for method that aren't active, text (HTML) otherwise
	 *
	 * public function plgVmOnShowOrderLineFE(  $_orderId, $_lineId) {
	 * return null;
	 * }
	 */
	function plgVmDeclarePluginParamsPaymentVM3(&$data) {
		return $this->declarePluginParams('payment', $data);
	}

	/**
	 * @param $name
	 * @param $id
	 * @param $table
	 * @return bool
	 */
	function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {

		return $this->setOnTablePluginParams($name, $id, $table);
	}

	private function _submitToIPG($method, $dbValues, $cart, $order) {
		$hash_keys = array(
			'Password' => $method->ipg_password,
			'MerID' => $method->ipg_merchant_id,
			'AcqID' => $method->acquirer_id,
			'OrderID' => $dbValues['order_number'],
			'PurchaseAmt' => $this->_getAmountWithPaddedZeros($dbValues['payment_order_total']),
			'PurchaseCurrency' => $dbValues['payment_currency']
		);

		// Form the signature hash
		$hash = '';
		foreach($hash_keys as $k => $v) {
			$hash .= $v;
		}
		// Form the signature
		$enc = base64_encode(pack('H*', sha1($hash)));

		// Prepare form data to be submited
		$form_data = $hash_keys;
		$form_data['Version'] = '1.0.0';
		$form_data['PurchaseCurrencyExponent'] = '2';
		$form_data['CaptureFlag'] = 'M';
		$form_data['SubmitURL'] = self::REDIRECT_URL;
		$form_data['MerRespURL'] = self::getSuccessUrl($order);
		$form_data['SignatureMethod'] = 'SHA1';
		$form_data['Signature'] = $enc;

		$cart->_confirmDone = FALSE;
		$cart->_dataValidated = FALSE;
		$cart->setCartIntoSession();

		$html = $this->renderByLayout('process_payment', array(
			'form_data' => $form_data
		));
		vRequest::setVar('html', $html);
	}

	private function _getAmountWithPaddedZeros($amount) {
		return str_pad(number_format($amount, 2, '', ''), 12, '0', STR_PAD_LEFT);
	}

	private function _validate_hnb_ipg_data($payment_params, $paymentmethod_id, &$error_msg) {

		$errors = array();
		if (empty($payment_params['hnb_ipg_selected_' . $paymentmethod_id])) {
			$errors[] = vmText::_('VMPAYMENT_HNB_IPG_PLEASE_SELECT');
		}

		if (!empty($errors)) {
			$error_msg .= "</br />";
			foreach ($errors as $error) {
				$error_msg .= " -" . $error . "</br />";
			}
			return FALSE;
		}
		return TRUE;
	}

	private static function getEmptyPaymentParams($paymentmethod_id) {

		$payment_params['hnb_ipg_selected_' . $paymentmethod_id] = "";

		return $payment_params;
	}

	private static function _clearHnbIpgSession() {

		$session = JFactory::getSession();
		$session->clear('HnbIpg', 'vm');
	}

	private static function _setIntoSession($data) {

		$session = JFactory::getSession();
		$session->set('HNB_IPG', json_encode($data), 'vm');
	}

	private static function _getFromSession() {

		$session = JFactory::getSession();
		$data = $session->get('HNB_IPG', 0, 'vm');
		if (empty($data)) {
			//return self::getEmptyPaymentParams ();
			return NULL;
		}
		return json_decode($data);
	}

	private static function   getSuccessUrl($order) {
		$url = JROUTE::_("index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived&pm=" . $order['details']['BT']->virtuemart_paymentmethod_id . '&on=' . $order['details']['BT']->order_number . "&Itemid=" . vRequest::getInt('Itemid'), false);
		if (strpos($url, 'index.php')) {
			$url = 'index.php' . explode('index.php', $url)[1];
		}
		return JURI::base() . $url;
	}

	private static function   getCancelUrl($order) {
		return JURI::base() . JROUTE::_("index.php?option=com_virtuemart&view=pluginresponse&task=pluginUserPaymentCancel&pm=" . $order['details']['BT']->virtuemart_paymentmethod_id . '&on=' . $order['details']['BT']->order_number . '&Itemid=' . vRequest::getInt('Itemid'), false);
	}

	private static function   getNotificationUrl($order_number) {
		return JURI::base() . JROUTE::_("index.php?option=com_virtuemart&view=pluginresponse&tmpl=component&task=pluginnotification&on=" . $order_number, false);
	}

}

// No closing tag
