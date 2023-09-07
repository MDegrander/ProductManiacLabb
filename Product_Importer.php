<?php
/**
 * Plugin Name: Product Maniac V2
 * Author: Erik Radl & Mathias Degrander
 */

// Include the required library
include('simple_html_dom.php');

//Include GPT3 integration
require_once WP_PLUGIN_DIR . '/ProductManiacV2/Gpt3Integration.php';


$gpt3 = new Gpt3Integration();

// Använd $gpt3->analyzeText() och $gpt3->rewriteText() där det behövs


// Add admin menu for the plugin
add_action('admin_menu', 'product_url_fetch_menu');

function product_url_fetch_menu() {
    add_menu_page('Product URL Fetch', 'Product URL Fetch', 'manage_options', 'product_url_fetch', 'product_url_fetch_page');
    add_submenu_page('product_url_fetch', 'OpenAI Settings', 'OpenAI Settings', 'manage_options', 'openai_settings', 'openai_settings_page');
}


// Utility function to find the first matching element
function find_first_matching($html, $selectors) {
    foreach ($selectors as $selector) {
        $element = $html->find($selector, 0);
        if ($element && !empty(trim($element->plaintext))) {
            return trim($element->plaintext);
        }
    }
    return null;
}

// Utility function to find the first image
function find_first_image($html, $selectors) {
    foreach ($selectors as $selector) {
        $element = $html->find($selector, 0);
        if ($element && !empty(trim($element->src))) {
            return trim($element->src);
        }
    }
    return null;
}

// Utility function to find product variations
function find_variations($html, $selectors) {
    $variations = array();
    foreach ($selectors as $selector) {
        $elements = $html->find($selector);
        foreach ($elements as $element) {
            if ($element && !empty(trim($element->plaintext))) {
                $variations[] = trim($element->plaintext);
            }
        }
    }
    return $variations;
}

// Utility function to find product attributes
function find_attributes($html, $selectors) {
    $attributes = array();
    foreach ($selectors as $selector) {
        $elements = $html->find($selector);
        foreach ($elements as $element) {
            if ($element && !empty(trim($element->plaintext))) {
                $attributes[] = trim($element->plaintext);
            }
        }
    }
    return $attributes;
}

//Add openAI settings to menu
function openai_settings_page() {
    echo '<div class="wrap">';
    echo '<h1>OpenAI Settings</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields('openai_settings');
    do_settings_sections('openai_settings');
    submit_button();
    echo '</form>';
    echo '</div>';
        echo '<button id="test-connection">Testa anslutning</button>';
    echo '<div id="test-result"></div>';
}

//Register OpenAI settings
add_action('admin_init', 'register_openai_settings');

function register_openai_settings() {
    register_setting('openai_settings', 'openai_api_key');
    add_settings_section('openai_settings_section', 'API Settings', null, 'openai_settings');
    add_settings_field('openai_api_key', 'API Key', 'openai_api_key_callback', 'openai_settings', 'openai_settings_section');
}

function openai_api_key_callback() {
    $api_key = get_option('openai_api_key', '');
    echo '<input type="text" name="openai_api_key" value="' . esc_attr($api_key) . '" />';
}


// Function to log actions
function log_action($message) {
    $current_logs = get_option('product_url_fetch_logs', array());
    $current_logs[] = array('time' => current_time('mysql'), 'message' => $message);
    update_option('product_url_fetch_logs', $current_logs);
}

