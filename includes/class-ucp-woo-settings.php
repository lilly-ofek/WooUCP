<?php
namespace WooUCP;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Class for WooUCP
 */
class Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add settings page to the menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('UCP Settings', 'ucp-for-woocommerce'),
            __('UCP Settings', 'ucp-for-woocommerce'),
            'manage_options',
            'ucp-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ucp_settings_group', 'ucp_woo_debug_mode');
        register_setting('ucp_settings_group', 'ucp_woo_dev_mode');
        register_setting('ucp_settings_group', 'ucp_woo_default_order_status');
        register_setting('ucp_settings_group', 'ucp_woo_agent_whitelist');
        register_setting('ucp_settings_group', 'ucp_woo_max_order_total');
        register_setting('ucp_settings_group', 'ucp_woo_enabled_capabilities');

        // Handle manual permalink flush
        if (isset($_GET['flush_ucp_rules']) && check_admin_referer('ucp_flush_nonce')) {
            add_action('admin_init', function() {
                ucp_woo_add_rewrite_rules();
                flush_rewrite_rules();
                add_settings_error('ucp_settings_group', 'ucp_rules_flushed', __('Permalinks flushed successfully!', 'ucp-for-woocommerce'), 'updated');
            });
        }
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('UCP for WooCommerce Settings', 'ucp-for-woocommerce'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('ucp_settings_group'); ?>
                <?php do_settings_sections('ucp_settings_group'); ?>
                
                <h2 class="title"><?php _e('General Settings', 'ucp-for-woocommerce'); ?></h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Enable Debug Mode', 'ucp-for-woocommerce'); ?></th>
                        <td>
                            <input type="checkbox" name="ucp_woo_debug_mode" value="1" <?php checked(1, get_option('ucp_woo_debug_mode'), true); ?> />
                            <p class="description"><?php _e('Logs API requests and errors to wp-content/debug.log (WP_DEBUG must be enabled).', 'ucp-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Enable Development (Bypass) Mode', 'ucp-for-woocommerce'); ?></th>
                        <td>
                            <input type="checkbox" name="ucp_woo_dev_mode" value="1" <?php checked(1, get_option('ucp_woo_dev_mode'), true); ?> />
                            <p class="description"><?php _e('Allows testing with "test" signature without full JWT verification. WARNING: Disable in production!', 'ucp-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Default Order Status', 'ucp-for-woocommerce'); ?></th>
                        <td>
                            <select name="ucp_woo_default_order_status">
                                <option value="processing" <?php selected('processing', get_option('ucp_woo_default_order_status', 'processing')); ?>><?php _e('Processing', 'ucp-for-woocommerce'); ?></option>
                                <option value="on-hold" <?php selected('on-hold', get_option('ucp_woo_default_order_status')); ?>><?php _e('On Hold', 'ucp-for-woocommerce'); ?></option>
                                <option value="pending" <?php selected('pending', get_option('ucp_woo_default_order_status')); ?>><?php _e('Pending Payment', 'ucp-for-woocommerce'); ?></option>
                            </select>
                            <p class="description"><?php _e('The status given to new orders created by AI agents.', 'ucp-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('Security Settings', 'ucp-for-woocommerce'); ?></h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Max Order Total', 'ucp-for-woocommerce'); ?></th>
                        <td>
                            <input type="number" name="ucp_woo_max_order_total" value="<?php echo esc_attr(get_option('ucp_woo_max_order_total', '500')); ?>" step="0.01" />
                            <p class="description"><?php _e('Maximum total amount allowed for a single AI-initiated order. Set to 0 for no limit.', 'ucp-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Agent Profile Whitelist', 'ucp-for-woocommerce'); ?></th>
                        <td>
                            <textarea name="ucp_woo_agent_whitelist" rows="5" cols="50" class="large-text"><?php echo esc_textarea(get_option('ucp_woo_agent_whitelist')); ?></textarea>
                            <p class="description"><?php _e('Enter allowed agent profile URLs (one per line). Leave empty to allow all agents with valid signatures.', 'ucp-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('UCP Capabilities', 'ucp-for-woocommerce'); ?></h2>
                <p><?php _e('Enable or disable specific UCP features to keep the plugin lightweight.', 'ucp-for-woocommerce'); ?></p>
                <table class="form-table">
                    <?php 
                    $caps = get_option('ucp_woo_enabled_capabilities', ['checkout', 'discovery']); 
                    ?>
                    <tr valign="top">
                        <th scope="row"><?php _e('Checkout', 'ucp-for-woocommerce'); ?></th>
                        <td>
                            <input type="checkbox" name="ucp_woo_enabled_capabilities[]" value="checkout" <?php checked(in_array('checkout', $caps)); ?> />
                            <p class="description"><?php _e('Allows AI agents to submit orders.', 'ucp-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php _e('Product Discovery', 'ucp-for-woocommerce'); ?></th>
                        <td>
                            <input type="checkbox" name="ucp_woo_enabled_capabilities[]" value="discovery" <?php checked(in_array('discovery', $caps)); ?> />
                            <p class="description"><?php _e('Allows AI agents to discover products (Enhanced).', 'ucp-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2 class="title"><?php _e('Tools', 'ucp-for-woocommerce'); ?></h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Fix Discovery (404)', 'ucp-for-woocommerce'); ?></th>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ucp-settings&flush_ucp_rules=1'), 'ucp_flush_nonce'); ?>" class="button button-secondary">
                                <?php _e('Flush Permalinks', 'ucp-for-woocommerce'); ?>
                            </a>
                            <p class="description"><?php _e('Click this if you get a 404 error when visiting /.well-known/ucp.', 'ucp-for-woocommerce'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get setting value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = false) {
        return get_option($key, $default);
    }
}
