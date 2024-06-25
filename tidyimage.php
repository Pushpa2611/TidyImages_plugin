<?php
/*
Plugin Name: Tidy Images
Description: A plugin to clean up unused images from the media library.
Version: 1.0
Author: Pushpasharmila S
*/

function image_gallery_scripts() {
    wp_enqueue_style('image-gallery-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('image-gallery-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
}
add_action('admin_enqueue_scripts', 'image_gallery_scripts');




//Add Admin Menu

function add_image_gallery_menu() {
    add_menu_page(
        'Tidy Images',
        'Tidy Images',
        'manage_options',
        'tidy-images',
        'display_image_gallery_page',
        'dashicons-images-alt2',
        20
    );
}
add_action('admin_menu', 'add_image_gallery_menu');

//Display Image Gallery Page
function display_image_gallery_page() {
    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
    ));

    if ($attachments) {
        $used_images = array();
        $unused_images = array();

        foreach ($attachments as $attachment) {
            if (is_image_used($attachment->ID)) {
                $used_images[] = $attachment;
            } else {
                $unused_images[] = $attachment;
            }
        }
        ?>

        <div>
            <h1>Tidy Images</h1>
            <p>You can cleanup your site from remove the unused images</p>
        </div>
        <div class="image-tabs">
            <button class="tablink active" onclick="openTab(event, 'used')">Used Images</button>
            <button class="tablink" onclick="openTab(event, 'unused')">Unused Images</button>
            <form id="bulk-delete-form" method="post" action="">
                <input type="submit" name="bulk_delete" value="Delete Selected" onclick="return confirmDelete();" class="button button-primary bulk-delete" style="display:none;">
        </div>

        <div id="used" class="tabcontent">
            <?php display_images_table($used_images, true); ?>
        </div>
        <div id="unused" class="tabcontent">
            <?php display_images_table($unused_images, false); ?>
        </div>

            </form>
        <?php
    } else {
        echo 'No images found.';
    }
}





function display_images_table($images, $is_used) {
    if (!empty($images)) {
        ?>
        <div class="image-table">
            <table class="full-width border-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="select-all" /></th>
                        <th>Image</th>
                        <th>View Usage</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($images as $attachment) {
                        $image_url = wp_get_attachment_url($attachment->ID);
                        $image_alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
                        $usage_details = get_image_usage($attachment->ID);
                        $image_name = get_the_title($attachment->ID);
                        ?>
                        <tr>
                            <td><input type="checkbox" name="attachments[]" value="<?php echo esc_attr($attachment->ID); ?>" class="image-checkbox" /></td>
                            <td>
                                <div class="image-info">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" class="small-un-image" />
                                    <p class="image-name"><?php echo esc_html($image_name); ?></p>
                                </div>
                            </td>
                            <td>
                                <div class="usage-details">
                                    <?php if (!empty($usage_details)) {
                                        foreach ($usage_details as $usage) { ?>
                                            <p><strong><?php echo esc_html($usage['type']); ?>:</strong> <a href="<?php echo esc_url($usage['link']); ?>"><?php echo esc_html($usage['title']); ?></a></p>
                                        <?php } 
                                    } else {
                                        echo 'No usage found.';
                                    } ?>
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=image-gallery&action=custom_delete&id=' . $attachment->ID)); ?>" onclick="return confirmDelete();">Delete Permanently</a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
    } else {
        echo 'No ' . ($is_used ? 'used' : 'unused') . ' images found.';
    }
}




//Custom Deletion
function handle_custom_delete_action() {
    if (isset($_GET['page']) && $_GET['page'] === 'image-gallery' && isset($_GET['action']) && $_GET['action'] === 'custom_delete' && isset($_GET['id'])) {
        $attachment_id = (int) $_GET['id'];

        if (!current_user_can('delete_post', $attachment_id)) {
            return;
        }

        $attachment = get_post($attachment_id);

        if ($attachment && 'attachment' === $attachment->post_type && strpos($attachment->post_mime_type, 'image') !== false) {
            $deleted = wp_delete_attachment($attachment_id, true);

            if ($deleted) {
                wp_redirect(admin_url('admin.php?page=image-gallery'));
                exit;
            } else {
                echo 'Failed to delete image.';
            }
        } else {
            echo 'This is not an image or doesn\'t exist.';
        }
    }
}
add_action('admin_init', 'handle_custom_delete_action');


// check image usage
function is_image_used($attachment_id) {
    $attachment_url = wp_get_attachment_url($attachment_id);
    $post_types = get_post_types(array('public' => true), 'names');
    $all_post_types = array_merge(array('post', 'page', 'product', 'custom_post_type'), $post_types);

    $post_args = array(
        'post_type' => $all_post_types,
        'posts_per_page' => -1,
    );

    $posts_query = new WP_Query($post_args);

    if ($posts_query->have_posts()) {
        while ($posts_query->have_posts()) {
            $posts_query->the_post();
            $content = get_the_content();

            if (strpos($content, $attachment_url) !== false) {
                wp_reset_postdata();
                return true;
            }

            if (get_post_thumbnail_id(get_the_ID()) === $attachment_id) {
                wp_reset_postdata();
                return true;
            }

            $custom_fields = get_post_meta(get_the_ID());
            if (!empty($custom_fields)) {
                foreach ($custom_fields as $field_values) {
                    if (is_array($field_values)) {
                        foreach ($field_values as $field_value) {
                            if ($field_value == $attachment_id) {
                                wp_reset_postdata();
                                return true;
                            }
                        }
                    } elseif ($field_values == $attachment_id) {
                        wp_reset_postdata();
                        return true;
                    }
                }
            }

            // Check WooCommerce product gallery images
            if (get_post_type() === 'product' && function_exists('wc_get_product')) {
                $product = wc_get_product(get_the_ID());
                if ($product) {
                    // Check main product image
                    if (get_post_thumbnail_id($product->get_id()) === $attachment_id) {
                        wp_reset_postdata();
                        return true;
                    }

                    // Check product gallery images
                    $gallery_image_ids = $product->get_gallery_image_ids();
                    if (in_array($attachment_id, $gallery_image_ids)) {
                        wp_reset_postdata();
                        return true;
                    }

                    // Check variable product variations
                    if ($product->is_type('variable')) {
                        $variations = $product->get_children();
                        foreach ($variations as $variation_id) {
                            if (get_post_thumbnail_id($variation_id) === $attachment_id) {
                                wp_reset_postdata();
                                return true;
                            }
                        }
                    }
                }
            }
        }
        wp_reset_postdata();
    }
    return false;
}

//Get Image Usage

function get_image_usage($attachment_id) {
    $usage_posts = array();
    $attachment_url = wp_get_attachment_url($attachment_id);
    $post_types = get_post_types(array('public' => true), 'names');
    $all_post_types = array_merge(array('post', 'page'), $post_types);
    // jihuin


    // Query posts and pages
    $post_args = array(
        'post_type' => $all_post_types,
        'posts_per_page' => -1,
    );

    $posts_query = new WP_Query($post_args);
    if ($posts_query->have_posts()) {
        while ($posts_query->have_posts()) {
            $posts_query->the_post();
            $content = get_the_content();

            // Check if the attachment URL is within the content
            if (strpos($content, $attachment_url) !== false || get_post_thumbnail_id(get_the_ID()) === $attachment_id) {
                $usage_posts[] = array(
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                    'type' => get_post_type(),
                );
            }
            // Check custom fields
            $custom_fields = get_post_meta(get_the_ID());
            if (!empty($custom_fields)) {
                foreach ($custom_fields as $field_key => $field_values) {
                    if (is_array($field_values)) {
                        foreach ($field_values as $field_value) {
                            if ($field_value == $attachment_id) {
                                $usage_posts[] = array(
                                    'title' => get_the_title(),
                                    'link' => get_permalink(),
                                    'type' => get_post_type(),
                                );
                            }
                        }
                    } elseif ($field_values == $attachment_id) {
                        $usage_posts[] = array(
                            'title' => get_the_title(),
                            'link' => get_permalink(),
                            'type' => get_post_type(),
                        );
                    }
                }
            }
            
        }
        wp_reset_postdata();
    }

    // Check for product usage if WooCommerce is active
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        $usage_posts = array_merge($usage_posts, get_woocommerce_image_usage($attachment_id));
    }

    // Check custom post types
    $custom_post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
    foreach ($custom_post_types as $post_type) {
        $usage_posts = array_merge($usage_posts, get_custom_post_type_image_usage($attachment_id, $post_type));
    }

    return $usage_posts;
}

