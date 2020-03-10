<?php
/**
 * Admin Options Template
 */

defined( 'ABSPATH' ) || exit;
?>

<h2>
	<?php echo esc_html( $gateway->get_method_title() ); ?>
	<?php wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); ?>
</h2>
<?php echo wp_kses_post( wpautop( $gateway->get_method_description() ) ); ?>
<p>
	<?php
	echo sprintf(
		wp_kses_post( /* translators: 1: url 2: url */ __( 'Go to <a href="%1$s" target="_blank">%2$s</a> to create an account.', 'paxful-payments' ) ),
		'https://paxful.com/pay-with-paxful',
		'https://paxful.com/pay-with-paxful'
	);
	?>
</p>
<p>
	<?php
	echo sprintf(
		wp_kses_post( /* translators: %s: URL */ __( 'Download configuration guide <a href="%s" target="_blank">here</a>', 'paxful-payments' ) ),
		'https://paxful.com/pwp-plugin-manual/woocommerce.pdf'
	);
	?>
</p>

<table class="form-table">
	<?php $gateway->generate_settings_html( $gateway->get_form_fields(), true ); ?>
</table>
