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
		'https://paxful.com/paxful-pay',
		'https://paxful.com/paxful-pay'
	);
	?>
</p>
<p>
	<?php
	echo sprintf(
		wp_kses_post( /* translators: %s: URL */ __( 'Check the configuration guide <a href="%s" target="_blank">here</a>', 'paxful-payments' ) ),
		'https://paxful.com/support/en-us/articles/360020524999-Paxful-Pay-WooCommerce-Plugin-Guide'
	);
	?>
</p>

<table class="form-table">
	<?php $gateway->generate_settings_html( $gateway->get_form_fields(), true ); ?>
</table>

<table class="wc_table widefat" cellspacing="0">
    <tbody>
    <tr>
        <td><h4>
                Purchase Completed Event Callback Url
            </h4></td>
        <td>
            <pre>
    <?php
    echo get_site_url() . "/wc-api/WC_Gateway_Paxful/";
    ?>
</pre>
        </td>
    </tr>
    <tr>
        <td><h4>Default Woo-Commerce Thank you page</h4></td>
        <td><pre>
    <?php
    echo wc_get_endpoint_url( 'order-received', '', get_permalink( get_option( 'woocommerce_checkout_page_id' ) ) );
    ?>
</pre>
        </td>
    </tr>
    </tbody>
</table>