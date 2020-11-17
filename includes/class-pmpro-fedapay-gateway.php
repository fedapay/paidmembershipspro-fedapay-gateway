<?php
//load classes init method
add_action('init', array('PMProGateway_fedapay', 'init'));

/**
 * PMProGateway_fedapay Class
 *
 * Handles fedapay integration.
 *
 */
class PMProGateway_fedapay extends PMProGateway
{
    function PMProGateway($gateway = NULL)
    {
        $this->gateway = $gateway;
        return $this->gateway;
    }

    /**
     * Run on WP init
     *
     * @since 1.8
     */
    static function init()
    {
        //make sure fedapay is a gateway option
        add_filter('pmpro_gateways', array('PMProGateway_fedapay', 'pmpro_gateways'));

        //add fields to payment settings
        add_filter('pmpro_payment_options', array('PMProGateway_fedapay', 'pmpro_payment_options'));
        add_filter('pmpro_payment_option_fields', array('PMProGateway_fedapay', 'pmpro_payment_option_fields'), 10, 2);

        // add some fields to edit user page (Updates)
        add_action('pmpro_after_membership_level_profile_fields', array('PMProGateway_fedapay', 'user_profile_fields'));
        // add_action('profile_update', array('PMProGateway_fedapay', 'user_profile_fields_save'));

        // //updates cron
        // add_action('pmpro_activation', array('PMProGateway_fedapay', 'pmpro_activation'));
        // add_action('pmpro_deactivation', array('PMProGateway_fedapay', 'pmpro_deactivation'));
        // add_action('pmpro_cron_fedapay_subscription_updates', array('PMProGateway_fedapay', 'pmpro_cron_fedapay_subscription_updates'));

        // //code to add at checkout if fedapay is the current gateway
        $gateway = pmpro_getOption('gateway');

        if ($gateway == 'fedapay') {
            add_action('pmpro_checkout_preheader', array('PMProGateway_fedapay', 'pmpro_checkout_preheader'));
            add_filter('pmpro_checkout_order', array('PMProGateway_fedapay', 'pmpro_checkout_order'));
            add_filter('pmpro_include_payment_information_fields', '__return_false');
        }
    }

    /**
     * Make sure fedapay is in the gateways list
     *
     * @since 1.8
     */
    static function pmpro_gateways($gateways)
    {
        if (empty($gateways['fedapay'])) {
            $gateways['fedapay'] = __('FedaPay', 'pmpro-fedapay-gateway');
        }

        return $gateways;
    }

    /**
     * Get a list of payment options that the fedapay gateway needs/supports.
     *
     * @since 1.8
     */
    static function getGatewayOptions()
    {
        $options = array(
            'sslseal',
            'nuclear_HTTPS',
            'gateway_environment',
            'fedapay_secretkey',
            'fedapay_publickey',
            'currency',
            'use_ssl',
        );

        return $options;
    }

    /**
     * Set payment options for payment settings page.
     *
     * @since 1.8
     */
    static function pmpro_payment_options($options)
    {
        //get fedapay options
        $fedapay_options = PMProGateway_fedapay::getGatewayOptions();

        //merge with others.
        $options = array_merge($fedapay_options, $options);

        return $options;
    }

