// Captures WordPress.org listing screenshots and regenerates readme.txt's Screenshots section from the same list. Run via `make screenshots`.
'use strict';

const fs = require( 'fs' );
const path = require( 'path' );
const { chromium } = require( '@playwright/test' );

const BASE_URL = process.env.WP_BASE_URL || 'https://pdf-invoice.local';
const REPO_ROOT = path.join( __dirname, '..' );
const OUT_DIR = path.join( REPO_ROOT, 'wporg-assets' );
const README_PATH = path.join( REPO_ROOT, 'src', 'readme.txt' );
const VIEWPORT = { width: 1280, height: 800 };

function shotPath( file ) {
	return path.join( OUT_DIR, file );
}

async function login( page ) {
	await page.goto( `${ BASE_URL }/wp-login.php` );
	if ( page.url().includes( 'wp-login.php' ) ) {
		await page.fill( '#user_login', 'admin' );
		await page.fill( '#user_pass', 'password' );
		await page.click( '#wp-submit' );
		await page.waitForURL( /wp-admin/ );
	}
}

async function hideAdminNotices( page ) {
	await page.addStyleTag( {
		content:
			'#wpbody-content .notice, #wpbody-content .updated, #wpbody-content .error, .update-nag { display: none !important; }',
	} );
}

async function ensureSampleOrderDocuments( page ) {
	await page.goto( `${ BASE_URL }/wp-admin/post.php?post=57&action=edit` );
	await page.waitForSelector( '#wpwing-pdf-invoice-box' );

	const docTypes = [ 'invoice', 'packing', 'delivery', 'shipping-label' ];
	for ( const type of docTypes ) {
		const createBtn = page.locator( `a[href*="wpwing-create-${ type }"]` );
		if ( ( await createBtn.count() ) > 0 ) {
			await createBtn.first().click();
			await page.waitForLoadState( 'networkidle' );
			await page.waitForSelector( '#wpwing-pdf-invoice-box' );
		}
	}
}

async function getPreviewHref( page, type ) {
	await page.goto( `${ BASE_URL }/wp-admin/post.php?post=57&action=edit` );
	await page.waitForSelector( '#wpwing-pdf-invoice-box' );
	const link = page.locator( `a[href*="wpwing-preview-html-${ type }"]` ).first();
	await link.waitFor( { state: 'attached' } );
	const href = await link.getAttribute( 'href' );
	return href.startsWith( 'http' ) ? href : `${ BASE_URL }${ href.startsWith( '/' ) ? '' : '/' }${ href }`;
}

