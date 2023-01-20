<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Gateway_PPCP_AngellEYE_Subscriptions_Helper {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function angelleye_ppcp_is_save_payment_token($current, $order_id) {
        if ((!empty($_POST['wc-angelleye_ppcp_cc-new-payment-method']) && $_POST['wc-angelleye_ppcp_cc-new-payment-method'] == true) || $this->is_subscription($order_id) || $this->angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item()) {
            return true;
        }
        if ((!empty($_POST['wc-angelleye_ppcp-new-payment-method']) && $_POST['wc-angelleye_ppcp-new-payment-method'] == true) || $this->is_subscription($order_id) || $this->angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item()) {
            return true;
        }
        return false;
    }

    public function save_payment_token($order, $payment_tokens_id) {
        $order_id = version_compare(WC_VERSION, '3.0', '<') ? $order->id : $order->get_id();
        if (function_exists('wcs_order_contains_subscription') && wcs_order_contains_subscription($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_order($order_id);
        } elseif (function_exists('wcs_order_contains_renewal') && wcs_order_contains_renewal($order_id)) {
            $subscriptions = wcs_get_subscriptions_for_renewal_order($order_id);
        } else {
            $subscriptions = array();
        }
        if (!empty($subscriptions)) {
            foreach ($subscriptions as $subscription) {
                $subscription_id = version_compare(WC_VERSION, '3.0', '<') ? $subscription->id : $subscription->get_id();
                update_post_meta($subscription_id, '_payment_tokens_id', $payment_tokens_id);
            }
        } else {
            update_post_meta($order_id, '_payment_tokens_id', $payment_tokens_id);
        }
    }

    public function angelleye_ppcp_wc_save_payment_token($order_id, $api_response) {
        if ($this->angelleye_ppcp_is_save_payment_token($this, $order_id)) {
            $payment_token = isset($api_response['payment_source']['card']['attributes']['vault']['id']) ? $api_response['payment_source']['card']['attributes']['vault']['id'] : '';
            if (empty($payment_token)) {
                $payment_token = isset($api_response['payment_source']['paypal']['attributes']['vault']['id']) ? $api_response['payment_source']['paypal']['attributes']['vault']['id'] : '';
            }
            if (!empty($_POST['wc-angelleye_ppcp_cc-payment-token']) && $_POST['wc-angelleye_ppcp_cc-payment-token'] != 'new') {
                $token_id = wc_clean($_POST['wc-angelleye_ppcp_cc-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                $order->add_payment_token($token);
                if ($this->is_subscription($order_id)) {
                    $this->save_payment_token($order, $payment_token);
                }
            } elseif (!empty($_POST['wc-angelleye_ppcp-payment-token']) && $_POST['wc-angelleye_ppcp-payment-token'] != 'new') {
                $token_id = wc_clean($_POST['wc-angelleye_ppcp-payment-token']);
                $token = WC_Payment_Tokens::get($token_id);
                $order->add_payment_token($token);
                if ($this->is_subscription($order_id)) {
                    $this->save_payment_token($order, $payment_token);
                }
            } else {
                if (!empty($api_response['payment_source']['card']['attributes']['vault']['id'])) {
                    $token = new WC_Payment_Token_CC();
                    $order = wc_get_order($order_id);
                    if (0 != $order->get_user_id()) {
                        $customer_id = $order->get_user_id();
                    } else {
                        $customer_id = get_current_user_id();
                    }
                    $token->set_token($payment_token);
                    $token->set_gateway_id($order->get_payment_method());
                    $token->set_card_type($api_response['payment_source']['card']['brand']);
                    $token->set_last4($api_response['payment_source']['card']['last_digits']);
                    $token->set_expiry_month('05');
                    $token->set_expiry_year('2025');
                    $token->set_user_id($customer_id);
                    if ($token->validate()) {
                        $this->save_payment_token($order, $payment_token);
                        $save_result = $token->save();
                        if ($save_result) {
                            $order->add_payment_token($token);
                        }
                    } else {
                        $order->add_order_note('ERROR MESSAGE: ' . __('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
                    }
                } elseif (!empty($api_response['payment_source']['paypal']['attributes']['vault']['id'])) {
                    $token = new WC_Payment_Token_CC();
                    $order = wc_get_order($order_id);
                    if (0 != $order->get_user_id()) {
                        $customer_id = $order->get_user_id();
                    } else {
                        $customer_id = get_current_user_id();
                    }
                    $token->set_token($payment_token);
                    $token->set_gateway_id($order->get_payment_method());
                    $token->set_card_type('PayPal Billing Agreement');
                    $token->set_last4(substr($payment_token, -4));
                    $token->set_expiry_month(date('m'));
                    $token->set_expiry_year(date('Y', strtotime('+20 years')));
                    $token->set_user_id($customer_id);
                    if ($token->validate()) {
                        $this->save_payment_token($order, $payment_token);
                        $save_result = $token->save();
                        if ($save_result) {
                            $order->add_payment_token($token);
                        }
                    } else {
                        $order->add_order_note('ERROR MESSAGE: ' . __('Invalid or missing payment token fields.', 'paypal-for-woocommerce'));
                    }
                }
            }
        }
    }

    public function is_subscription($order_id) {
        return ( function_exists('wcs_order_contains_subscription') && ( wcs_order_contains_subscription($order_id) || wcs_is_subscription($order_id) || wcs_order_contains_renewal($order_id) ) );
    }

    public function angelleye_paypal_for_woo_wc_autoship_cart_has_autoship_item() {
        if (!function_exists('WC')) {
            return false;
        }
        $cart = WC()->cart;
        if (empty($cart)) {
            return false;
        }
        $has_autoship_items = false;
        foreach ($cart->get_cart() as $item) {
            if (isset($item['wc_autoship_frequency'])) {
                $has_autoship_items = true;
                break;
            }
        }
        return $has_autoship_items;
    }
}
