<div class="invoice-document">
	<div class="company-header">
		<table>
			<tr>
				<td class="invoice-from-section">
					<?php
					/**
					 * wpwing_wcpi_invoice_template_company_data hook
					 *
					 * @hooked show_invoice_template_company_data - 10 (Render and show company data)
					 */
					do_action( 'wpwing_wcpi_invoice_template_company_data' );
					?>
				</td>
				<td class="invoice-logo">
					<?php
					/**
					 * wpwing_wcpi_invoice_template_company_logo hook
					 *
					 * @hooked show_invoice_template_company_logo - 10 (Show company logo)
					 */
					do_action( 'wpwing_wcpi_invoice_template_company_logo' );
					?>
				</td>
			</tr>

		</table>

	</div>

	<div class="invoice-header">
		<table>
			<tr>
				<td class="invoice-to-section">
					<?php
					/**
					 * wpwing_wcpi_invoice_template_customer_data hook
					 *
					 * @hooked show_invoice_template_customer_data - 10 (Render and show customer data)
					 */
					do_action( 'wpwing_wcpi_invoice_template_customer_data' );
					?>
				</td>
				<td class="invoice-data">
					<?php
					/**
					 * wpwing_wcpi_invoice_template_order_data hook
					 *
					 * @hooked show_invoice_template_order_data - 10 (Render and show order data)
					 */
					do_action( 'wpwing_wcpi_invoice_template_order_data' );
					?>
				</td>
			</tr>
		</table>
	</div>


	<div class="invoice-content">
		<?php
		/**
		 * wpwing_wcpi_invoice_template_product_list hook
		 *
		 * @hooked show_invoice_template_product_list - 10 (Show product list for current order)
		 */
		do_action( 'wpwing_wcpi_invoice_template_product_list' );
		?>
	</div>

	<?php
	/**
	 * wpwing_wcpi_invoice_template_footer hook
	 *
	 * @hooked show_invoice_template_footer - 10 (Show footer information)
	 */
	do_action( 'wpwing_wcpi_invoice_template_footer' );
	?>
</div>