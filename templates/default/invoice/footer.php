<?php if ( isset( $notes ) ) : ?>
	<div class="notes">
		<span class="notes-title"><?php _e("Notes", 'wpwing-wc-pdf-invoice'); ?></span>
		<span><?php echo nl2br( $notes ); ?></span>
	</div>
<?php endif; ?>

<?php if ( isset( $footer ) ) : ?>
	<footer>
		<span><?php echo nl2br( $footer ); ?></span>
	</footer>
<?php endif; ?>