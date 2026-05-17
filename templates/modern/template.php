<?php

	/**
	 * The Template for invoice — Modern theme
	 *
	 * Override this template by copying it to yourtheme/modern/template.php
	 *
	 * @version     1.0.0
	 */

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
		<?php do_action( 'wpwing_wcpi_template_head' ); ?>
	</head>

	<body>
		<?php do_action( 'wpwing_wcpi_template_content' ); ?>
	</body>
</html>
