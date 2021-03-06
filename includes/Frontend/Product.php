<?php

namespace SpringDevs\WcSubscription\Frontend;

use SpringDevs\WcSubscription\Illuminate\Helper;

/**
 * Product class
 * control single product page
 */
class Product
{

    public function __construct()
    {
        add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'change_single_add_to_cart_text'));
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'change_single_add_to_cart_text'));
        add_filter('woocommerce_get_price_html', array($this, 'change_price_html'), 10, 2);
        add_filter('woocommerce_cart_item_price', array($this, 'change_price_cart_html'), 10, 3);
        add_filter('woocommerce_cart_item_subtotal', array($this, 'change_price_cart_html'), 10, 3);
        add_action('woocommerce_cart_totals_after_order_total', array($this, 'add_rows_order_total'));
        add_action('woocommerce_review_order_after_order_total', array($this, 'add_rows_order_total'));
        add_action('woocommerce_review_order_before_cart_contents', array($this, 'change_cart_calculates'));
        add_action('woocommerce_before_cart_totals', array($this, 'change_cart_calculates'));
        add_action('woocommerce_cart_calculate_fees', [$this, 'add_cart_fee']);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_to_cart_item_data'], 10, 3);
        add_filter('woocommerce_is_purchasable', [$this, "check_if_purchasable"], 10, 2);
        add_filter('woocommerce_loop_add_to_cart_link', [$this, "remove_button_active_products"], 10, 2);
        add_action('woocommerce_single_product_summary', [$this, "text_if_active"]);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_order_item_product_meta'], 10, 4);
        add_filter('woocommerce_cart_get_total', [$this, 'calculates_cart_total']);
    }

    public function calculates_cart_total($total)
    {
        $cart_items = WC()->cart->cart_contents;
        foreach ($cart_items as $cart_item) {
            $conditional_key = apply_filters('subscrpt_filter_checkout_conditional_key', $cart_item['product_id'], $cart_item);
            $post_meta = get_post_meta($conditional_key, 'subscrpt_general', true);
            $has_trial = Helper::Check_Trial($conditional_key);
            if (is_array($post_meta) && $post_meta['enable']) {
                if (!empty($post_meta['trial_time']) && $post_meta['trial_time'] > 0 && $has_trial) {
                    if (isset($cart_item["line_subtotal"])) $total = $total - $cart_item["line_subtotal"];
                }
            }
        }
        return $total;
    }

    public function add_cart_fee($cart)
    {
        $cart_items = WC()->cart->cart_contents;
        $signup_fee = 0;
        foreach ($cart_items as $cart_item) {
            $conditional_key = apply_filters('subscrpt_filter_checkout_conditional_key', $cart_item['product_id'], $cart_item);
            $post_meta = get_post_meta($conditional_key, 'subscrpt_general', true);
            if (is_array($post_meta) && $post_meta['enable']) :
                $has_trial = Helper::Check_Trial($conditional_key);
                if ($has_trial && isset($post_meta['signup_fee'])) $signup_fee += (int)$post_meta['signup_fee'];
            endif;
        }
        if ($signup_fee > 0) $cart->add_fee("SignUp Fee", $signup_fee);
    }

    public function save_order_item_product_meta($item, $cart_item_key, $cart_item, $order)
    {
        if (isset($cart_item['renew_subscrpt'])) $item->update_meta_data('_renew_subscrpt', $cart_item['renew_subscrpt']);
    }

    public function text_if_active()
    {
        global $product;
        if ($product->is_type('variable')) return;
        $post_meta = get_post_meta($product->get_id(), 'subscrpt_general', true);
        $unexpired = Helper::Check_un_expired($product->get_id());
        if (is_array($post_meta) && isset($post_meta['limit'])) {
            if ($post_meta['limit'] == "unlimited") return;
            if ($post_meta['limit'] == "one") if (!$unexpired) return;
            if ($post_meta['limit'] == "only_one") {
                if (!subscrpt_check_trial($product->get_id())) {
                    echo '<strong>' . __('You Already Purchased These Product!', 'sdevs_wea') . '</strong>';
                }
            }
        }
        if ($unexpired) echo '<strong>' . __('You Already Purchased These Product!', 'sdevs_wea') . '</strong>';
    }

    public function remove_button_active_products($button, $product)
    {
        if ($product->is_type('variable') && !sdevs_is_pro_module_activate('subscription-pro')) return $button;
        $unexpired = Helper::Check_un_expired($product->get_id());
        if ($unexpired) return;
        return $button;
    }

    public function check_if_purchasable($is_purchasable, $product)
    {
        if ($product->is_type('variable')) return $is_purchasable;
        $unexpired = Helper::Check_un_expired($product->get_id());
        if ($unexpired) return false;
        return $is_purchasable;
    }

    public function add_to_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        $expired = Helper::CheckExpired($product_id);
        if ($expired) $cart_item_data['renew_subscrpt'] = true;
        return $cart_item_data;
    }

    public function change_cart_calculates($cart)
    {
        $cart_items = WC()->cart->cart_contents;
        foreach ($cart_items as $cart_item) {
            $conditional_key = apply_filters('subscrpt_filter_checkout_conditional_key', $cart_item['product_id'], $cart_item);
            $post_meta = get_post_meta($conditional_key, 'subscrpt_general', true);
            $has_trial = Helper::Check_Trial($conditional_key);
            if (is_array($post_meta) && $post_meta['enable']) {
                if (!empty($post_meta['trial_time']) && $post_meta['trial_time'] > 0 && $has_trial) {
                    $subtotal = WC()->cart->get_subtotal() - $cart_item["line_subtotal"];
                    // $total = WC()->cart->total - $cart_item["line_subtotal"];
                    WC()->cart->set_subtotal($subtotal);
                    // WC()->cart->set_total($total);
                }
            }
        }
    }

    public function change_single_add_to_cart_text($text)
    {
        global $product;
        if (!$product) return $text;
        if ($product->is_type('variable')) return $text;
        $post_meta = get_post_meta($product->get_id(), 'subscrpt_general', true);
        if (is_array($post_meta) && isset($post_meta['limit']) && $post_meta['limit'] == "unlimited") return $post_meta['cart_txt'];
        $expired = Helper::CheckExpired($product->get_id());
        if ($expired) :
            $text = __("renew", "sdevs_wea");
        elseif (is_array($post_meta) && $post_meta['enable']) :
            $text = $post_meta['cart_txt'];
        endif;
        return $text;
    }

    public function change_price_html($price, $product)
    {
        if ($product->is_type('variable')) return $price;
        $post_meta = get_post_meta($product->get_id(), 'subscrpt_general', true);
        if (is_array($post_meta) && $post_meta['enable']) :
            $time = $post_meta['time'] == 1 ? null : $post_meta['time'];
            $type = Helper::get_typos($post_meta['time'], $post_meta["type"]);
            $has_trial = Helper::Check_Trial($product->get_id());
            $trial = null;
            $signup_fee_html = null;
            if (!empty($post_meta['trial_time']) && $post_meta['trial_time'] > 0 && $has_trial) {
                $trial = "<br/> + Get " . $post_meta['trial_time'] . " " . Helper::get_typos($post_meta['trial_time'], $post_meta['trial_type']) . " free trial!";
                if (isset($post_meta['signup_fee'])) $signup_fee_html = "<br/> + Signup fee of " . wc_price($post_meta['signup_fee']);
            }
            $price_html = $price . " / " . $time . " " . $type . $signup_fee_html . $trial;
            return $price_html;
        else :
            return $price;
        endif;
    }

    public function change_price_cart_html($price, $cart_item, $cart_item_key)
    {
        $product = wc_get_product($cart_item['product_id']);
        if ($product->is_type('variable')) return $price;
        $post_meta = get_post_meta($cart_item['product_id'], 'subscrpt_general', true);
        if (is_array($post_meta) && $post_meta['enable']) :
            $time = $post_meta['time'] == 1 ? null : $post_meta['time'];
            $price_type = apply_filters("subscrpt_single_item_cart_price_type", $post_meta["type"], $cart_item);
            $type = Helper::get_typos($post_meta['time'], $price_type);
            $trial = null;
            $signup_fee_html = null;
            $has_trial = Helper::Check_Trial($cart_item['product_id']);
            if (!empty($post_meta['trial_time']) && $post_meta['trial_time'] > 0 && $has_trial) {
                $trial = "<br/><small> + " . $post_meta['trial_time'] . " " . Helper::get_typos($post_meta['trial_time'], $post_meta['trial_type']) . " free trial!</small>";
                if (isset($post_meta['signup_fee'])) $signup_fee_html = "<br/><small> + Signup fee of " . wc_price($post_meta['signup_fee']) . "</small>";
            }
            $price_html = $price . " / " . $time . " " . $type . $signup_fee_html . $trial;
            return apply_filters("subscrpt_single_item_cart_price_html", $price_html, $cart_item);
        else :
            return $price;
        endif;
    }

    public function add_rows_order_total()
    {
        $cart_items = WC()->cart->cart_contents;
        $recurrs = [];
        foreach ($cart_items as $cart_item) {
            $post_meta = get_post_meta($cart_item['product_id'], 'subscrpt_general', true);
            $product = wc_get_product($cart_item['product_id']);
            if (!$product->is_type('variable') && is_array($post_meta) && $post_meta['enable']) :
                $time = $post_meta['time'] == 1 ? null : $post_meta['time'];
                $price_type = apply_filters("subscrpt_single_item_cart_price_type", $post_meta["type"], $cart_item);
                $type = Helper::get_typos($post_meta['time'], $price_type);
                $price_html = get_woocommerce_currency_symbol() . $cart_item['line_subtotal'] . " / " . $time . " " . $type;
                $trial = null;
                $start_date = null;
                $has_trial = Helper::Check_Trial($cart_item['product_id']);
                if (!empty($post_meta['trial_time']) && $post_meta['trial_time'] > 0 && $has_trial) {
                    $trial = $post_meta['trial_time'] . " " . Helper::get_typos($post_meta['trial_time'], $post_meta['trial_type']);
                }
                $trial_status = $trial == null ? false : true;
                $next_date = Helper::next_date(
                    $post_meta['time'] . " " . $type,
                    $trial
                );
                $next_date = apply_filters("subscrpt_next_date_single_cart", $next_date,  $cart_item, $trial);
                array_push($recurrs, [
                    "trial" => $trial_status,
                    "price_html" => $price_html,
                    "start_date" => $start_date,
                    "next_date" => $next_date
                ]);
            endif;
        }
        $recurrs = apply_filters('subscrpt_cart_recurring_items', $recurrs);
        if (count($recurrs) == 0) return;
?>
        <tr class="recurring-total">
            <th><?php esc_html_e('Recurring totals', 'sdevs_wea'); ?></th>
            <td data-title="<?php esc_attr_e('Recurring totals', 'sdevs_wea'); ?>">
                <?php foreach ($recurrs as $recurr) : ?>
                    <?php if ($recurr['trial']) : ?>
                        <p>
                            <span><?php echo $recurr['price_html']; ?></span><br />
                            <small><?php _e('First billing on', 'sdevs_wea'); ?>: <?php echo date('F d, Y', $recurr['start_date']); ?></small>
                        </p>
                    <?php else : ?>
                        <p>
                            <span><?php echo $recurr['price_html']; ?></span><br />
                            <small><?php _e('Next billing on', 'sdevs_wea'); ?>: <?php echo $recurr['next_date']; ?></small>
                        </p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </td>
        </tr>
<?php
    }
}
