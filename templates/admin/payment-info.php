<?php
/**
 * Payment Info Template
 */

defined( 'ABSPATH' ) || exit;

?>
<div>
    <strong><?php esc_html_e( 'Payment Info', 'paxful-payments' ); ?></strong>
    <br/>
    <strong><?php esc_html_e( 'Track ID', 'paxful-payments' ); ?>:</strong> <?php echo esc_html( $track_id ); ?>
    <br/>
</div>