    /**
     * Display fields for fedapay options.
     *
     * @since 1.8
     */
    static function pmpro_payment_option_fields($values, $gateway)
    {
?>
        <tr class="pmpro_settings_divider gateway gateway_fedapay" <?php if ($gateway != "fedapay") { ?>style="display: none;" <?php } ?>>
            <td colspan="2">
                <?php _e('FedaPay Settings', 'pmpro-fedapay-gateway'); ?>
            </td>
        </tr>
        <tr class="gateway gateway_fedapay" <?php if ($gateway != "fedapay") { ?>style="display: none;" <?php } ?>>
            <th scope="row" valign="top">
                <label for="fedapay_publickey"><?php _e('Public Key', 'paid-memberships-pro'); ?>:</label>
            </th>
            <td>
                <input type="text" id="fedapay_publickey" name="fedapay_publickey" value="<?php echo esc_attr($values['fedapay_publickey']) ?>" class="regular-text code" />
                <?php
                $public_key_prefix = substr($values['fedapay_publickey'], 0, 3);
                if (!empty($values['fedapay_publickey']) && $public_key_prefix != 'pk_') {
                ?>
                    <p class="pmpro_red"><strong><?php _e('Your Public Key appears incorrect.', 'paid-memberships-pro'); ?></strong></p>
                <?php
                }
                ?>
            </td>
        </tr>
        <tr class="gateway gateway_fedapay" <?php if ($gateway != "fedapay") { ?>style="display: none;" <?php } ?>>
            <th scope="row" valign="top">
                <label for="fedapay_secretkey"><?php _e('Secret Key', 'paid-memberships-pro'); ?>:</label>
            </th>
            <td>
                <input type="text" id="fedapay_secretkey" name="fedapay_secretkey" value="<?php echo esc_attr($values['fedapay_secretkey']) ?>" class="regular-text code" />
            </td>
        </tr>

        <script>
            // TO fix currencies filed display issue
            jQuery(function() {
                pmpro_changeGateway(jQuery('#gateway').val());
            });
        </script>
<?php
    }

    /**
     * Code added to checkout preheader.
     *
     * @since 2.1
     */
    static function pmpro_checkout_preheader()
    {
        // Enqueue Checkout Script
    }


    /**
     * Filtering orders at checkout.
     *
     * @since 1.8
     */
    static function pmpro_checkout_order($morder)
    {
        return $morder;
    }

    /**
     * Code to run after checkout
     *
     * @since 1.8
     */
    static function pmpro_after_checkout($user_id, $morder)
    {
    }

    function process(&$order)
    {
        //check for initial payment
        if (floatval($order->InitialPayment) == 0) {
            //auth first, then process
            if ($this->authorize($order)) {
                $this->void($order);
                if (!pmpro_isLevelTrial($order->membership_level)) {
                    //subscription will start today with a 1 period trial (initial payment charged separately)
                    $order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
                    $order->TrialBillingPeriod = $order->BillingPeriod;
                    $order->TrialBillingFrequency = $order->BillingFrequency;
                    $order->TrialBillingCycles = 1;
                    $order->TrialAmount = 0;

                    //add a billing cycle to make up for the trial, if applicable
                    if (!empty($order->TotalBillingCycles))
                        $order->TotalBillingCycles++;
                } elseif ($order->InitialPayment == 0 && $order->TrialAmount == 0) {
                    //it has a trial, but the amount is the same as the initial payment, so we can squeeze it in there
                    $order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
                    $order->TrialBillingCycles++;

                    //add a billing cycle to make up for the trial, if applicable
                    if ($order->TotalBillingCycles)
                        $order->TotalBillingCycles++;
                } else {
                    //add a period to the start date to account for the initial payment
                    $order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
                }

                $order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
                return $this->subscribe($order);
            } else {
                if (empty($order->error))
                    $order->error = __("Unknown error: Authorization failed.", "pmpro");
                return false;
            }
        } else {
            //charge first payment
            if ($this->charge($order)) {
                //set up recurring billing
                if (pmpro_isLevelRecurring($order->membership_level)) {
                    if (!pmpro_isLevelTrial($order->membership_level)) {
                        //subscription will start today with a 1 period trial
                        $order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
                        $order->TrialBillingPeriod = $order->BillingPeriod;
                        $order->TrialBillingFrequency = $order->BillingFrequency;
                        $order->TrialBillingCycles = 1;
                        $order->TrialAmount = 0;

                        //add a billing cycle to make up for the trial, if applicable
                        if (!empty($order->TotalBillingCycles))
                            $order->TotalBillingCycles++;
                    } elseif ($order->InitialPayment == 0 && $order->TrialAmount == 0) {
                        //it has a trial, but the amount is the same as the initial payment, so we can squeeze it in there
                        $order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
                        $order->TrialBillingCycles++;

                        //add a billing cycle to make up for the trial, if applicable
                        if (!empty($order->TotalBillingCycles))
                            $order->TotalBillingCycles++;
                    } else {
                        //add a period to the start date to account for the initial payment
                        $order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $this->BillingFrequency . " " . $this->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
                    }

                    $order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
                    if ($this->subscribe($order)) {
                        return true;
                    } else {
                        if ($this->void($order)) {
                            if (!$order->error)
                                $order->error = __("Unknown error: Payment failed.", "pmpro");
                        } else {
                            if (!$order->error)
                                $order->error = __("Unknown error: Payment failed.", "pmpro");

                            $order->error .= " " . __("A partial payment was made that we could not void. Please contact the site owner immediately to correct this.", "pmpro");
                        }

                        return false;
                    }
                } else {
                    //only a one time charge
                    $order->status = "success";    //saved on checkout page
                    return true;
                }
            } else {
                if (empty($order->error))
                    $order->error = __("Unknown error: Payment failed.", "pmpro");

                return false;
            }
        }
    }

