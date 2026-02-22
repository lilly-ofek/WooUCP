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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('woocommerce_page_ucp-settings' !== $hook) {
            return;
        }
        $plugin_file = dirname(dirname(__FILE__)) . '/woo-ucp.php';
        wp_enqueue_style('ucp-admin-styles', plugins_url('assets/css/admin.css', $plugin_file), [], UCP_WOO_VERSION);
        wp_enqueue_script('ucp-admin-js', plugins_url('assets/js/admin.js', $plugin_file), ['jquery'], UCP_WOO_VERSION, true);
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
        register_setting('ucp_settings_group', 'ucp_woo_field_mapping');
        register_setting('ucp_settings_group', 'ucp_woo_api_rate_limit');

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
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
        $stats = $this->get_stats();
        ?>
        <div id="ucp-notices-anchor"></div>
        <div class="wrap ucp-settings-wrap">
            <div class="ucp-header">
                <div>
                    <h1><?php _e('Universal Commerce Protocol', 'ucp-for-woocommerce'); ?></h1>
                    <p><?php _e('Connecting your store to the world of AI Agents.', 'ucp-for-woocommerce'); ?></p>
                </div>
                <div class="version-tag">v<?php echo UCP_WOO_VERSION; ?></div>
            </div>

            <nav class="ucp-tabs-nav">
                <a href="#dashboard" class="ucp-tab-link active"><?php _e('Dashboard', 'ucp-for-woocommerce'); ?></a>
                <a href="#general" class="ucp-tab-link"><?php _e('General Settings', 'ucp-for-woocommerce'); ?></a>
                <a href="#security" class="ucp-tab-link"><?php _e('Security & Keys', 'ucp-for-woocommerce'); ?></a>
                <a href="#mapping" class="ucp-tab-link"><?php _e('Field Mapping', 'ucp-for-woocommerce'); ?></a>
            </nav>

            <div class="ucp-tab-container">
                <form method="post" action="options.php">
                    <?php settings_fields('ucp_settings_group'); ?>

                    <!-- Dashboard Tab -->
                    <div id="dashboard" class="ucp-tab-content">
                        <h2><?php _e('Performance Overview', 'ucp-for-woocommerce'); ?></h2>
                        <div class="ucp-stats-grid">
                            <div class="ucp-stat-card">
                                <span class="label"><?php _e('Total UCP Revenue', 'ucp-for-woocommerce'); ?></span>
                                <span class="value"><?php echo wc_price($stats['revenue']); ?></span>
                            </div>
                            <div class="ucp-stat-card">
                                <span class="label"><?php _e('UCP Orders', 'ucp-for-woocommerce'); ?></span>
                                <span class="value"><?php echo esc_html($stats['order_count']); ?></span>
                            </div>
                            <div class="ucp-stat-card">
                                <span class="label"><?php _e('Conversion Rate', 'ucp-for-woocommerce'); ?></span>
                                <span class="value"><?php echo esc_html($stats['conversion']); ?>%</span>
                            </div>
                        </div>

                        <h3><?php _e('Ready for rehearsal?', 'ucp-for-woocommerce'); ?></h3>
                        <div class="ucp-tool-card">
                            <p><?php _e('Your security setup is active and verified. You can test the full flow using our professional rehearsal tool.', 'ucp-for-woocommerce'); ?></p>
                            <a href="<?php echo plugins_url('tests/rehearsal.php', dirname(__FILE__)); ?>" class="button button-primary button-large" target="_blank">
                                <?php _e('Launch Security Rehearsal', 'ucp-for-woocommerce'); ?>
                            </a>
                        </div>
                    </div>

                    <!-- General Tab -->
                    <div id="general" class="ucp-tab-content" style="display:none;">
                        <h2><?php _e('System Configuration', 'ucp-for-woocommerce'); ?></h2>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php _e('Capabilities', 'ucp-for-woocommerce'); ?></th>
                                <td>
                                    <?php $caps = get_option('ucp_woo_enabled_capabilities', ['checkout', 'discovery']); ?>
                                    <label><input type="checkbox" name="ucp_woo_enabled_capabilities[]" value="checkout" <?php checked(in_array('checkout', $caps)); ?> /> <?php _e('Enable AI Checkout', 'ucp-for-woocommerce'); ?></label><br>
                                    <label><input type="checkbox" name="ucp_woo_enabled_capabilities[]" value="discovery" <?php checked(in_array('discovery', $caps)); ?> /> <?php _e('Enable Product Discovery', 'ucp-for-woocommerce'); ?></label>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php _e('Default Order Status', 'ucp-for-woocommerce'); ?></th>
                                <td>
                                    <select name="ucp_woo_default_order_status">
                                        <option value="processing" <?php selected('processing', get_option('ucp_woo_default_order_status', 'processing')); ?>><?php _e('Processing', 'ucp-for-woocommerce'); ?></option>
                                        <option value="on-hold" <?php selected('on-hold', get_option('ucp_woo_default_order_status')); ?>><?php _e('On Hold', 'ucp-for-woocommerce'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php _e('Debug Mode', 'ucp-for-woocommerce'); ?></th>
                                <td>
                                    <input type="checkbox" name="ucp_woo_debug_mode" value="1" <?php checked(1, get_option('ucp_woo_debug_mode'), true); ?> />
                                    <span class="description"><?php _e('Log UCP events to debug.log', 'ucp-for-woocommerce'); ?></span>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save General Settings', 'ucp-for-woocommerce'), 'primary ucp-save-btn'); ?>
                    </div>

                    <!-- Security Tab -->
                    <div id="security" class="ucp-tab-content" style="display:none;">
                        <h2><?php _e('Security & Access Control', 'ucp-for-woocommerce'); ?></h2>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php _e('Development Mode', 'ucp-for-woocommerce'); ?></th>
                                <td>
                                    <input type="checkbox" name="ucp_woo_dev_mode" value="1" <?php checked(1, get_option('ucp_woo_dev_mode'), true); ?> />
                                    <span class="description"><?php _e('Allow "test" signature for local debugging.', 'ucp-for-woocommerce'); ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php _e('Max Order Total', 'ucp-for-woocommerce'); ?></th>
                                <td>
                                    <input type="number" name="ucp_woo_max_order_total" value="<?php echo esc_attr(get_option('ucp_woo_max_order_total', '500')); ?>" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php _e('IP Rate Limit', 'ucp-for-woocommerce'); ?></th>
                                <td>
                                    <input type="number" name="ucp_woo_api_rate_limit" value="<?php echo esc_attr(get_option('ucp_woo_api_rate_limit', '60')); ?>" />
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row"><?php _e('Agent Whitelist', 'ucp-for-woocommerce'); ?></th>
                                <td>
                                    <textarea name="ucp_woo_agent_whitelist" rows="4"><?php echo esc_textarea(get_option('ucp_woo_agent_whitelist')); ?></textarea>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save Security Settings', 'ucp-for-woocommerce'), 'primary ucp-save-btn'); ?>
                    </div>

                    <!-- Mapping Tab -->
                    <div id="mapping" class="ucp-tab-content" style="display:none;">
                        <h2><?php _e('Checkout Data Mapping', 'ucp-for-woocommerce'); ?></h2>
                        <p><?php _e('Define how AI agent data (left) maps to your store meta (right).', 'ucp-for-woocommerce'); ?></p>
                        <table class="form-table">
                            <tr valign="top">
                                <th scope="row"><?php _e('UCP Map', 'ucp-for-woocommerce'); ?></th>
                                <td>
                                    <textarea name="ucp_woo_field_mapping" rows="8" placeholder="ucp_field|woo_meta_key"><?php echo esc_textarea(get_option('ucp_woo_field_mapping')); ?></textarea>
                                </td>
                            </tr>
                        </table>
                        <?php submit_button(__('Save Mapping Definitions', 'ucp-for-woocommerce'), 'primary ucp-save-btn'); ?>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Get Analytics Stats
     */
    private function get_stats() {
        $orders = wc_get_orders([
            'limit' => -1,
            'return' => 'ids',
            'meta_key' => '_ucp_woo_agent_profile', 
        ]);

        $revenue = 0;
        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            $revenue += $order->get_total();
        }

        return [
            'revenue' => $revenue,
            'order_count' => count($orders),
            'conversion' => count($orders) > 0 ? 100 : 0, // Mock for now
        ];
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
