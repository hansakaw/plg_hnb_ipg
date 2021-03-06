<?php
defined ('_JEXEC') or die();

/**
 * @author Hansaka Weerasingha
 * @version $Id: post_payment.php 7953 2016-12-06 22:07:25Z hansaka $
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2004-Copyright (C) 2004-2015 Virtuemart Team. All rights reserved.   - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */

?>
<div class="post_payment_order_number" style="width: 100%">
	<span class="post_payment_order_number_title"><?php echo vmText::_ ('COM_VIRTUEMART_ORDER_NUMBER'); ?> : </span>
	<?php echo  $viewData['order']['details']['BT']->order_number; ?>
</div>
<?php if ($viewData['decision']) { ?>
<div class="post_payment_transaction" style="width: 100%">
	<span class="post_payment_transaction_response_code"><?php echo vmText::_ ('VMPAYMENT_HNB_IPG_RESPONSE_STATUS'); ?> : </span>
	<?php echo vmText::_('VMPAYMENT_HNB_IPG_RESPONSE_CODE_' . $viewData['decision']); ?>
</div>

<div class="post_payment_transaction" style="width: 100%">
	<span class="post_payment_transaction_reason_code"><?php echo vmText::_ ('VMPAYMENT_HNB_IPG_RESPONSE_MESSAGE'); ?> :</span>
	<?php echo vmText::_('VMPAYMENT_HNB_IPG_REASON_CODE_' . $viewData['reasonCode']); ?>
</div>
<?php } ?>

<?php if ($viewData['refNo']) { ?>
<div class="post_payment_transaction" style="width: 100%">
	<span class="post_payment_transaction_ref_no"><?php echo vmText::_ ('VMPAYMENT_HNB_IPG_REFERENCE_NUMBER'); ?> : </span>
	<?php echo $viewData['refNo']; ?>
</div>
<?php } ?>

<div class="post_payment_order_total" style="width: 100%">
	<span class="post_payment_order_total_title"><?php echo vmText::_ ('COM_VIRTUEMART_ORDER_PRINT_TOTAL'); ?> : </span>
	<?php echo $viewData['displayTotalInPaymentCurrency']; ?>
</div>

<div class="post_payment_auth_amount" style="width: 100%">
	<span class="post_payment_auth_amount_title"><?php echo vmText::_ ('VMPAYMENT_HNB_IPG_AUTH_AMOUNT'); ?> : </span>
	<?php echo $viewData['authAmount']; ?>
</div>
</br>
<strong>
<a class="vm-button-correct" href="<?php echo JRoute::_('index.php?option=com_virtuemart&view=orders&layout=details&order_number='.$viewData["order"]['details']['BT']->order_number.'&order_pass='.$viewData["order"]['details']['BT']->order_pass, false)?>"><?php echo vmText::_('COM_VIRTUEMART_ORDER_VIEW_ORDER'); ?></a>
</strong>
