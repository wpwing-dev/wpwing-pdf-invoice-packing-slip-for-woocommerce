<div class="invoice-document">
	<div class="company-header">
		<table>
			<tr>
				<td class="invoice-from-section">
					<?php do_action( 'wpwing_wcpdf_invoice_template_company_data' ); ?>
				</td>
				<td class="invoice-logo">
					<?php do_action( 'wpwing_wcpdf_invoice_template_company_logo' ); ?>
				</td>
			</tr>
		</table>
	</div>

	<div class="invoice-header">
		<table>
			<tr>
				<td class="invoice-to-section">
					<?php do_action( 'wpwing_wcpdf_invoice_template_customer_data' ); ?>
				</td>
				<td class="invoice-data">
					<?php do_action( 'wpwing_wcpdf_invoice_template_order_data' ); ?>
				</td>
			</tr>
		</table>
	</div>

	<div class="invoice-content">
		<?php do_action( 'wpwing_wcpdf_invoice_template_product_list' ); ?>
	</div>

	<?php do_action( 'wpwing_wcpdf_invoice_template_footer' ); ?>
</div>