const SHOTS = [
	{
		file: 'screenshot-1.png',
		caption: 'PDF Invoice template preview',
		capture: async ( page ) => {
			const href = await getPreviewHref( page, 'invoice' );
			await page.goto( href );
			await page.locator( '.invoice-document' ).first().waitFor( { state: 'visible' } );
			await page.waitForLoadState( 'networkidle' );
			await page.screenshot( { path: shotPath( 'screenshot-1.png' ) } );
		},
	},
	{
		file: 'screenshot-2.png',
		caption: 'PDF Packing Slip template preview',
		capture: async ( page ) => {
			const href = await getPreviewHref( page, 'packing' );
			await page.goto( href );
			await page.locator( '.invoice-document' ).first().waitFor( { state: 'visible' } );
			await page.waitForLoadState( 'networkidle' );
			await page.screenshot( { path: shotPath( 'screenshot-2.png' ) } );
		},
	},
	{
		file: 'screenshot-3.png',
		caption: 'WooCommerce order details page with PDF invoice metabox actions',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/wp-admin/post.php?post=57&action=edit` );
			await page.waitForSelector( '#wpwing-pdf-invoice-box' );
			await hideAdminNotices( page );
			await page.screenshot( { path: shotPath( 'screenshot-3.png' ) } );
		},
	},
	{
		file: 'screenshot-4.png',
		caption: 'General settings screen - numbering format, automation, and email attachments',
		capture: async ( page ) => {
			await page.goto(
				`${ BASE_URL }/wp-admin/admin.php?page=wpwing-pdf-invoice-packing-slip-for-woocommerce-settings`
			);
			const tabBtn = page.locator( '.wpwing-wcpdf-setting-nav-tab[data-target="wpwing_pdf_general"]' );
			await tabBtn.waitFor( { state: 'visible' } );
			await tabBtn.click();
			await page.locator( '#wpwing_pdf_general' ).waitFor( { state: 'visible' } );
			await hideAdminNotices( page );
			await page.screenshot( { path: shotPath( 'screenshot-4.png' ) } );
		},
	},
	{
		file: 'screenshot-5.png',
		caption: 'Template settings screen with live interactive preview panel',
		capture: async ( page ) => {
			await page.goto(
				`${ BASE_URL }/wp-admin/admin.php?page=wpwing-pdf-invoice-packing-slip-for-woocommerce-settings`
			);
			const tabBtn = page.locator( '.wpwing-wcpdf-setting-nav-tab[data-target="wpwing_pdf_template"]' );
			await tabBtn.waitFor( { state: 'visible' } );
			await tabBtn.click();
			await page.locator( '#invoice_preview_btn-field' ).waitFor( { state: 'visible' } );
			await hideAdminNotices( page );
			await page.click( '#invoice_preview_btn-field' );
			const frame = page.frameLocator( '#wpwing-preview-frame' );
			await frame.locator( '.invoice-document' ).first().waitFor( { state: 'visible' } );
			await page.waitForTimeout( 500 );
			await page.screenshot( { path: shotPath( 'screenshot-5.png' ) } );
		},
	},
	{
		file: 'screenshot-6.png',
		caption: 'Delivery Note template preview',
		capture: async ( page ) => {
			const href = await getPreviewHref( page, 'delivery' );
			await page.goto( href );
			await page.locator( '.invoice-document' ).first().waitFor( { state: 'visible' } );
			await page.waitForLoadState( 'networkidle' );
			await page.screenshot( { path: shotPath( 'screenshot-6.png' ) } );
		},
	},
	{
		file: 'screenshot-7.png',
		caption: 'Shipping Label with QR code template preview',
		capture: async ( page ) => {
			const href = await getPreviewHref( page, 'shipping-label' );
			await page.goto( href );
			await page.locator( '.invoice-document' ).first().waitFor( { state: 'visible' } );
			await page.waitForLoadState( 'networkidle' );
			await page.screenshot( { path: shotPath( 'screenshot-7.png' ) } );
		},
	},
	{
		file: 'screenshot-8.png',
		caption: 'WooCommerce orders list with quick PDF action buttons',
		capture: async ( page ) => {
			await page.goto( `${ BASE_URL }/wp-admin/edit.php?post_type=shop_order` );
			await page.locator( '.wp-list-table' ).waitFor( { state: 'visible' } );
			await hideAdminNotices( page );
			await page.screenshot( { path: shotPath( 'screenshot-8.png' ) } );
		},
	},
];

function updateReadme() {
	const readme = fs.readFileSync( README_PATH, 'utf8' );
	const list = SHOTS.map(
		( shot, i ) => `${ i + 1 }. ${ shot.caption }`
	).join( '\n' );
	const pattern = /== Screenshots ==\n\n[\s\S]*?(?=\n== )/;
	if ( ! pattern.test( readme ) ) {
		throw new Error(
			'Could not find a "== Screenshots ==" section in readme.txt to update.'
		);
	}
	const updated = readme.replace(
		pattern,
		() => `== Screenshots ==\n\n${ list }\n`
	);
	fs.writeFileSync( README_PATH, updated );
}

async function main() {
	fs.mkdirSync( OUT_DIR, { recursive: true } );

	const browser = await chromium.launch();
	const page = await (
		await browser.newContext( {
			viewport: VIEWPORT,
			ignoreHTTPSErrors: true,
		} )
	).newPage();

	await login( page );
	await ensureSampleOrderDocuments( page );

	for ( const shot of SHOTS ) {
		process.stdout.write(
			`Capturing ${ shot.file } (${ shot.caption })... `
		);
		try {
			await shot.capture( page );
		} catch {
			process.stdout.write( 'retrying... ' );
			await shot.capture( page );
		}
		console.log( 'done' );
	}

	await browser.close();
	updateReadme();
	console.log(
		`\nWrote ${ SHOTS.length } screenshots to wporg-assets/ and updated readme.txt.`
	);
}

main().catch( ( err ) => {
	console.error( '\nscreenshot capture failed:', err );
	process.exit( 1 );
} );
