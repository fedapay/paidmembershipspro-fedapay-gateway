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
    public function PMProGateway($gateway = NULL)
    {
        $this->gateway = $gateway;
        return $this->gateway;
    }

    /**
     * @var bool    Is the FedaPay/PHP Library loaded
     */
    private static $is_loaded = false;

    /**
     * FedaPay Class Constructor
     *
     * @since 1.4
     */
    function __construct($gateway = null)
    {
        $this->gateway = $gateway;
        $this->gateway_environment = pmpro_getOption('gateway_environment');

        if (true === $this->dependencies()) {
            $this->loadFedaPayLibrary();
            FedaPay\FedaPay::setApiKey(pmpro_getOption('fedapay_secretkey'));

            if ($this->gateway_environment == 'sandbox') {
                \FedaPay\FedaPay::setEnvironment('sandbox');
            } else {
                \FedaPay\FedaPay::setEnvironment('live');
            }

            self::$is_loaded = true;
        }

        return $this->gateway;
    }

    /**
     * Load the FedaPay API library.
     *
     * @since 1.8
     * Moved into a method in version 1.8 so we only load it when needed.
     */
    function loadFedaPayLibrary()
    {
        //load FedaPay library if it hasn't been loaded already (usually by another plugin using FedaPay)
        if (!class_exists('FedaPay\FedaPay')) {
            require_once(PMPRO_FEDAPAY_GATEWAY_DIR . "/vendor/fedapay-php/init.php");
        }
    }

    /**
     * Warn if required extensions aren't loaded.
     *
     * @return bool
     * @since 1.8.6.8.1
     * @since 1.8.13.6 - Add json dependency
     */
    public static function dependencies()
    {
        global $msg, $msgt, $pmpro_FedaPay_error;

        if (version_compare(PHP_VERSION, '5.5.0', '<')) {

            $pmpro_FedaPay_error = true;
            $msg = -1;
            $msgt = sprintf(__("The FedaPay Gateway requires PHP 5.3.29 or greater. We recommend upgrading to PHP %s or greater. Ask your host to upgrade.", "pmpro-fedapay-gateway"), PMPRO_PHP_MIN_VERSION);

            if (!is_admin()) {
                pmpro_setMessage($msgt, "pmpro_error");
            }

            return false;
        }

        $modules = array('curl', 'mbstring', 'json');

        foreach ($modules as $module) {
            if (!extension_loaded($module)) {
                $pmpro_FedaPay_error = true;
                $msg = -1;
                $msgt = sprintf(__("The %s gateway depends on the %s PHP extension. Please enable it, or ask your hosting provider to enable it.", 'pmpro-fedapay-gateway'), 'FedaPay', $module);

                //throw error on checkout page
                if (!is_admin()) {
                    pmpro_setMessage($msgt, 'pmpro_error');
                }

                return false;
            }
        }

        self::$is_loaded = true;

        return true;
    }

    /**
     * Run on WP init
     *
     * @since 1.8
     */
    public static function init()
    {
        //make sure fedapay is a gateway option
        add_filter('pmpro_gateways', array('PMProGateway_fedapay', 'pmpro_gateways'));

        //add fields to payment settings
        add_filter('pmpro_payment_options', array('PMProGateway_fedapay', 'pmpro_payment_options'));
        add_filter('pmpro_payment_option_fields', array('PMProGateway_fedapay', 'pmpro_payment_option_fields'), 10, 2);

        add_action('wp_ajax_fedapay_webhook', array('PMProGateway_fedapay', 'fedapay_webhook'));
        add_action('wp_ajax_nopriv_fedapay_webhook', array('PMProGateway_fedapay', 'fedapay_webhook'));

        // //code to add at checkout if fedapay is the current gateway
        $gateway = pmpro_getOption('gateway');

        if ($gateway == 'fedapay') {
            add_filter('pmpro_include_payment_information_fields', '__return_false');
            add_filter('pmpro_required_billing_fields', array('PMProGateway_fedapay', 'pmpro_required_billing_fields'));
            add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_fedapay', 'pmpro_checkout_before_change_membership_level'), 10, 2);
        }
    }

    /**
     * Make sure fedapay is in the gateways list
     *
     * @since 1.8
     */
    public static function pmpro_gateways($gateways)
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
    public static function getGatewayOptions()
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
    public static function pmpro_payment_options($options)
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
    public static function pmpro_payment_option_fields($values, $gateway)
    {
        $environment = pmpro_getOption('gateway_environment');
?>
        <tr class="pmpro_settings_divider gateway gateway_fedapay" <?php if ($gateway != "fedapay") { ?>style="display: none;" <?php } ?>>
            <td colspan="2">
                <?php _e('FedaPay Settings', 'pmpro-fedapay-gateway'); ?>
            </td>
        </tr>
        <tr class="gateway gateway_fedapay" <?php if ($gateway != "fedapay") { ?>style="display: none;" <?php } ?>>
            <th scope="row" valign="top">
                <label for="fedapay_publickey"><?php _e('Public Key', 'pmpro-fedapay-gateway'); ?>:</label>
            </th>
            <td>
                <input type="text" id="fedapay_publickey" name="fedapay_publickey" value="<?php echo esc_attr($values['fedapay_publickey']) ?>" class="regular-text code" />
                <?php
                $public_key_prefix = substr($values['fedapay_publickey'], 0, 3);
                if (!empty($values['fedapay_publickey']) && $public_key_prefix != 'pk_') {
                ?>
                    <p class="pmpro_red"><strong><?php _e('Your Public Key appears incorrect.', 'pmpro-fedapay-gateway'); ?></strong></p>
                <?php
                }
                ?>
            </td>
        </tr>
        <tr class="gateway gateway_fedapay" <?php if ($gateway != "fedapay") { ?>style="display: none;" <?php } ?>>
            <th scope="row" valign="top">
                <label for="fedapay_secretkey"><?php _e('Secret Key', 'pmpro-fedapay-gateway'); ?>:</label>
            </th>
            <td>
                <input type="text" id="fedapay_secretkey" name="fedapay_secretkey" value="<?php echo esc_attr($values['fedapay_secretkey']) ?>" class="regular-text code" />
            </td>
        </tr>

        <tr class="gateway gateway_fedapay" <?php if ($gateway != "fedapay") { ?>style="display: none;" <?php } ?>>
            <th scope="row" valign="top">
                <label><?php _e('Webhook URL', 'pmpro-fedapay-gateway'); ?>:</label>
            </th>
            <td>
                <p>
                    <?php
                    if ($environment === 'sandbox') {
                        $url = 'https://sandbox.fedapay.com/webhooks';
                    } else {
                        $url = 'https://live.fedapay.com/webhooks';
                    }
                    echo sprintf(__('Here is your Webhook URL for reference. You should set this in your FedaPay dashboard <a href="%s" target="_blank">here</a>.', 'pmpro-fedapay-gateway'), $url);
                    ?>
                    <pre><?php echo add_query_arg('action', 'fedapay_webhook', admin_url('admin-ajax.php')); ?></pre>
                </p>
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
     * Remove required billing fields
     *
     * @since 1.8
     */
    public static function pmpro_required_billing_fields($fields)
    {
        unset($fields['baddress1']);
        unset($fields['bcity']);
        unset($fields['bstate']);
        unset($fields['bzipcode']);
        unset($fields['CardType']);
        unset($fields['AccountNumber']);
        unset($fields['ExpirationMonth']);
        unset($fields['ExpirationYear']);
        unset($fields['CVV']);

        return $fields;
    }

    /**
     * Instead of change membership levels, send users to FedaPay to pay.
     *
     * @param int           $user_id
     * @param \MemberOrder  $morder
     *
     * @since 1.8
     */
    public static function pmpro_checkout_before_change_membership_level($user_id, $morder)
    {
        //if no order, no need to pay
        if (empty($morder))
            return;

        $morder->user_id = $user_id;
        $morder->saveOrder();

        do_action('pmpro_before_send_to_fedapay', $user_id, $morder);

        $morder->Gateway->process_payment($morder);
    }

    private static function pmpro_webhook_change_membership_level(&$morder)
    {
        //filter for level
        $morder->membership_level = apply_filters("pmpro_ipnhandler_level", $morder->membership_level, $morder->user_id);

        //set the start date to current_time('timestamp') but allow filters  (documented in preheaders/checkout.php)
        $startdate = apply_filters("pmpro_checkout_start_date", "'" . current_time('mysql') . "'", $morder->user_id, $morder->membership_level);

        //fix expiration date
        if (!empty($morder->membership_level->expiration_number)) {
            $enddate = "'" . date_i18n('Y-m-d', strtotime('+ ' . $morder->membership_level->expiration_number . ' ' . $morder->membership_level->expiration_period, current_time('timestamp'))) . "'";
        } else {
            $enddate = 'NULL';
        }

        //filter the enddate (documented in preheaders/checkout.php)
        $enddate = apply_filters("pmpro_checkout_end_date", $enddate, $morder->user_id, $morder->membership_level, $startdate);

        //get discount code
        $morder->getDiscountCode();
        if (!empty($morder->discount_code)) {
            //update membership level
            $morder->getMembershipLevel(true);
            $discount_code_id = $morder->discount_code->id;
        } else {
            $discount_code_id = "";
        }

        //custom level to change user to
        $custom_level = array(
            'user_id'         => $morder->user_id,
            'membership_id'   => $morder->membership_level->id,
            'code_id'         => $discount_code_id,
            'initial_payment' => $morder->membership_level->initial_payment,
            'billing_amount'  => $morder->membership_level->billing_amount,
            'cycle_number'    => $morder->membership_level->cycle_number,
            'cycle_period'    => $morder->membership_level->cycle_period,
            'billing_limit'   => $morder->membership_level->billing_limit,
            'trial_amount'    => $morder->membership_level->trial_amount,
            'trial_limit'     => $morder->membership_level->trial_limit,
            'startdate'       => $startdate,
            'enddate'         => $enddate
        );

        //change level and continue "checkout"
        if (pmpro_changeMembershipLevel($custom_level, $morder->user_id, 'changed') !== false) {
            //update order status and transaction ids
            $morder->status = 'success';
            $morder->saveOrder();

            //hook
            do_action('pmpro_after_checkout', $morder->user_id, $morder);

            //setup some values for the emails
            if (!empty($morder)) {
                $invoice = new MemberOrder($morder->id);
            } else {
                $invoice = null;
            }

            $user = get_userdata($morder->user_id);
            $user->membership_level = $morder->membership_level; // Make sure they have the right level info

            //send email to member
            $pmproemail = new PMProEmail();
            $pmproemail->sendCheckoutEmail($user, $invoice);

            //send email to admin
            $pmproemail = new PMProEmail();
            $pmproemail->sendCheckoutAdminEmail($user, $invoice);

            return true;
        } else {
            return false;
        }
    }

    public function process(&$order)
    {
        if (empty($order->code))
            $order->code = $order->getRandomCode();

        //clean up a couple values
        $order->payment_type = "FedaPay";
        $order->CardType = "";
        $order->cardtype = "";

        //just save, the user will go to FedaPay to pay
        $order->status = "review";
        $order->saveOrder();

        return true;
    }

    /*
     * Cancel a subscription at the gateway.
     * Required if supporting recurring subscriptions.
     */
    public function cancel(&$order)
    {
        //require a subscription id
        if (empty($order->subscription_transaction_id))
            return false;

        //code to cancel a subscription at the gateway and test results would go here

        //simulate a successful cancel
        $order->updateStatus("cancelled");
        return true;
    }

    public function process_payment(&$order)
    {
        global $pmpro_currency;

        $initial_payment = $order->InitialPayment;
        $initial_payment_tax = $order->getTaxForPrice($initial_payment);
        $amount = pmpro_round_price((float)$initial_payment + (float)$initial_payment_tax);
        $firstname = $order->FirstName;
        $lastname = $order->LastName;
        $email = $order->Email;
        $description = apply_filters('pmpro_paypal_level_description', substr($order->membership_level->name . " at " . get_bloginfo("name"), 0, 127), $order->membership_level->name, $order, get_bloginfo("name"));

        $callback_url = add_query_arg('level', $order->membership_level->id, pmpro_url('confirmation'));

        if ($pmpro_currency !== 'XOF') {
            $error = __("FedaPay only supports XOF as currency for now. Please select XOF currrency or contact the store manager.", 'pmpro-fedapay-gateway');
            $order->error = $error;
            $order->shorterror = $error;
            return false;
        }

        try {
            $transaction = \FedaPay\Transaction::create(array(
                'description' => $description,
                'amount' => $amount,
                'currency' => array('iso' => $pmpro_currency),
                'callback_url' => $callback_url,
                'customer' => [
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email
                ]
            ));

            $order->payment_transaction_id = $transaction->id;
            $order->saveOrder();

            wp_redirect($transaction->generateToken()->url);
            exit;
        } catch (\Exception $e) {
            $errorMessage = $this->fedapayError($e);
            $order->error = $errorMessage;
            $order->shorterror = $errorMessage;
            return false;
        }
    }

    /**
     * Webhooks callback
     */
    public static function fedapay_webhook()
    {
        $body = @file_get_contents('php://input');
        $event = json_decode($body, false);

        if ($event->name === 'transaction.approved') {
            $morder = self::get_order_by_event($event);

            if (!empty($morder->id)) {

                //get some more order info
                $morder->getMembershipLevel();
                $morder->getUser();

                self::pmpro_webhook_change_membership_level($morder);
            }
        }
    }

    /**
     * Get order by webhook event
     */
    private static function get_order_by_event($event)
    {
        //pause here to give PMPro a chance to finish checkout
        sleep(PMPRO_FEDAPAY_WEBHOOK_DELAY);

        $order = new MemberOrder();
        $order->getMemberOrderByPaymentTransactionID($event->entity->id);

        if (!empty($order->id)) {
            return $order;
        } else {
            return false;
        }
    }

    /**
     * Display payment request errors
     * @param \Exception $e
     */
    private function fedapayError(\Exception $e)
    {
        $errorMessage = 'Payment error: ' . $e->getMessage();

        if ($e instanceof \FedaPay\Error\ApiConnection && $e->hasErrors()) {
            foreach ($e->getErrors() as $key => $errors) {
                foreach ($errors as $error) {
                    $errorMessage .= '<br/>' . $key . ' ' . $error;
                }
            }
        }

        return $errorMessage;
    }
}
