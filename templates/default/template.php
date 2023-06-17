<?php

	/**
	 * The Template for invoice
	 *
	 * Override this template by copying it to yourtheme/default/template.php
	 *
	 * @version     1.0.0
	 */

	// Exit if accessed directly
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
			/**
			 * wpwing_wcpi_template_head hook
			 *
			 * @hooked add_template_head - 10 ( add css style )
			 */
			do_action( 'wpwing_wcpi_template_head' );
		?>
	</head>

	<body>

		<?php
			/**
			 * wpwing_wcpi_template_content hook
			 *
			 * @hooked add_template_content - 10 ( add other content )
			 */
			do_action( 'wpwing_wcpi_template_content' );
		?>

	</body>
</html>