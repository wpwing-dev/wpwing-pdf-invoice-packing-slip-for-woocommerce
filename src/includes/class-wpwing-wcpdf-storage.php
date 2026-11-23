<?php
/**
 * Storage cleanup and maintenance routines for WPWing PDF Invoices.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Storage' ) ) {

	/**
	 * WPWing_WcPdf_Storage Class
	 *
	 * Manages scheduled storage maintenance, temporary export cleanup,
	 * thumbnail cache purging, and PDF retention policies.
	 *
	 * @since 1.20.0
	 */
	class WPWing_WcPdf_Storage {

		/**
		 * Cron hook name for daily cleanup.
		 *
		 * @var string
		 */
		const CRON_HOOK = 'wpwing_wcpdf_daily_storage_cleanup';

		/**
		 * Initialize storage maintenance hooks.
		 *
		 * @since 1.20.0
		 */
		public static function init() {
			add_action( self::CRON_HOOK, array( __CLASS__, 'run_daily_cleanup' ) );

			if ( function_exists( 'wp_next_scheduled' ) && ! wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
			}
		}

		/**
		 * Clear scheduled cleanup on plugin deactivation.
		 *
		 * @since 1.20.0
		 */
		public static function clear_cron() {
			if ( function_exists( 'wp_clear_scheduled_hook' ) ) {
				wp_clear_scheduled_hook( self::CRON_HOOK );
			}
		}

		/**
		 * Execute daily storage maintenance.
		 *
		 * Cleans up:
		 * 1. Stale temporary bulk export archives older than 24 hours.
		 * 2. Stale thumbnail cache files older than 30 days.
		 * 3. Generated PDF/XML documents older than configured retention threshold (if enabled).
		 *
		 * @since 1.20.0
		 * @return void
		 */
		public static function run_daily_cleanup() {
			$purged = array(
				'temp'   => self::cleanup_temp_files(),
				'thumbs' => self::cleanup_thumbnails(),
				'docs'   => self::cleanup_expired_documents(),
			);

			if ( ( $purged['temp'] > 0 || $purged['thumbs'] > 0 || $purged['docs'] > 0 ) && function_exists( 'wc_get_logger' ) ) {
				wc_get_logger()->info(
					sprintf(
						'WPWing PDF Invoice: daily storage cleanup completed. Purged %d temp archives, %d stale thumbnails, %d expired documents.',
						$purged['temp'],
						$purged['thumbs'],
						$purged['docs']
					),
					array( 'source' => 'wpwing-pdf-invoice' )
				);
			}
		}

		/**
		 * Purge temporary bulk export archives older than 24 hours.
		 *
		 * Inspects both the system temp directory and plugin document directory.
		 *
		 * @since 1.20.0
		 * @return int Number of temporary files deleted.
		 */
		public static function cleanup_temp_files() {
			$deleted = 0;
			$dirs    = array();

			if ( function_exists( 'get_temp_dir' ) ) {
				$dirs[] = get_temp_dir();
			} elseif ( function_exists( 'sys_get_temp_dir' ) ) {
				$dirs[] = sys_get_temp_dir();
			}

			if ( defined( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR' ) ) {
				$dirs[] = WPWING_WCPDF_DOCUMENT_SAVE_DIR;
			}

			$dirs = array_unique( array_filter( $dirs, 'is_dir' ) );
			$now  = time();

			foreach ( $dirs as $dir ) {
				$pattern = trailingslashit( $dir ) . 'wpwing-wcpdf*';
				$files   = glob( $pattern );
				if ( ! is_array( $files ) ) {
					continue;
				}

				foreach ( $files as $file ) {
					if ( ! is_file( $file ) ) {
						continue;
					}

					$mtime = filemtime( $file );
					if ( false !== $mtime && ( $now - $mtime ) >= DAY_IN_SECONDS ) {
						wp_delete_file( $file );
						++$deleted;
					}
				}
			}

			return $deleted;
		}

		/**
		 * Purge cached product thumbnails older than 30 days.
		 *
		 * @since 1.20.0
		 * @return int Number of cached thumbnail files deleted.
		 */
		public static function cleanup_thumbnails() {
			if ( ! defined( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR' ) ) {
				return 0;
			}

			$thumbs_dir = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . 'thumbs/';
			if ( ! is_dir( $thumbs_dir ) ) {
				return 0;
			}

			$deleted   = 0;
			$now       = time();
			$threshold = 30 * DAY_IN_SECONDS;
			$pattern   = trailingslashit( $thumbs_dir ) . 'thumb_*.png';
			$files     = glob( $pattern );

			if ( ! is_array( $files ) ) {
				return 0;
			}

			foreach ( $files as $file ) {
				if ( ! is_file( $file ) ) {
					continue;
				}

				$mtime = filemtime( $file );
				if ( false !== $mtime && ( $now - $mtime ) >= $threshold ) {
					wp_delete_file( $file );
					++$deleted;
				}
			}

			return $deleted;
		}

		/**
		 * Purge generated documents (PDF and XML) older than the configured retention threshold.
		 *
		 * Protected files (.htaccess, index.html) and thumbnail directory are skipped.
		 *
		 * @since 1.20.0
		 * @param int|null $retention_days Optional override for days. Defaults to setting value.
		 * @return int Number of expired document files deleted.
		 */
		public static function cleanup_expired_documents( $retention_days = null ) {
			if ( ! defined( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR' ) ) {
				return 0;
			}

			$save_dir = WPWING_WCPDF_DOCUMENT_SAVE_DIR;
			if ( ! is_dir( $save_dir ) ) {
				return 0;
			}

			if ( null === $retention_days ) {
				$settings       = class_exists( 'WPWing_WcPdf_Settings' ) ? WPWing_WcPdf_Settings::get_instance() : null;
				$retention_days = $settings ? absint( $settings->get_option( 'pdf_retention_days' ) ) : 0;
			} else {
				$retention_days = absint( $retention_days );
			}

			if ( $retention_days <= 0 ) {
				return 0;
			}

			$deleted   = 0;
			$threshold = time() - ( $retention_days * DAY_IN_SECONDS );
			$base_real = realpath( $save_dir );

			if ( ! $base_real ) {
				return 0;
			}

			try {
				$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator( $save_dir, FilesystemIterator::SKIP_DOTS ),
					RecursiveIteratorIterator::CHILD_FIRST
				);

				foreach ( $iterator as $item ) {
					if ( ! $item->isFile() ) {
						continue;
					}

					$path = $item->getPathname();
					$real = realpath( $path );

					if ( ! $real || 0 !== strpos( $real, $base_real . DIRECTORY_SEPARATOR ) ) {
						continue;
					}

					// Never delete protected web server configuration or index shields.
					$filename = $item->getFilename();
					if ( in_array( $filename, array( '.htaccess', 'index.html', 'index.php' ), true ) ) {
						continue;
					}

					// Skip thumbs subdirectory.
					if ( false !== strpos( $path, DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR ) ) {
						continue;
					}

					// Only purge document formats.
					$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
					if ( ! in_array( $ext, array( 'pdf', 'xml' ), true ) ) {
						continue;
					}

					$mtime = $item->getMTime();
					if ( $mtime < $threshold ) {
						wp_delete_file( $path );
						++$deleted;
					}
				}
			} catch ( \Throwable $e ) {
				if ( function_exists( 'wc_get_logger' ) ) {
					wc_get_logger()->error(
						sprintf( 'WPWing PDF Invoice: retention cleanup error - %s', $e->getMessage() ),
						array( 'source' => 'wpwing-pdf-invoice' )
					);
				}
			}

			return $deleted;
		}

		/**
		 * Collect storage diagnostics and usage metrics.
		 *
		 * @since 1.20.0
		 * @return array{
		 *     document_count: int,
		 *     document_bytes: int,
		 *     thumb_count: int,
		 *     thumb_bytes: int,
		 *     total_bytes: int,
		 *     formatted_total: string,
		 *     retention_days: int
		 * }
		 */
		public static function get_storage_stats() {
			$doc_count   = 0;
			$doc_bytes   = 0;
			$thumb_count = 0;
			$thumb_bytes = 0;

			$save_dir = defined( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR' ) ? WPWING_WCPDF_DOCUMENT_SAVE_DIR : '';

			if ( ! empty( $save_dir ) && is_dir( $save_dir ) ) {
				$base_real = realpath( $save_dir );
				if ( $base_real ) {
					try {
						$iterator = new RecursiveIteratorIterator(
							new RecursiveDirectoryIterator( $save_dir, FilesystemIterator::SKIP_DOTS ),
							RecursiveIteratorIterator::CHILD_FIRST
						);

						foreach ( $iterator as $item ) {
							if ( ! $item->isFile() ) {
								continue;
							}

							$path = $item->getPathname();
							$real = realpath( $path );

							if ( ! $real || 0 !== strpos( $real, $base_real . DIRECTORY_SEPARATOR ) ) {
								continue;
							}

							$filename = $item->getFilename();
							if ( in_array( $filename, array( '.htaccess', 'index.html', 'index.php' ), true ) ) {
								continue;
							}

							$is_thumb = false !== strpos( $path, DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR );
							$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

							if ( $is_thumb && 'png' === $ext ) {
								++$thumb_count;
								$thumb_bytes += (int) $item->getSize();
							} elseif ( in_array( $ext, array( 'pdf', 'xml' ), true ) ) {
								++$doc_count;
								$doc_bytes += (int) $item->getSize();
							}
						}
					} catch ( \Throwable $e ) {
						unset( $e ); // Discard exception and return partial stats.
					}
				}
			}

			$settings       = class_exists( 'WPWing_WcPdf_Settings' ) ? WPWing_WcPdf_Settings::get_instance() : null;
			$retention_days = $settings ? absint( $settings->get_option( 'pdf_retention_days' ) ) : 0;
			$total_bytes    = $doc_bytes + $thumb_bytes;

			$formatted = function_exists( 'size_format' )
				? (string) size_format( $total_bytes, 2 )
				: sprintf( '%.2f MB', $total_bytes / ( 1024 * 1024 ) );

			return array(
				'document_count'  => $doc_count,
				'document_bytes'  => $doc_bytes,
				'thumb_count'     => $thumb_count,
				'thumb_bytes'     => $thumb_bytes,
				'total_bytes'     => $total_bytes,
				'formatted_total' => $formatted,
				'retention_days'  => $retention_days,
			);
		}
	}
}
