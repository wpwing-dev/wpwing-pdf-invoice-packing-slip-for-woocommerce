<?php
/**
 * The Template for invoice.
 *
 * Override this template by copying it to yourtheme/default/template.php
 *
 * @version 1.0.0
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<style type="text/css">
			body {
				color: #000;
				font-family: DejaVu Sans, sans-serif;
			}
		</style>
		<?php
			/*
			 * Fires wpwing_wcpdf_template_head.
			 *
			 * @hooked add_template_head - 10 ( add css style )
			 */
			do_action( 'wpwing_wcpdf_template_head' );
		?>
	</head>

	<body>

		<?php
			/*
			 * Fires wpwing_wcpdf_template_content.
			 *
			 * @hooked add_template_content - 10 ( add other content )
			 */
			do_action( 'wpwing_wcpdf_template_content' );
		?>

	</body>
</html>