// Function to add product to WooCommerce
function add_product_to_woocommerce($title, $description, $short_description, $price, $image_urls, $variations, $attributes) {
    $new_product = array(
        'post_title'    => $title,
        'post_content'  => $description,
        'post_status'   => 'publish',
        'post_type'     => 'product',
        'post_excerpt'  => $short_description,
    );


// Kontrollera valda attribut och variationer
if (isset($_POST['selected_attributes'])) {
    $selected_attributes = $_POST['selected_attributes'];
    // Använd $selected_attributes för att skapa produktvariationer i WooCommerce
}


    $product_id = wp_insert_post($new_product);

    update_post_meta($product_id, '_price', $price);
    update_post_meta($product_id, '_regular_price', $price);

// Definiera attribut och dess värden
$attributes = array(
    'pa_color' => array(
        'name' => 'pa_color',
        'value' => '',
        'position' => 1,
        'is_visible' => 1,
        'is_variation' => 1,
        'is_taxonomy' => 1
    ),
    // Lägg till fler attribut här om du behöver
);

// Sätt attribut till produkten
update_post_meta($product_id, '_product_attributes', $attributes);

// Lägg till termerna (färgerna i detta fall)
// Ersätt 'Red' och 'Green' med de faktiska färgerna eller attributen du har
wp_set_object_terms($product_id, array('Red', 'Green'), 'pa_color');


// Handle multiple images
$image_ids = array();
foreach ($image_urls as $image_url) {
    // Lägg till "http:" i början om det saknas
    if (strpos($image_url, '//') === 0) {
        $image_url = 'http:' . $image_url;
    }

    // Ta bort alla extra parametrar efter ".jpg"
    $image_url = preg_replace('/(.jpg).*$/i', '$1', $image_url);

    // Download the image
    $tmp = download_url($image_url, 30); // 30 seconds timeout

        // Remove URL parameters from the filename
        $filename = preg_replace('/\?.*/', '', basename($image_url));

        $file_array = array(
            'name' => $filename,
            'tmp_name' => $tmp,
            'type' => 'image/jpeg',  // Explicitly define the MIME type
        );

        // Handle the sideload
        $id = media_handle_sideload($file_array, $product_id);
        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            log_action('Error sideloading image: ' . $id->get_error_message());
            continue;
        }

        $image_ids[] = $id;
    }

    // Set the main product image
    if (!empty($image_ids)) {
        set_post_thumbnail($product_id, $image_ids[0]);
    }

    // Set product gallery if we have more than one image
    if (count($image_ids) > 1) {
        update_post_meta($product_id, '_product_image_gallery', implode(',', array_slice($image_ids, 1)));
    }

    // Handle variations
    if (!empty($variations)) {
        // Set the product type to variable
        wp_set_object_terms($product_id, 'variable', 'product_type');

        foreach ($variations as $variation_name) {
            // Create a new product variation
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_attributes(array('attribute_pa_variation' => sanitize_title($variation_name)));
            $variation->set_status("publish");
            $variation_id = $variation->save();

            // Set variation meta
            update_post_meta($variation_id, 'attribute_pa_variation', sanitize_title($variation_name));
        }

    }
}

