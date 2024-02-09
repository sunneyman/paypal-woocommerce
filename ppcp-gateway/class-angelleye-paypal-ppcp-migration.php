<?php

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * @since      1.0.0
 * @package    AngellEYE_PayPal_PPCP_Migration
 * @subpackage AngellEYE_PayPal_PPCP_Migration/includes
 * @author     AngellEYE <andrew@angelleye.com>
 */
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Migration {

    protected static $_instance = null;
    public $setting_obj;

    // Define class constants for better readability
    const SUBSCRIPTION_BATCH_LIMIT = 100;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->angelleye_ppcp_load_class();
        add_action('angelleye_ppcp_migration_schedule', array($this, 'process_subscription_batch'), 10, 2);
        $this->process_subscription_batch('paypal_express', 'ppcp');
    }

    public function angelleye_ppcp_load_class() {
        try {
            // Check if the necessary class exists before including it
            if (!class_exists('WC_Gateway_PPCP_AngellEYE_Settings')) {
                include_once PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . '/ppcp-gateway/class-wc-gateway-ppcp-angelleye-settings.php';
            }
            $this->setting_obj = WC_Gateway_PPCP_AngellEYE_Settings::instance();
        } catch (Exception $ex) {
            // Handle exceptions if needed
        }
    }
    
    public function angelleye_ppcp_paypal_express_to_ppcp($seller_onboarding_status) {
        try {
            $this->angelleye_express_checkout_setting_field_map();
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_pro_to_ppcp($seller_onboarding_status) {
        $woocommerce_paypal_pro_settings = get_option('woocommerce_paypal_pro_settings');
        $woocommerce_paypal_pro_settings['enabled'] = 'no';

        $gateway_settings_key_array = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');

        foreach ($gateway_settings_key_array as $gateway_settings_value) {
            if (!empty($woocommerce_paypal_pro_settings[$gateway_settings_value])) {
                $woocommerce_paypal_pro_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_pro_settings[$gateway_settings_value], $action = 'e');
            }
        }

        update_option('woocommerce_paypal_pro_settings', $woocommerce_paypal_pro_settings);
    }

    public function angelleye_ppcp_paypal_pro_payflow_to_ppcp($seller_onboarding_status) {
        $woocommerce_paypal_pro_payflow_settings = get_option('woocommerce_paypal_pro_payflow_settings');
        $woocommerce_paypal_pro_payflow_settings['enabled'] = 'no';

        $gateway_settings_key_array = array('sandbox_paypal_vendor', 'sandbox_paypal_password', 'sandbox_paypal_user', 'sandbox_paypal_partner', 'paypal_vendor', 'paypal_password', 'paypal_user', 'paypal_partner');

        foreach ($gateway_settings_key_array as $gateway_settings_value) {
            if (!empty($woocommerce_paypal_pro_payflow_settings[$gateway_settings_value])) {
                $woocommerce_paypal_pro_payflow_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_pro_payflow_settings[$gateway_settings_value], $action = 'e');
            }
        }

        update_option('woocommerce_paypal_pro_payflow_settings', $woocommerce_paypal_pro_payflow_settings);
    }

    public function angelleye_ppcp_paypal_advanced_to_ppcp($seller_onboarding_status) {
        $woocommerce_paypal_advanced_settings = get_option('woocommerce_paypal_advanced_settings');
        $woocommerce_paypal_advanced_settings['enabled'] = 'no';

        $gateway_settings_key_array = array('loginid', 'resellerid', 'user', 'password');

        foreach ($gateway_settings_key_array as $gateway_settings_value) {
            if (!empty($woocommerce_paypal_advanced_settings[$gateway_settings_value])) {
                $woocommerce_paypal_advanced_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_advanced_settings[$gateway_settings_value], $action = 'e');
            }
        }

        update_option('woocommerce_paypal_advanced_settings', $woocommerce_paypal_advanced_settings);
    }

    public function angelleye_ppcp_paypal_credit_card_rest_to_ppcp($seller_onboarding_status) {
        $woocommerce_paypal_credit_card_rest_settings = get_option('woocommerce_paypal_credit_card_rest_settings');
        $woocommerce_paypal_credit_card_rest_settings['enabled'] = 'no';

        $gateway_settings_key_array = array('rest_client_id_sandbox', 'rest_secret_id_sandbox', 'rest_client_id', 'rest_secret_id');

        foreach ($gateway_settings_key_array as $gateway_settings_value) {
            if (!empty($woocommerce_paypal_credit_card_rest_settings[$gateway_settings_value])) {
                $woocommerce_paypal_credit_card_rest_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_credit_card_rest_settings[$gateway_settings_value], $action = 'e');
            }
        }

        update_option('woocommerce_paypal_credit_card_rest_settings', $woocommerce_paypal_credit_card_rest_settings);
    }

    public function angelleye_ppcp_paypal_to_ppcp() {
        $woocommerce_paypal_settings = get_option('woocommerce_paypal_settings');
        $woocommerce_paypal_settings['enabled'] = 'no';
        update_option('woocommerce_paypal_settings', $woocommerce_paypal_settings);
    }

    public function angelleye_ppcp_ppec_paypal_to_ppcp() {
        $woocommerce_ppec_paypal_settings = get_option('woocommerce_ppec_paypal_settings');
        $woocommerce_ppec_paypal_settings['enabled'] = 'no';
        update_option('woocommerce_ppec_paypal_settings', $woocommerce_ppec_paypal_settings);
    }

    public function angelleye_express_checkout_setting_field_map() {
        try {
            $woocommerce_paypal_express_settings = get_option('woocommerce_paypal_express_settings');
            $woocommerce_angelleye_ppcp_settings = get_option('woocommerce_angelleye_ppcp_settings');
            $map_fields_list = array('enable_cart_button' => 'show_on_cart',
                'cart_button_position' => 'button_position',
                'enable_paypal_checkout_page' => 'show_on_checkout',
                'checkout_page_display_option' => 'show_on_checkout',
                'enable_product_button' => 'show_on_product_page',
                'landing_page' => 'landing_page',
                'brand_name' => 'brand_name',
                'soft_descriptor' => 'softdescriptor',
                'error_email_notification' => 'error_email_notify',
                'invoice_prefix' => 'invoice_id_prefix',
                'skip_final_review' => 'skip_final_review',
                'disable_term' => 'disable_term',
                'paymentaction' => 'payment_action',
                'set_billing_address' => 'billing_address',
                'send_items' => 'send_items',
                'debug' => 'debug',
                'product_style_color' => 'button_color',
                'cart_style_color' => 'button_color',
                'checkout_style_color' => 'button_color',
                'product_style_shape' => 'button_shape',
                'cart_style_shape' => 'button_shape',
                'checkout_style_shape' => 'button_shape',
                'product_button_tagline' => 'button_tagline',
                'cart_button_tagline' => 'button_tagline',
                'checkout_button_tagline' => 'button_tagline',
                'single_product_configure_settings' => '',
                'product_button_layout' => 'single_product_button_layout',
                'product_button_size' => 'single_product_button_size',
                'product_button_height' => 'single_product_button_height',
                'product_button_label' => 'single_product_button_label',
                'product_disallowed_funding_methods' => 'single_product_disallowed_funding_methods',
                'cart_configure_settings' => '',
                'cart_button_layout' => 'cart_button_layout',
                'cart_button_size' => 'cart_button_size',
                'cart_button_height' => 'cart_button_height',
                'cart_button_label' => 'cart_button_label',
                'cart_disallowed_funding_methods' => 'cart_disallowed_funding_methods',
                'checkout_page_configure_settings' => '',
                'checkout_disable_smart_button' => 'checkout_page_disable_smart_button',
                'checkout_button_layout' => 'checkout_page_button_layout',
                'checkout_button_size' => 'checkout_page_button_size',
                'checkout_button_height' => 'checkout_page_button_height',
                'checkout_button_label' => 'checkout_page_button_label',
                'checkout_disallowed_funding_methods' => 'checkout_page_disallowed_funding_methods',
                'enabled_pay_later_messaging' => 'enabled_credit_messaging',
                'pay_later_messaging_page_type' => 'credit_messaging_page_type',
                'pay_later_messaging_home_layout_type' => 'credit_messaging_home_layout_type',
                'pay_later_messaging_home_text_layout_logo_type' => 'credit_messaging_home_text_layout_logo_type',
                'pay_later_messaging_home_text_layout_logo_position' => 'credit_messaging_home_text_layout_logo_position',
                'pay_later_messaging_home_text_layout_text_size' => 'credit_messaging_home_text_layout_text_size',
                'pay_later_messaging_home_text_layout_text_color' => 'credit_messaging_home_text_layout_text_color',
                'pay_later_messaging_home_flex_layout_color' => 'credit_messaging_home_flex_layout_color',
                'pay_later_messaging_home_flex_layout_ratio' => 'credit_messaging_home_flex_layout_ratio',
                'pay_later_messaging_home_shortcode' => 'credit_messaging_home_shortcode',
                'pay_later_messaging_category_layout_type' => 'credit_messaging_category_layout_type',
                'pay_later_messaging_category_text_layout_logo_type' => 'credit_messaging_category_text_layout_logo_type',
                'pay_later_messaging_category_text_layout_logo_position' => 'credit_messaging_category_text_layout_logo_position',
                'pay_later_messaging_category_text_layout_text_size' => 'credit_messaging_category_text_layout_text_size',
                'pay_later_messaging_category_text_layout_text_color' => 'credit_messaging_category_text_layout_text_color',
                'pay_later_messaging_category_flex_layout_color' => 'credit_messaging_category_flex_layout_color',
                'pay_later_messaging_category_flex_layout_ratio' => 'credit_messaging_category_flex_layout_ratio',
                'pay_later_messaging_category_shortcode' => 'credit_messaging_category_shortcode',
                'pay_later_messaging_product_layout_type' => 'credit_messaging_product_layout_type',
                'pay_later_messaging_product_text_layout_logo_type' => 'credit_messaging_product_text_layout_logo_type',
                'pay_later_messaging_product_text_layout_logo_position' => 'credit_messaging_product_text_layout_logo_position',
                'pay_later_messaging_product_text_layout_text_size' => 'credit_messaging_product_text_layout_text_size',
                'pay_later_messaging_product_text_layout_text_color' => 'credit_messaging_product_text_layout_text_color',
                'pay_later_messaging_product_flex_layout_color' => 'credit_messaging_product_flex_layout_color',
                'pay_later_messaging_product_flex_layout_ratio' => 'credit_messaging_product_flex_layout_ratio',
                'pay_later_messaging_product_shortcode' => 'credit_messaging_product_shortcode',
                'pay_later_messaging_cart_layout_type' => 'credit_messaging_cart_layout_type',
                'pay_later_messaging_cart_text_layout_logo_type' => 'credit_messaging_cart_text_layout_logo_type',
                'pay_later_messaging_cart_text_layout_logo_position' => 'credit_messaging_cart_text_layout_logo_position',
                'pay_later_messaging_cart_text_layout_text_size' => 'credit_messaging_cart_text_layout_text_size',
                'pay_later_messaging_cart_text_layout_text_color' => 'credit_messaging_cart_text_layout_text_color',
                'pay_later_messaging_cart_flex_layout_color' => 'credit_messaging_cart_flex_layout_color',
                'pay_later_messaging_cart_flex_layout_ratio' => 'credit_messaging_cart_flex_layout_ratio',
                'pay_later_messaging_cart_shortcode' => 'credit_messaging_cart_shortcode',
                'pay_later_messaging_payment_layout_type' => 'credit_messaging_payment_layout_type',
                'pay_later_messaging_payment_text_layout_logo_type' => 'credit_messaging_payment_text_layout_logo_type',
                'pay_later_messaging_payment_text_layout_logo_position' => 'credit_messaging_payment_text_layout_logo_position',
                'pay_later_messaging_payment_text_layout_text_size' => 'credit_messaging_payment_text_layout_text_size',
                'pay_later_messaging_payment_text_layout_text_color' => 'credit_messaging_payment_text_layout_text_color',
                'pay_later_messaging_payment_flex_layout_color' => 'credit_messaging_payment_flex_layout_color',
                'pay_later_messaging_payment_flex_layout_ratio' => 'credit_messaging_payment_flex_layout_ratio',
                'pay_later_messaging_payment_shortcode' => 'credit_messaging_payment_shortcode'
            );
            if (!empty($woocommerce_paypal_express_settings)) {
                if (isset($woocommerce_paypal_express_settings['single_product_configure_settings']) && $woocommerce_paypal_express_settings['single_product_configure_settings'] === '') {
                    $map_fields_list['product_button_layout'] = 'button_layout';
                    $map_fields_list['product_button_size'] = 'button_size';
                    $map_fields_list['product_button_height'] = 'button_height';
                    $map_fields_list['product_button_label'] = 'button_label';
                    $map_fields_list['product_disallowed_funding_methods'] = 'disallowed_funding_methods';
                }
                if (isset($woocommerce_paypal_express_settings['cart_configure_settings']) && $woocommerce_paypal_express_settings['cart_configure_settings'] === '') {
                    $map_fields_list['cart_button_layout'] = 'button_layout';
                    $map_fields_list['cart_button_size'] = 'button_size';
                    $map_fields_list['cart_button_height'] = 'button_height';
                    $map_fields_list['cart_button_label'] = 'button_label';
                    $map_fields_list['cart_disallowed_funding_methods'] = 'disallowed_funding_methods';
                }
                if (isset($woocommerce_paypal_express_settings['checkout_page_configure_settings']) && $woocommerce_paypal_express_settings['checkout_page_configure_settings'] === '') {
                    $map_fields_list['checkout_button_layout'] = 'button_layout';
                    $map_fields_list['checkout_button_size'] = 'button_size';
                    $map_fields_list['checkout_button_height'] = 'button_height';
                    $map_fields_list['checkout_button_label'] = 'button_label';
                    $map_fields_list['checkout_disallowed_funding_methods'] = 'disallowed_funding_methods';
                }
                foreach ($map_fields_list as $key => $value) {
                    if (isset($woocommerce_paypal_express_settings[$value]) && !empty($woocommerce_paypal_express_settings[$value])) {
                        if (isset($woocommerce_paypal_express_settings[$value]) && 'false' === $woocommerce_paypal_express_settings[$value]) {
                            $woocommerce_angelleye_ppcp_settings[$key] = 'no';
                        } elseif (isset($woocommerce_paypal_express_settings[$value]) && 'true' === $woocommerce_paypal_express_settings[$value]) {
                            $woocommerce_angelleye_ppcp_settings[$key] = 'yes';
                        } else {
                            $woocommerce_angelleye_ppcp_settings[$key] = $woocommerce_paypal_express_settings[$value];
                        }
                    }
                }
                if ($woocommerce_angelleye_ppcp_settings['paymentaction'] === 'Sale') {
                    $woocommerce_angelleye_ppcp_settings['paymentaction'] = 'capture';
                } elseif ($woocommerce_angelleye_ppcp_settings['paymentaction'] === 'Authorization') {
                    $woocommerce_angelleye_ppcp_settings['paymentaction'] = 'authorize';
                } elseif ($woocommerce_angelleye_ppcp_settings['paymentaction'] === 'Order') {
                    $woocommerce_angelleye_ppcp_settings['paymentaction'] = 'capture';
                }
                if ($woocommerce_angelleye_ppcp_settings['debug'] === 'no') {
                    $woocommerce_angelleye_ppcp_settings['debug'] = 'disabled';
                } elseif ($woocommerce_angelleye_ppcp_settings['debug'] === 'yes') {
                    $woocommerce_angelleye_ppcp_settings['debug'] = 'everything';
                }
                if ($woocommerce_angelleye_ppcp_settings['landing_page'] === 'login') {
                    $woocommerce_angelleye_ppcp_settings['landing_page'] = 'LOGIN';
                } elseif ($woocommerce_angelleye_ppcp_settings['landing_page'] === 'billing') {
                    $woocommerce_angelleye_ppcp_settings['landing_page'] = 'BILLING';
                } else {
                    $woocommerce_angelleye_ppcp_settings['landing_page'] = 'NO_PREFERENCE';
                }
                if (isset($woocommerce_paypal_express_settings['show_on_checkout']) && $woocommerce_paypal_express_settings['show_on_checkout'] !== 'no') {
                    $woocommerce_angelleye_ppcp_settings['enable_paypal_checkout_page'] = 'yes';
                } else {
                    $woocommerce_angelleye_ppcp_settings['enable_paypal_checkout_page'] = '';
                }
                $woocommerce_paypal_express_settings['enabled'] = 'no';
                $paypal_api_keys = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');
                foreach ($paypal_api_keys as $gateway_settings_key => $gateway_settings_value) {
                    if (!empty($woocommerce_paypal_express_settings[$gateway_settings_value])) {
                        $woocommerce_paypal_express_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($woocommerce_paypal_express_settings[$gateway_settings_value], $action = 'e');
                    }
                }
                update_option('woocommerce_paypal_express_settings', $woocommerce_paypal_express_settings);
                update_option('woocommerce_angelleye_ppcp_settings', $woocommerce_angelleye_ppcp_settings);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_subscription_order_migration($from_payment_method, $to_payment_method) {
        try {
            $subscription_ids = $this->angelleye_ppcp_get_subscription_order_list($from_payment_method);

            // Check if subscription_ids is not empty before scheduling the next batch
            if (!empty($subscription_ids)) {
                $this->schedule_next_batch($from_payment_method, $to_payment_method);
            }
        } catch (Exception $ex) {
            // Handle exceptions if needed
        }
    }

    public function angelleye_ppcp_get_subscription_order_list($payment_method_id) {
        try {
            $args = array(
                'type' => 'shop_subscription',
                'limit' => self::SUBSCRIPTION_BATCH_LIMIT,
                'return' => 'ids',
                'orderby' => 'date',
                'order' => 'DESC'
            );

            if (OrderUtil::custom_orders_table_usage_is_enabled()) {
                $args['status'] = array('wc-active', 'wc-on-hold');
                $args['payment_method'] = $payment_method_id;
            } elseif (function_exists('wcs_get_orders_with_meta_query')) {
                $args['status'] = 'any';
                $args['meta_query'] = array(
                    array(
                        'key' => '_payment_method',
                        'value' => $payment_method_id,
                    ),
                );
            }

            return wc_get_orders($args);
        } catch (Exception $ex) {
            // Handle exceptions if needed
            return array();
        }
    }

    public function angelleye_ppcp_update_payment_method($subscription, $new_payment_method) {
        try {
            // Store current payment method details
            $old_payment_method = $subscription->get_payment_method();
            $old_payment_method_title = $subscription->get_payment_method_title();

            // Get payment method titles from settings or default to 'PayPal'
            $new_payment_method_title = $this->setting_obj->get('title', 'PayPal');

            // Trigger pre-update payment method action
            do_action('woocommerce_subscriptions_pre_update_payment_method', $subscription, $new_payment_method, $old_payment_method);

            // Trigger gateway status update hook
            WC_Subscriptions_Core_Plugin::instance()->get_gateways_handler_class()::trigger_gateway_status_updated_hook($subscription, 'cancelled');

            // Handle empty payment method titles
            $old_payment_method_title = empty($old_payment_method_title) ? $old_payment_method : $old_payment_method_title;
            $new_payment_method_title = empty($new_payment_method_title) ? $new_payment_method : $new_payment_method_title;

            $subscription->set_payment_method($new_payment_method);
            $subscription->set_payment_method_title($new_payment_method_title);
            
            $subscription->update_meta_data('_old_payment_method', $old_payment_method);
            $subscription->update_meta_data('_angelleye_ppcp_old_payment_method', $old_payment_method);
            $subscription->update_meta_data('_old_payment_method_title', $old_payment_method_title);

            

            // Apply filters for old and new payment method titles
            $old_payment_method_title = apply_filters('woocommerce_subscription_note_old_payment_method_title', $old_payment_method_title, $old_payment_method, $subscription);
            $new_payment_method_title = apply_filters('woocommerce_subscription_note_new_payment_method_title', $new_payment_method_title, $new_payment_method, $subscription);

            // Add order note about payment method change
            $note_message = sprintf(
                _x('Payment method changed from "%1$s" to "%2$s" by the Angelleye Migration.', '%1$s: old payment title, %2$s: new payment title', 'woocommerce-subscriptions'),
                $old_payment_method_title,
                $new_payment_method_title
            );
            $subscription->add_order_note($note_message);

            // Save changes and trigger relevant actions
            $subscription->save();
            do_action('woocommerce_subscription_payment_method_updated', $subscription, $new_payment_method, $old_payment_method);
            do_action('woocommerce_subscription_payment_method_updated_to_' . $new_payment_method, $subscription, $old_payment_method);

            if ($old_payment_method) {
                do_action('woocommerce_subscription_payment_method_updated_from_' . $old_payment_method, $subscription, $new_payment_method);
            }
        } catch (Exception $e) {
            // Handle exceptions and provide a user-friendly error message
            $error_message = sprintf(
                __('%1$sError:%2$s %3$s', 'woocommerce-subscriptions'),
                '<strong>',
                '</strong>',
                $e->getMessage()
            );
            $subscription->add_order_note($error_message);
            $subscription->add_order_note(__('An error occurred updating your subscription\'s payment method. Please contact us for assistance.', 'woocommerce-subscriptions'));
        }
    }

    public function is_angelleye_ppcp_old_payment_token_exist($user_subscription) {
        try {
            $meta_keys = [
                '_payment_tokens_id',
                'payment_token_id',
                '_ppec_billing_agreement_id',
                '_paypal_subscription_id'
            ];
            foreach ($meta_keys as $key) {
                $payment_tokens_id = $user_subscription->get_meta($key);
                if (!empty($payment_tokens_id)) {
                    return true;
                }
            }
            return false;
        } catch (Exception $ex) {
            return false;
        }
    }

    public function schedule_next_batch($from_payment_method, $to_payment_method) {
        try {
            $action_hook = 'angelleye_ppcp_migration_schedule';
            $scheduled_time = time();
           // as_schedule_single_action($scheduled_time, $action_hook, array($from_payment_method, $to_payment_method));
        } catch (Exception $ex) {
            // Handle exceptions if needed
        }
    }

    public function process_subscription_batch($from_payment_method, $to_payment_method) {
        try {
            $subscription_ids = $this->angelleye_ppcp_get_subscription_order_list($from_payment_method);

            // Check if subscription_ids is not empty before processing the batch
            if (!empty($subscription_ids)) {
                foreach ($subscription_ids as $subscription_id) {
                    $subscription = wcs_get_subscription($subscription_id);
                    if ($this->is_angelleye_ppcp_old_payment_token_exist($subscription)) {
                        $this->angelleye_ppcp_update_payment_method($subscription, $to_payment_method);
                    }
                }
                $this->schedule_next_batch($from_payment_method, $to_payment_method);
            }
        } catch (Exception $ex) {
            // Handle exceptions if needed
        }
    }
}
