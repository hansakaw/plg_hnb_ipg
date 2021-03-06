<?php
defined ('_JEXEC') or die();

/**
 * @author Valérie Isaksen
 * @version $Id: order_fe.php 7953 2014-05-18 14:06:25Z alatak $
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
<div class="payment_name" style="width: 100%">
    <?php echo  $viewData['paymentName'] ; ?>
</div>
<div class="tx-status" style="width: 100%">
    <span class="tx-status-title"><?php echo vmText::_ ('VMPAYMENT_HNB_IPG_RESPONSE_STATUS'); ?> : </span>
    <?php echo vmText::_('VMPAYMENT_HNB_IPG_RESPONSE_CODE_' . $viewData['paymentInfos']->hnb_decision); ?>
</div>
<?php if ($viewData['paymentInfos']->hnb_ref) { ?>
<div class="tx-ref" style="width: 100%">
    <span class="tx-ref-title"><?php echo vmText::_ ('VMPAYMENT_HNB_IPG_REFERENCE_NUMBER'); ?> : </span>
    <?php echo $viewData['paymentInfos']->hnb_ref; ?>
</div>
<?php } ?>
