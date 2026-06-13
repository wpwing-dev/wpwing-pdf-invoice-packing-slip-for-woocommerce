<?php
/**
 * One-time data migration from the old wpwing_wcpi_ prefix to wpwing_wcpdf_.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Migration' ) ) {

	/**
	 * One-time data migration from the old wpwing_wcpi_ prefix to wpwing_wcpdf_.
	 * Runs on every plugins_loaded until the stored db_version matches WPWING_WCPDF_VERSION.
	 * Safe to run multiple times (idempotent).
	 *
	 * @since 1.5.1
	 */
	class WPWing_WcPdf_Migration {

		/**
		 * Run migration if the stored db_version does not match the current plugin version.
		 */
		public static function maybe_run() {
			if ( get_option( 'wpwing_wcpdf_db_version' ) === WPWING_WCPDF_VERSION ) {
				return;
			}
			self::migrate_options();
			self::migrate_order_meta();
			update_option( 'wpwing_wcpdf_db_version', WPWING_WCPDF_VERSION );
		}

		/**
		 * Migrate plugin options from the old wpwing_wcpi_ prefix.
		 */
		private static function migrate_options() {
			// Settings blob (all plugin settings stored under one key).
			$settings = get_option( 'wpwing_wcpi_settings' );
			if ( false !== $settings ) {
				update_option( 'wpwing_wcpdf_settings', $settings );
				delete_option( 'wpwing_wcpi_settings' );
			}

			// Folder-protection flag.
			$protected = get_option( 'wpwing_wcpi_check_folder_already_protected' );
			if ( false !== $protected ) {
				update_option( 'wpwing_wcpdf_check_folder_already_protected', $protected );
				delete_option( 'wpwing_wcpi_check_folder_already_protected' );
			}
		}

		/**
		 * Migrate order meta keys from the old wpwing_wcpi_ prefix.
		 */
		private static function migrate_order_meta() {
			global $wpdb;

			$meta_map = array(
				'_wpwing_wcpi_invoiced'       => '_wpwing_wcpdf_invoiced',
				'_wpwing_wcpi_invoice_number' => '_wpwing_wcpdf_invoice_number',
				'_wpwing_wcpi_invoice_prefix' => '_wpwing_wcpdf_invoice_prefix',
				'_wpwing_wcpi_invoice_suffix' => '_wpwing_wcpdf_invoice_suffix',
				'_wpwing_wcpi_invoice_date'   => '_wpwing_wcpdf_invoice_date',
				'_wpwing_wcpi_invoice_path'   => '_wpwing_wcpdf_invoice_path',
				'_wpwing_wcpi_packing'        => '_wpwing_wcpdf_packing',
				'_wpwing_wcpi_packing_path'   => '_wpwing_wcpdf_packing_path',
				'_wpwing_wcpi_proforma'       => '_wpwing_wcpdf_proforma',
				'_wpwing_wcpi_proforma_path'  => '_wpwing_wcpdf_proforma_path',
			);

			// Classic orders (wp_postmeta).
			foreach ( $meta_map as $old_key => $new_key ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- migration routine, no caching needed.
				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
						$new_key,
						$old_key
					)
				);
			}

			// Credit note dynamic keys in postmeta (keyed by refund ID: _wpwing_wcpi_creditnote_{id}).
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				"UPDATE {$wpdb->postmeta}
				 SET meta_key = REPLACE(meta_key, '_wpwing_wcpi_creditnote', '_wpwing_wcpdf_creditnote')
				 WHERE meta_key LIKE '_wpwing_wcpi_creditnote%'"
			);

			// HPOS orders (wc_orders_meta) - only if table exists.
			$hpos_table = $wpdb->prefix . 'wc_orders_meta';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $hpos_table ) );
			if ( $table_exists === $hpos_table ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- migration routine; table name is plugin-controlled.
				foreach ( $meta_map as $old_key => $new_key ) {
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$hpos_table} SET meta_key = %s WHERE meta_key = %s",
							$new_key,
							$old_key
						)
					);
				}
				$wpdb->query(
					"UPDATE {$hpos_table}
					 SET meta_key = REPLACE(meta_key, '_wpwing_wcpi_creditnote', '_wpwing_wcpdf_creditnote')
					 WHERE meta_key LIKE '_wpwing_wcpi_creditnote%'"
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}
	}
}
