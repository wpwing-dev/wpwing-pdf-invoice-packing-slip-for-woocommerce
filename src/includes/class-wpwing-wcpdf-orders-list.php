<?php
/**
 * Orders list column and bulk actions for invoice/packing slip generation.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Orders_List' ) ) {

	/**
	 * Handles the invoice status column and bulk generation actions
	 * on both the classic and HPOS WooCommerce orders list screens.
	 *
	 * @since 1.8.0
	 */
	class WPWing_WcPdf_Orders_List {

		/**
		 * Plugin instance.
		 *
		 * @var WPWing_WcPdf_Plugin
		 */
		private $plugin;

		/**
		 * Constructor.
		 *
		 * @param WPWing_WcPdf_Plugin $plugin Plugin instance.
		 */
		public function __construct( WPWing_WcPdf_Plugin $plugin ) {
			$this->plugin = $plugin;
		}

		/**
		 * Register orders list hooks.
		 */
		public function register() {
			// Bulk actions - classic orders screen.
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_actions' ), 10, 3 );

			// Bulk actions - HPOS orders screen.
			add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_actions' ), 10, 3 );

			// Invoice status column - classic orders screen.
			add_filter( 'manage_shop_order_posts_columns', array( $this, 'add_order_list_column' ) );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_list_column' ), 10, 2 );

			// Invoice status column - HPOS orders screen.
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_list_column' ) );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_order_list_column' ), 10, 2 );
		}

		/**
		 * Register bulk actions on the orders list screen.
		 *
		 * @param array $actions Existing bulk actions.
		 * @return array
		 */
		public function add_bulk_actions( $actions ) {
			$actions['wpwing_generate_invoices']     = __( 'Generate Invoices', 'wpwing-wcpdf' );
			$actions['wpwing_generate_packing']      = __( 'Generate Packing Slips', 'wpwing-wcpdf' );
			$actions['wpwing_download_invoices_zip'] = __( 'Download Invoices (ZIP)', 'wpwing-wcpdf' );
			$actions['wpwing_download_invoices_pdf'] = __( 'Download Invoices (Merged PDF)', 'wpwing-wcpdf' );
			$actions['wpwing_download_packing_zip']  = __( 'Download Packing Slips (ZIP)', 'wpwing-wcpdf' );
			$actions['wpwing_download_packing_pdf']  = __( 'Download Packing Slips (Merged PDF)', 'wpwing-wcpdf' );
			return $actions;
		}

		/**
		 * Process bulk invoice / packing slip generation and downloads.
		 *
		 * Download actions generate any missing documents first, then stream a ZIP
		 * or a single merged PDF and exit. Generate actions redirect with a notice.
		 *
		 * @param string $redirect_to Redirect URL after processing.
		 * @param string $action      Bulk action key.
		 * @param array  $ids         Selected order IDs.
		 * @return string
		 */
		public function handle_bulk_actions( $redirect_to, $action, $ids ) {
			// Action key => [ document type, download format ].
			$bulk_actions = array(
				'wpwing_generate_invoices'     => array( 'invoice', '' ),
				'wpwing_generate_packing'      => array( 'packing', '' ),
				'wpwing_download_invoices_zip' => array( 'invoice', 'zip' ),
				'wpwing_download_invoices_pdf' => array( 'invoice', 'pdf' ),
				'wpwing_download_packing_zip'  => array( 'packing', 'zip' ),
				'wpwing_download_packing_pdf'  => array( 'packing', 'pdf' ),
			);

			if ( ! isset( $bulk_actions[ $action ] ) ) {
				return $redirect_to;
			}

			list( $document_type, $download_format ) = $bulk_actions[ $action ];

			$count     = 0;
			$all_ids   = (array) $ids;
			$batch_ids = array_slice( $all_ids, 0, 50 );
			$skipped   = count( $all_ids ) - count( $batch_ids );
			$documents = array();

			foreach ( $batch_ids as $order_id ) {
				$document = $this->plugin->get_document_by_type( intval( $order_id ), $document_type );
				if ( null === $document || ! $document->is_valid ) {
					continue;
				}
				if ( ! $document->exists ) {
					$this->plugin->save_document( $document );
					if ( $document->exists ) {
						++$count;
					}
				}
				if ( $document->exists ) {
					$documents[] = $document;
				}
			}

			if ( $download_format && $documents ) {
				if ( 'zip' === $download_format ) {
					$this->stream_zip( $documents, $document_type );
				} else {
					$this->stream_merged_pdf( $documents, $document_type );
				}
				// Streaming exits on success; reaching here means it failed, fall through to redirect.
			}

			$redirect_to = remove_query_arg( array( 'wpwing_bulk_type', 'wpwing_bulk_created', 'wpwing_bulk_skipped', 'wpwing_bulk_nonce' ), $redirect_to );
			$redirect_to = add_query_arg(
				array(
					'wpwing_bulk_type'    => $document_type,
					'wpwing_bulk_created' => $count,
					'wpwing_bulk_skipped' => $skipped,
					'wpwing_bulk_nonce'   => wp_create_nonce( 'wpwing_bulk_notice' ),
				),
				$redirect_to
			);

			return $redirect_to;
		}

		/**
		 * Stream the given documents' PDF files as a ZIP archive and exit.
		 * Returns without output if no valid files are found or the archive fails.
		 *
		 * @param WPWing_WcPdf_Document[] $documents     Documents with existing PDF files.
		 * @param string                  $document_type 'invoice' or 'packing'.
		 * @since 1.10.0
		 */
		private function stream_zip( $documents, $document_type ) {
			$files = array();
			$base  = realpath( WPWING_WCPDF_DOCUMENT_SAVE_DIR );

			foreach ( $documents as $document ) {
				$real = realpath( WPWING_WCPDF_DOCUMENT_SAVE_DIR . $document->save_path );
				if ( $real && $base && 0 === strpos( $real, $base . DIRECTORY_SEPARATOR ) ) {
					$files[ basename( $real ) ] = $real;
				}
			}

			if ( ! $files ) {
				return;
			}

			$tmp = wp_tempnam( 'wpwing-wcpdf-bulk' );
			if ( ! $tmp ) {
				return;
			}

			if ( class_exists( 'ZipArchive' ) ) {
				$zip = new ZipArchive();
				if ( true !== $zip->open( $tmp, ZipArchive::OVERWRITE ) ) {
					wp_delete_file( $tmp );
					return;
				}
				foreach ( $files as $name => $path ) {
					$zip->addFile( $path, $name );
				}
				$zip->close();
			} else {
				require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
				$archive = new PclZip( $tmp );
				// @phpstan-ignore-next-line -- PclZip::create() accepts variadic option pairs.
				$result = $archive->create( implode( ',', array_values( $files ) ), PCLZIP_OPT_REMOVE_ALL_PATH );
				if ( 0 === $result ) {
					wp_delete_file( $tmp );
					return;
				}
			}

			$filename = ( 'invoice' === $document_type ? 'invoices-' : 'packing-slips-' ) . wp_date( 'Y-m-d' ) . '.zip';

			nocache_headers();
			header( 'Content-Type: application/zip' );
			header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
			header( 'Content-Length: ' . filesize( $tmp ) );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
			readfile( $tmp );
			wp_delete_file( $tmp );
			exit();
		}

		/**
		 * Render the given documents into one PDF (page break between documents),
		 * stream it to the browser, and exit. Returns without output on failure.
		 *
		 * @param WPWing_WcPdf_Document[] $documents     Documents with existing PDF files.
		 * @param string                  $document_type 'invoice' or 'packing'.
		 * @since 1.10.0
		 */
		private function stream_merged_pdf( $documents, $document_type ) {
			$first_html = '';
			$bodies     = array();

			foreach ( $documents as $document ) {
				$html = $this->plugin->render_document_html( $document );
				if ( ! $html || ! preg_match( '/<body[^>]*>(.*)<\/body>/is', $html, $matches ) ) {
					continue;
				}
				if ( '' === $first_html ) {
					$first_html = $html;
				}
				$bodies[] = $matches[1];
			}

			// Reuse the first document's skeleton (doctype, head, styles) around the combined bodies.
			if ( ! $bodies || ! preg_match( '/^(.*<body[^>]*>).*(<\/body>.*)$/is', $first_html, $shell ) ) {
				return;
			}

			$merged_html = $shell[1]
				. implode( '<div style="page-break-before: always;"></div>', $bodies )
				. $shell[2];

			try {
				$pdf = WPWing_WcPdf_Document::render_pdf_from_html( $merged_html );
			} catch ( \Throwable $e ) {
				wc_get_logger()->error(
					sprintf( 'WPWing PDF Invoice: bulk merged PDF failed - %s', $e->getMessage() ),
					array( 'source' => 'wpwing-pdf-invoice' )
				);
				return;
			}

			$filename = ( 'invoice' === $document_type ? 'invoices-' : 'packing-slips-' ) . wp_date( 'Y-m-d' ) . '.pdf';

			nocache_headers();
			header( 'Content-Type: application/pdf' );
			header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
			header( 'Content-Length: ' . strlen( $pdf ) );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary PDF stream.
			echo $pdf;
			exit();
		}

		/**
		 * Add invoice status column to the orders list table.
		 *
		 * @param array $columns Existing table columns.
		 * @return array
		 */
		public function add_order_list_column( $columns ) {
			$new_columns = array();
			foreach ( $columns as $key => $label ) {
				$new_columns[ $key ] = $label;
				if ( 'order_status' === $key ) {
					$new_columns['wpwing_invoice'] = __( 'Invoice', 'wpwing-wcpdf' );
				}
			}
			return $new_columns;
		}

		/**
		 * Render the invoice status column cell.
		 *
		 * Works for both classic (post ID int) and HPOS (WC_Order object) screens.
		 *
		 * @param string       $column      Column key.
		 * @param int|WC_Order $order_or_id Order ID or WC_Order object.
		 */
		public function render_order_list_column( $column, $order_or_id ) {
			if ( 'wpwing_invoice' !== $column ) {
				return;
			}

			$order_id = $order_or_id instanceof WC_Order ? $order_or_id->get_id() : intval( $order_or_id );
			$invoice  = $this->plugin->get_document_by_type( $order_id, 'invoice' );

			if ( null !== $invoice && $invoice->exists ) {
				printf(
					'<span class="dashicons dashicons-yes-alt" title="%s"></span>',
					esc_attr__( 'Invoice generated', 'wpwing-wcpdf' )
				);
			} else {
				$url = wp_nonce_url(
					add_query_arg( 'wpwing-create-invoice', $order_id ),
					'wpwing_create_invoice_' . $order_id
				);
				printf(
					'<a href="%s" title="%s"><span class="dashicons dashicons-plus-alt2"></span></a>',
					esc_url( $url ),
					esc_attr__( 'Create Invoice', 'wpwing-wcpdf' )
				);
			}
		}
	}
}
