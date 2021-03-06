<?php

/**
 * External product add to cart
 *
 * This template can be overridden by copying it to yourtheme/simple-booking/myaccount/bookings.php
 *
 */

$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$args = [
    'author' => get_current_user_id(),
    'posts_per_page' => 10,
    'paged' => $paged,
    'post_type' => 'subscrpt_order',
    'post_status' => ["pending", "active", "on_hold", "cancelled", "expired", "pe_cancelled"]
];

$postslist = new WP_Query($args);
?>

<table class="shop_table my_account_subscrpt">
    <thead>
        <tr>
            <th scope="col" class="subscrpt-id"><?php esc_html_e('Subscription', 'sdevs_wea'); ?></th>
            <th scope="col" class="order-status"><?php esc_html_e('Status', 'sdevs_wea'); ?></th>
            <th scope="col" class="order-product"><?php esc_html_e('Product', 'sdevs_wea'); ?></th>
            <th scope="col" class="subscrpt-next-date"><?php esc_html_e('Next Payment', 'sdevs_wea'); ?></th>
            <th scope="col" class="subscrpt-total"><?php esc_html_e('Total', 'sdevs_wea'); ?></th>
            <th scope="col" class="subscrpt-action"></th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($postslist->have_posts()) :
            while ($postslist->have_posts()) : $postslist->the_post();
                $post_meta = get_post_meta(get_the_ID(), "_subscrpt_order_general", true);
                $product = wc_get_product($post_meta["product_id"]);
                if (isset($post_meta['variation_id'])) {
                    $product_meta = get_post_meta($post_meta['variation_id'], 'subscrpt_general', true);
                } else {
                    $product_meta = $product->get_meta('subscrpt_general', true);
                }
                $product_name = get_the_title($post_meta['product_id']);
                $product_name = apply_filters('subscrpt_filter_product_name', $product_name, $post_meta);
                $product_link = get_the_permalink($post_meta['product_id']);
                $product_link = apply_filters('subscrpt_filter_product_permalink', $product_link, $post_meta);
                $time = $product_meta['time'] == 1 ? null : $product_meta['time'];
                $type = subscrpt_get_typos($product_meta['time'], $product_meta["type"]);
                $product_price_html = wc_price($product->get_price() * $post_meta['qty']) . " / " . $time . " " . $type;
                $product_price_html = apply_filters("subscrpt_price_recurring", $product_price_html, $product, wc_get_order($post_meta['order_id']), $post_meta['qty']);
        ?>
                <tr>
                    <td><?php the_ID(); ?></td>
                    <td><?php echo get_post_status(); ?></td>
                    <td><a href="<?php echo $product_link; ?>" target="_blank"><?php echo $product_name; ?></a></td>
                    <?php if ($post_meta['trial'] == null) : ?>
                        <td><?php echo date('F d, Y', $post_meta['next_date']); ?></td>
                    <?php else : ?>
                        <td><small>First Billing : </small><?php echo date('F d, Y', $post_meta['start_date']); ?></td>
                    <?php endif; ?>
                    <td><?php echo $product_price_html; ?></td>
                    <td>
                        <a href="<?php echo get_permalink(wc_get_page_id('myaccount')) . "view-subscrpt/" . get_the_ID(); ?>" class="woocommerce-button button view">View</a>
                    </td>
                </tr>
        <?php
            endwhile;
            next_posts_link('Older Entries', $postslist->max_num_pages);
            previous_posts_link('Next Entries &raquo;');
            wp_reset_postdata();
        endif;
        ?>
    </tbody>
</table>