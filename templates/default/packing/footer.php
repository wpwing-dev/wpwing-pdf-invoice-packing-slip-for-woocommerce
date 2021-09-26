<?php if ( isset( $notes ) ) : ?>
	<div class="notes">
		<span class="notes-title"><?php _e("Notes", 'wpwing-wc-pdf-invoice'); ?></span>
		<span><?php echo nl2br( esc_html( $notes ) ); ?></span>
	</div>
<?php endif; ?>

<?php if ( isset( $footer ) ) : ?>
	<footer>
		<span><?php echo nl2br( esc_html( $footer ) ); ?></span>
	</footer>
<?php endif; ?>