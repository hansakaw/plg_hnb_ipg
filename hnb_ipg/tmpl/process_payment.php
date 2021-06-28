<?php
defined ('_JEXEC') or die();

/**
 * @author Hansaka Weerasingha
 * @version $Id: process_payment.php 7953 2016-12-07 00:54:25Z hansaka $
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2016 Hansaka Weerasingha. All rights reserved.
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
<form id="vmPaymentForm" method="post" action="<?php echo $viewData['form_data']['SubmitURL']; ?>">
<div align="center">
<input id='Version' type='hidden' name='Version' value="<?php echo $viewData['form_data']['Version']; ?>">
<input id='MerID' type='hidden' value="<?php echo $viewData['form_data']['MerID']; ?>" name='MerID' >
<input id='AcqID' type='hidden' value="<?php echo $viewData['form_data']['AcqID']; ?>" name='AcqID' >
<input id='MerRespURL' type='hidden' value="<?php echo $viewData['form_data']['MerRespURL']; ?>" name='MerRespURL'>
<input id='PurchaseCurrency' type='hidden' value="<?php echo $viewData['form_data']['PurchaseCurrency']; ?>" name='PurchaseCurrency'>
<input id='PurchaseCurrencyExponent' type='hidden' value="<?php echo $viewData['form_data']['PurchaseCurrencyExponent']; ?>" name='PurchaseCurrencyExponent'>
<input id='OrderID' type='hidden' value="<?php echo $viewData['form_data']['OrderID']; ?>" name='OrderID' >
<input id='SignatureMethod' type='hidden' value="<?php echo $viewData['form_data']['SignatureMethod']; ?>" name='SignatureMethod'>
<input id='Signature' type='hidden' value="<?php echo $viewData['form_data']['Signature']; ?>" name='Signature'>
<input id='CaptureFlag' type='hidden' value="<?php echo $viewData['form_data']['CaptureFlag']; ?>" name='CaptureFlag' >
<input id='PurchaseAmt' type='hidden' value="<?php echo $viewData['form_data']['PurchaseAmt']; ?>" name='PurchaseAmt' >

<noscript>
	<h3 align="center"> Please click on the Submit button to continue processing.<br>
	<input type="submit" value="Submit">
</noscript>
</div>
</form>

<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery('#vmPaymentForm').submit();
});
</script>