function get_woocommerce_image_usage($attachment_id) {
    $usage_posts = array();
    

    // Query products and product variations
    $product_args = array(
        'post_type' => array('product', 'product_variation'),
        'posts_per_page' => -1,
    );

    $products_query = new WP_Query($product_args);
    if ($products_query->have_posts()) {
        while ($products_query->have_posts()) {
            $products_query->the_post();
            $product = wc_get_product(get_the_ID());

            // Check if the attachment ID is in the product gallery
            if (in_array($attachment_id, $product->get_gallery_image_ids())) {
                $usage_posts[] = array(
                    'title' => get_the_title(),
                    'link' => get_permalink(),
                    'type' => 'product',
                );
            }

            // Check if the variation uses the image
            if ($product->is_type('variable')) {
                foreach ($product->get_children() as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation && get_post_thumbnail_id($variation_id) === $attachment_id) {
                        $usage_posts[] = array(
                            'title' => $variation->get_name(),
                            'link' => get_permalink($variation_id),
                            'type' => 'product_variation',
                        );
                    }
                }
            }
        }
        wp_reset_postdata();
    }

    return $usage_posts;
}

function get_custom_post_type_image_usage($attachment_id, $post_type) {
    $usage_posts = array();

    $custom_post_args = array(
        'post_type' => $post_type,
        'posts_per_page' => -1,
    );

    $custom_posts_query = new WP_Query($custom_post_args);
    if ($custom_posts_query->have_posts()) {
        while ($custom_posts_query->have_posts()) {
            $custom_posts_query->the_post();

            // if (function_exists('get_fields')) {
            //     $custom_fields = get_fields();
            //     if (is_array($custom_fields)) {
            //         foreach ($custom_fields as $field_key => $field_value) {
            //             if (is_array($field_value) && in_array($attachment_id, $field_value)) {
            //                 $usage_posts[] = array(
            //                     'title' => get_the_title(),
            //                     'link' => get_permalink(),
            //                     'type' => $post_type,
            //                 );
            //             }
            //         }
            //     }
            // }
        }
        wp_reset_postdata();
    }

    return $usage_posts;
}


//Confirm Delete
function confirmDelete() {
    return confirm('Are you sure you want to delete this image?');
}


function handle_bulk_delete_action() {
    if (isset($_POST['bulk_delete']) && isset($_POST['attachments']) && is_array($_POST['attachments'])) {
        foreach ($_POST['attachments'] as $attachment_id) {
            $attachment_id = (int) $attachment_id;
            if (current_user_can('delete_post', $attachment_id)) {
                wp_delete_attachment($attachment_id, true);
            }
        }
        wp_redirect(admin_url('admin.php?page=tidy-images'));
        exit;
    }
}
add_action('admin_init', 'handle_bulk_delete_action');