    /**
     * Run an authorization at the gateway.
     * Required if supporting recurring subscriptions
     * since we'll authorize $1 for subscriptions
     * with a $0 initial payment.
     */
    function authorize(&$order)
    {
        //create a code for the order
        if (empty($order->code))
            $order->code = $order->getRandomCode();

        //code to authorize with gateway and test results would go here

        //simulate a successful authorization
        $order->payment_transaction_id = "TEST" . $order->code;
        $order->updateStatus("authorized");
        return true;
    }

    /**
     * Void a transaction at the gateway.
     * Required if supporting recurring transactions
     * as we void the authorization test on subs
     * with a $0 initial payment and void the initial
     * payment if subscription setup fails.
     */
    function void(&$order)
    {
        //need a transaction id
        if (empty($order->payment_transaction_id))
            return false;

        //code to void an order at the gateway and test results would go here

        //simulate a successful void
        $order->payment_transaction_id = "TEST" . $order->code;
        $order->updateStatus("voided");
        return true;
    }

    /*
     * Make a charge at the gateway.
     * Required to charge initial payments.
     */
    function charge(&$order)
    {
        //create a code for the order
        if (empty($order->code))
            $order->code = $order->getRandomCode();

        //code to charge with gateway and test results would go here

        //simulate a successful charge
        $order->payment_transaction_id = "TEST" . $order->code;
        $order->updateStatus("success");
        return true;
    }

    /*
            Setup a subscription at the gateway.

            Required if supporting recurring subscriptions.
        */
    function subscribe(&$order)
    {
        //create a code for the order
        if (empty($order->code))
            $order->code = $order->getRandomCode();

        //filter order before subscription. use with care.
        $order = apply_filters("pmpro_subscribe_order", $order, $this);

        //code to setup a recurring subscription with the gateway and test results would go here

        //simulate a successful subscription processing
        $order->status = "success";
        $order->subscription_transaction_id = "TEST" . $order->code;
        return true;
    }

    /*
     * Update billing at the gateway.
     * Required if supporting recurring subscriptions and
     * processing credit cards on site.
     */
    function update(&$order)
    {
        //code to update billing info on a recurring subscription at the gateway and test results would go here

        //simulate a successful billing update
        return true;
    }

    /*
     * Cancel a subscription at the gateway.
     * Required if supporting recurring subscriptions.
     */
    function cancel(&$order)
    {
        //require a subscription id
        if (empty($order->subscription_transaction_id))
            return false;

        //code to cancel a subscription at the gateway and test results would go here

        //simulate a successful cancel
        $order->updateStatus("cancelled");
        return true;
    }

    /*
     * Get subscription status at the gateway.
     * Optional if you have code that needs this or
     * want to support addons that use this.
     */
    function getSubscriptionStatus(&$order)
    {
        //require a subscription id
        if (empty($order->subscription_transaction_id))
            return false;

        //code to get subscription status at the gateway and test results would go here

        //this looks different for each gateway, but generally an array of some sort
        return array();
    }

    /*
     * Get transaction status at the gateway.
     * Optional if you have code that needs this or
     * want to support addons that use this.
    */
    function getTransactionStatus(&$order)
    {
        //code to get transaction status at the gateway and test results would go here

        //this looks different for each gateway, but generally an array of some sort
        return array();
    }
}
