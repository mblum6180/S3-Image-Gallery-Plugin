<?php
/**
 * Plugin Name: S3 Image Gallery
 * Description: Automatically fetch images from S3 buckets and display them in a lazy-loading gallery.
 * Version: 1.1
 * Author: Your Name
 */

// Include AWS SDK (You need to include this in your project, e.g., via Composer)
require 'vendor/autoload.php';
use Aws\S3\S3Client;

// Add Admin Menu
add_action('admin_menu', 's3_gallery_menu');
function s3_gallery_menu() {
    add_menu_page(
        'S3 Gallery Settings',
        'S3 Gallery',
        'manage_options',
        's3-gallery',
        's3_gallery_settings_page'
    );
}

// Settings Page
function s3_gallery_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['s3_buckets'])) {
        update_option('s3_buckets', sanitize_textarea_field($_POST['s3_buckets']));
        update_option('aws_access_key', sanitize_text_field($_POST['aws_access_key']));
        update_option('aws_secret_key', sanitize_text_field($_POST['aws_secret_key']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    $buckets = get_option('s3_buckets', '');
    $aws_access_key = get_option('aws_access_key', '');
    $aws_secret_key = get_option('aws_secret_key', '');

    ?>
    <div class="wrap">
        <h1>S3 Image Gallery</h1>
        <form method="post">
            <label for="s3_buckets">
                <p>Enter your S3 bucket URLs, one per line:</p>
            </label>
            <textarea id="s3_buckets" name="s3_buckets" rows="10" cols="50" class="large-text"><?php echo esc_textarea($buckets); ?></textarea>
            <label for="aws_access_key">
                <p>Enter your AWS Access Key:</p>
            </label>
            <input type="text" id="aws_access_key" name="aws_access_key" value="<?php echo esc_attr($aws_access_key); ?>" class="regular-text">
            <label for="aws_secret_key">
                <p>Enter your AWS Secret Key:</p>
            </label>
            <input type="password" id="aws_secret_key" name="aws_secret_key" value="<?php echo esc_attr($aws_secret_key); ?>" class="regular-text">
            <p><em>Use the shortcode [s3_gallery bucket="BUCKET_URL"] to display a gallery.</em></p>
            <input type="submit" class="button-primary" value="Save Changes">
        </form>
    </div>
    <?php
}

// Shortcode to Render Gallery
add_shortcode('s3_gallery', 's3_gallery_shortcode');
function s3_gallery_shortcode($atts) {
    $atts = shortcode_atts(array('bucket' => ''), $atts, 's3_gallery');
    $bucket_url = esc_url($atts['bucket']);

    if (!$bucket_url) {
        return '<p>Please provide a valid S3 bucket URL.</p>';
    }

    $aws_access_key = get_option('aws_access_key');
    $aws_secret_key = get_option('aws_secret_key');

    // Initialize S3 Client
    $s3 = new S3Client([
        'version' => 'latest',
        'region'  => 'us-east-1', // Adjust to your S3 bucket's region
        'credentials' => [
            'key'    => $aws_access_key,
            'secret' => $aws_secret_key,
        ],
    ]);

    // Extract Bucket Name and Path
    $parsed_url = parse_url($bucket_url);
    $bucket_name = str_replace('.s3.amazonaws.com', '', $parsed_url['host']);
    $path = trim($parsed_url['path'], '/');

    // Fetch Images from S3 Bucket
    try {
        $results = $s3->listObjects([
            'Bucket' => $bucket_name,
            'Prefix' => $path,
        ]);

        $images = [];
        foreach ($results['Contents'] as $object) {
            if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $object['Key'])) {
                $images[] = $bucket_url . '/' . $object['Key'];
            }
        }
    } catch (Exception $e) {
        return '<p>Error fetching images: ' . $e->getMessage() . '</p>';
    }

    // Render Gallery with Lazy Loading
    $output = '<div class="s3-gallery" style="display: flex; flex-wrap: wrap;">';
    foreach ($images as $image) {
        $output .= '<div style="margin: 5px;">';
        $output .= '<img src="' . $image . '" loading="lazy" style="max-width: 150px; height: auto;" />';
        $output .= '</div>';
    }
    $output .= '</div>';

    return $output;
}
?>