// Main function to display the admin page
function product_url_fetch_page() {
    echo '<div class="wrap" style="width: 800px;">';
    echo '<h1 style="background-color: #4cadd3; color: white; padding: 10px;">Product Maniac V2</h1>';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (isset($_POST['add_to_woocommerce'])) {
            $title = sanitize_text_field($_POST['title']);
            $description = sanitize_textarea_field($_POST['description']);
            $short_description = sanitize_textarea_field($_POST['short_description']);
            $price = sanitize_text_field($_POST['price']);
            $image_urls = array_map('esc_url_raw', explode("\n", trim($_POST['image_urls'])));
            $variations = array_map('sanitize_text_field', explode("\n", trim($_POST['variations'])));
            $attributes = array_map('sanitize_text_field', explode("\n", trim($_POST['attributes'])));
            $selected_attributes = array_map('sanitize_text_field', $_POST['selected_attributes'] ?? []);


            add_product_to_woocommerce($title, $description, $short_description, $price, $image_urls, $variations, $attributes, $selected_attributes);


        } elseif (isset($_POST['product_url']) && filter_var($_POST['product_url'], FILTER_VALIDATE_URL)) {
            $product_url = $_POST['product_url'];

            // Use wp_remote_get() to fetch the content
            $response = wp_remote_get($product_url);
            if (is_wp_error($response)) {
                $error_message = 'Failed to fetch the product URL. Error: ' . $response->get_error_message();
                log_action($error_message);
                echo '<p>' . $error_message . '</p>';
                return;
            }

            $body = wp_remote_retrieve_body($response);
            $html = str_get_html($body);

            if (!$html) {
                $error_message = 'The URL does not return a valid page.';
                log_action($error_message);
                echo '<p>' . $error_message . '</p>';
                return;
            }

            $title_selectors = ['title', '.product__title', '.product-title'];
            $description_selectors = ['.product__description', '.description', '.product-description'];
            $short_description_selectors = ['.product__short-description', '.short-description', '.product-short-description'];
            $price_selectors = ['.price__regular', '.price', '.product-price'];
            $image_selectors = ['.product__media img', '.product-images img', '.product-gallery img'];
            $variation_selectors = ['.product__variation', '.variation', '.product-variation'];
            $attribute_selectors = ['.product__attribute', '.attribute', '.product-attribute'];

            $title = find_first_matching($html, $title_selectors);
            $description = find_first_matching($html, $description_selectors);
            $short_description = find_first_matching($html, $short_description_selectors);
            $price = find_first_matching($html, $price_selectors);
            $image_urls = $html->find(implode(', ', $image_selectors));
            $variations = find_variations($html, $variation_selectors);
            $attribute_selectors = ['input[type=radio][name=Color]'];
            $attributes = find_attributes($html, $attribute_selectors);

            echo '<form method="post" action="">';
            echo '<div class="section"><h2>Product Information</h2>';
            
            // Show only the first image in a 400x400px box
            $firstImage = $image_urls[0]->src;
            echo '<div style="position: relative; width: 400px; height: 400px;">';
            echo '<img src="' . $firstImage . '" alt="Product Image" style="max-width:400px; max-height:400px;">';
            echo '<div style="position: absolute; bottom: 0; right: 0; background-color: blue; color: white; padding: 5px;">Pictures: ' . count($image_urls) . '</div>';
            echo '</div>';
            
            // Add checkboxes for selecting images
            echo '<p><strong>Select pictures:</strong></p>';
            foreach ($image_urls as $img) {
                echo '<input type="checkbox" name="selected_images[]" value="' . $img->src . '" checked> ';
                echo '<img src="' . $img->src . '" alt="Product Image" style="max-width:50px;">';
            }
            
            echo '<p><strong>Title:</strong> <input type="text" name="title" value="' . $title . '" style="width:100%;"></p>';
            echo '<p><strong>Description:</strong> <textarea name="description" style="width:100%; height:100px;">' . $description . '</textarea></p>';
            echo '<p><strong>Short Description:</strong> <textarea name="short_description" style="width:100%; height:50px;">' . $short_description . '</textarea></p>';
            echo '<p><strong>Price:</strong> <input type="text" name="price" value="' . $price . '" style="width:100%;"></p>';
            echo '<p><strong>Image URLs (one per line):</strong> <textarea name="image_urls" style="width:100%; height:100px;">';
            foreach ($image_urls as $img) {
                echo $img->src . "\n";
            }
            echo '</textarea></p>';
            echo '<p><strong>Variations (one per line):</strong> <textarea name="variations" style="width:100%; height:100px;">' . implode("\n", $variations) . '</textarea></p>';
           // Attributes
echo '<p><strong>Available Attributes:</strong></p>';
foreach ($attributes as $attribute) {
    echo '<input type="checkbox" name="selected_attributes[]" value="' . $attribute . '" checked> ' . $attribute . '<br>';
}
            echo '<p><strong>Attributes (one per line):</strong> <textarea name="attributes" style="width:100%; height:100px;">' . implode("\n", $attributes) . '</textarea></p>';
            echo '<input type="submit" name="add_to_woocommerce" value="Add to WooCommerce" class="button button-primary">';
            echo '</div>';
            echo '</form>';            
        }
    }

    echo '<form method="post" action="">';
    echo '<div style="display: flex; flex-direction: column; align-items: center;">';
    echo '<input type="text" name="product_url" placeholder="Enter product URL" style="width:100%; margin-bottom: 10px;" required>';
    echo '<input type="submit" value="Fetch" style="background-color: #4cadd3; color: white; cursor: pointer;">';
    echo '</div>';    
    echo '</form>';

    // Display logs
    $logs = get_option('product_url_fetch_logs', array());
    if (!empty($logs)) {
        echo '<div class="section"><h2 style="background-color: #4cadd3; color: white; padding: 10px;">Log</h2>';
        echo '<div style="height: 800px; overflow-y: scroll; background-color: white;">';  // Scrollable div
        echo '<ul>';
        foreach ($logs as $log) {
            echo '<li><strong>' . esc_html($log['time']) . ':</strong> ' . esc_html($log['message']) . '</li>';
        }
        echo '</ul>';
        echo '</div>';  // End of scrollable div
        echo '</div>';
    }
    
    echo '</div>';  // End of container
}
