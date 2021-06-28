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
<form id="vmPaymentForm" method="post" action="<?php echo $viewData['submit_url']; ?>">
	<div align="left">
		<fieldset id="confirmation">
			<legend>Review Payment Details</legend>
			<div>
				<?php
					foreach($viewData['form_data'] as $name => $value) {
						if ($name != 'signed_field_names' && $name != 'access_key' && $name != 'signature' && $name != 'profile_id' && $name != 'override_custom_receipt_page' && $name != 'override_custom_cancel_page') {
							echo "<div>";
							echo "<span class=\"fieldName\">" . $name . "</span><span class=\"fieldValue\">" . $value . "</span>";
							echo "</div>\n";
						}
					}
				?>
			</div>
		</fieldset>
		<?php
			foreach($viewData['form_data'] as $name => $value) {
				if ($name != 'SubmitURL' && $name != 'MerRespURL' && $name != 'MerCancelURL' && $name != 'MerNotifyURL') {
					echo "<input type=\"hidden\" id=\"" . $name . "\" name=\"" . $name . "\" value=\"" . $value . "\"/>\n";
				}
			}
		?>
		<noscript>
			<h3 align="left">Please click on the Confirm button to continue processing.<br>
			<input type="submit" value="Confirm">
		</noscript>
	</div>
</form>

<script type="text/javascript">
jQuery(document).ready(function(){
	jQuery('#vmPaymentForm').submit();
});
</script>
