<?php
if (!isset($id)) {
    return;
}

if (!get_the_title($id)) {
    return;
}

do_action("before_single_subscrpt_content");
$post_meta = get_post_meta($id, "_subscrpt_order_general", true);
$order     = wc_get_order($post_meta['order_id']);
$product   = wc_get_product($post_meta['product_id']);
$status    = get_post_status($id);
?>
<style>
    .auto-renew-on,
    .subscription_renewal_early,
    .auto-renew-off {
        margin-bottom: 10px;
    }
</style>
<table class="shop_table subscription_details">
    <tbody>
        <tr>
            <td><?php _e('Order', 'sdevs_wea'); ?></td>
            <td><a href="<?php echo get_permalink(wc_get_page_id('myaccount')) . "view-order/" . $post_meta['order_id']; ?>" target="_blank"># <?php echo $post_meta['order_id']; ?></a></td>
        </tr>
        <tr>
            <td><?php _e('Status', 'sdevs_wea'); ?></td>
            <td><?php echo $status; ?></td>
        </tr>
        <tr>
            <td><?php _e('Start date', 'sdevs_wea'); ?></td>
            <td><?php echo date('F d, Y', $post_meta['start_date']); ?></td>
        </tr>
        <?php if ($post_meta['trial'] == null) : ?>
            <tr>
                <td><?php _e('Next payment date', 'sdevs_wea'); ?></td>
                <td><?php echo date('F d, Y', $post_meta['next_date']); ?></td>
            </tr>
        <?php else : ?>
            <tr>
                <td><?php _e('Trial', 'sdevs_wea'); ?></td>
                <td><?php echo $post_meta['trial']; ?></td>
            </tr>
            <tr>
                <td><?php _e('Trial End & First Billing', 'sdevs_wea'); ?></td>
                <td><?php echo date('F d, Y', $post_meta['start_date']); ?></td>
            </tr>
        <?php endif; ?>
        <tr>
            <td><?php _e('Payment', 'sdevs_wea'); ?></td>
            <td>
                <span data-is_manual="yes" class="subscription-payment-method"><?php echo $order->get_payment_method_title(); ?></span>
            </td>
        </tr>
        <?php
        $subscrpt_nonce = wp_create_nonce('subscrpt_nonce');
        if (isset($post_meta['variation_id'])) {
            $product_meta = get_post_meta($post_meta['variation_id'], 'subscrpt_general', true);
        } else {
            $product_meta = $product->get_meta('subscrpt_general', true);
        }
        ?>
        <?php if ($status != "cancelled") : ?>
            <tr>
                <td><?php _e('Actions', 'sdevs_wea'); ?></td>
                <td>
                    <?php if (($status == "pending" || $status == "active" || $status == "on_hold") && $product_meta['user_cancell'] == 'yes') : ?>
                        <a href="<?php echo get_permalink(wc_get_page_id('myaccount')) . "view-subscrpt/" . $id . "?subscrpt_id=" . $id . "&action=cancelled&wpnonce=" . $subscrpt_nonce; ?>" class="button cancel">Cancel</a>
                    <?php elseif (trim($status) == trim("pe_cancelled")) : ?>
                        <a href="" class="button subscription_renewal_early"><?php _e("Reactive", "sdevs_wea"); ?></a>
                    <?php endif; ?>
                    <?php if ($order->get_status() === 'pending') : ?>
                        <a href="<?php echo $order->get_checkout_payment_url(); ?>" class="button subscription_renewal_early"><?php _e('Pay now', 'sdevs_wea'); ?></a>
                    <?php elseif ((get_option('subscrpt_early_renew', '') == 1 || trim($status) == trim("expired")) && $order->get_status() == 'completed') : ?>
                        <a href="<?php echo get_permalink(wc_get_page_id('myaccount')) . "view-subscrpt/" . $id . "?subscrpt_id=" . $id . "&action=early-renew&wpnonce=" . $subscrpt_nonce; ?>" class="button subscription_renewal_early"><?php _e('Renew now', 'sdevs_wea'); ?></a>
                    <?php endif; ?>
                    <?php do_action('subscrpt_single_action_buttons', $id, $order, $subscrpt_nonce); ?>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<h2><?php _e('Subscription totals', 'sdevs_wea'); ?></h2>
<table class="shop_table order_details">
    <thead>
        <tr>
            <th class="product-name"><?php _e('Product', 'sdevs_wea'); ?></th>
            <th class="product-total"><?php _e('Total', 'sdevs_wea'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $product_name       = apply_filters('subscrpt_filter_product_name', $product->get_name(), $post_meta);
        $product_link       = apply_filters('subscrpt_filter_product_permalink', $product->get_permalink(), $post_meta);
        $time               = $product_meta['time'] == 1 ? null : $product_meta['time'];
        $type               = subscrpt_get_typos($product_meta['time'], $product_meta["type"]);
        $product_price_html = wc_price($product->get_price() * $post_meta['qty']) . " / " . $time . " " . $type;
        ?>
        <tr class="order_item">
            <td class="product-name">
                <a href="<?php echo $product_link; ?>"><?php echo $product_name; ?></a>
                <strong class="product-quantity">× <?php echo $post_meta['qty']; ?></strong>
            </td>
            <td class="product-total">
                <span class="woocommerce-Price-amount amount"><?php echo wc_price($product->get_price()) . " / " . $time . " " . $type; ?></span>
            </td>
        </tr>
    </tbody>
    <tfoot>
        <tr>
            <th scope="row"><?php _e('Subtotal', 'sdevs_wea'); ?>:</th>
            <td>
                <span class="woocommerce-Price-amount amount"><?php echo wc_price($product->get_price() * $post_meta['qty']); ?></span>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Renew', 'sdevs_wea'); ?>:</th>
            <td>
                <span class="woocommerce-Price-amount amount">
                    <?php echo $product_price_html; ?>
                </span>
            </td>
        </tr>
    </tfoot>
</table>

<section class="woocommerce-customer-details">
    <h2 class="woocommerce-column__title"><?php _e('Billing address', 'sdevs_wea'); ?></h2>
    <address>
        <?php echo $order->get_formatted_billing_address(); ?>
        <p class="woocommerce-customer-details--phone"><?php echo $order->get_billing_phone(); ?></p>
        <p class="woocommerce-customer-details--email"><?php echo $order->get_billing_email(); ?></p>
    </address>
</section>
<div class="clear"></div>