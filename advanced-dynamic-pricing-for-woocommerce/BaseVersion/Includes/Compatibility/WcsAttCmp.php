<?php

namespace ADP\BaseVersion\Includes\Compatibility;

use ADP\BaseVersion\Includes\Context;

defined('ABSPATH') or exit;

/**
 * Offer your existing products on subscription, with this powerful add-on for WooCommerce Subscriptions.
 *
 * Plugin Name: WooCommerce All Products For Subscriptions
 * Author: WooCommerce
 *
 * @see https://woocommerce.com/products/all-products-for-woocommerce-subscriptions/
 */
class WcsAttCmp
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var null|\WCS_ATT
     */
    protected $wcsAtt;

    /**
     * @param null $deprecated
     */
    public function __construct($deprecated = null)
    {
        $this->context = adp_context();
        $this->loadRequirements();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function loadRequirements()
    {
        if ( ! did_action('plugins_loaded')) {
            /* translators: Message about the load order*/
            _doing_it_wrong(__FUNCTION__, sprintf(esc_html__('%1$s should not be called earlier the %2$s action.',
                'advanced-dynamic-pricing-for-woocommerce'), 'loadRequirements', 'plugins_loaded'), esc_html(WC_ADP_VERSION));
        }

        $this->wcsAtt = class_exists("\WCS_ATT") ? \WCS_ATT::instance() : null;

        if ($this->isActive() && class_exists('\WCS_ATT_Display_Cart')) {
            remove_filter('woocommerce_cart_item_price', array('\WCS_ATT_Display_Cart', 'show_cart_item_subscription_options'), 1000);
            add_filter('woocommerce_cart_item_price', array('\WCS_ATT_Display_Cart', 'show_cart_item_subscription_options'), 10001, 3);
        }

        if ($this->isActive() && isset($this->wcsAtt->product_data) ) {
            $this->wcsAtt->product_data = new ADP_WCS_ATT_Product_Data_Wrapper($this->wcsAtt->product_data);
        }
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return ! is_null($this->wcsAtt) && ($this->wcsAtt instanceof \WCS_ATT);
    }
}

if (!class_exists('ADP_WCS_ATT_Product_Data_Wrapper')) {
    class ADP_WCS_ATT_Product_Data_Wrapper {
        private $handle;

        public function __construct($handle) {
            $this->handle = ($handle instanceof self) ? $handle->handle : $handle;
        }

        protected function get_id_val($product, $key) {
            if (!is_object($product) || !method_exists($product, 'get_id')) {
                return null;
            }

            return md5($product->get_id() . $key . 'adp_wcsatt_unique');
        }


        public function get($product, $key, $default = null) {
            $id_val = $this->get_id_val($product, $key);
            if (!$id_val) return $default;

            if (method_exists($product, 'get_meta')) {
                $value = $product->get_meta('_adp_wcsatt_' . $id_val, true);
                if (!empty($value)) {
                    return maybe_unserialize($value);
                }
            }

            if ($this->handle && method_exists($this->handle, 'get')) {
                return $this->handle->get($product, $key, $default);
            }

            return $default;
        }

        public function set($product, $key, $value) {
            $id_val = $this->get_id_val($product, $key);
            if (!$id_val) return;

            if (method_exists($product, 'update_meta_data')) {
                $product->update_meta_data('_adp_wcsatt_' . $id_val, maybe_serialize($value));
            }

            if ($this->handle && method_exists($this->handle, 'set')) {
                $this->handle->set($product, $key, $value);
            }
        }

        public function delete($product, $key) {
            $id_val = $this->get_id_val($product, $key);
            if (!$id_val) return false;

            if (method_exists($product, 'delete_meta_data')) {
                $product->delete_meta_data('_adp_wcsatt_' . $id_val);
            }

            if ($this->handle && method_exists($this->handle, 'delete')) {
                return $this->handle->delete($product, $key);
            }

            return false;
        }
    }

}
