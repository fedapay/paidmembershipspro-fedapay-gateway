<?php

function pmpro_fedapay_admin_enqueue_scripts() {
    $admin_css = plugins_url('css/admin.css',dirname(__FILE__) );

    wp_enqueue_style('pmpro_fedapay_admin', $admin_css, array(), PMPRO_FEDAPAY_GATEWAY_VERWION, 'screen');
}

add_action( 'admin_enqueue_scripts', 'pmpro_fedapay_admin_enqueue_scripts' );

/**
 * Enable 
 */
function pmpro_currencies_xof( $currencies ) {
    if (empty($currencies['XOF'])) {
        $currencies['XOF'] = array(
            'name' => __( 'West Africa FCFA (XOF)', 'pmpro-fedapay-gateway' ),
            'decimals' => '0',
            'thousands_separator' => '&nbsp;',
            'decimal_separator' => ',',
            'symbol' => 'FCFA&nbsp;',
            'position' => 'left',
        );
    }

	return $currencies;
}

add_filter( 'pmpro_currencies', 'pmpro_currencies_xof' );
