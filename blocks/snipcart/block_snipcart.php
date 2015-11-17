<?php

/**
 * Snipcart shopping cart block
 *
 * @package   block_snipcart
 * @author    Tim Butler
 * @copyright (c) 2015 Harcourts International Limited {@link http://www.harcourtsacademy.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot."/enrol/snipcart/classes/snipcartaccounts.php");

class block_snipcart extends block_base {
    public function init() {
        $this->title = get_string('shoppingcart', 'block_snipcart');
    }
    
    function has_config() {
        return false;
    }

    function applicable_formats() {
        return array('all' => false,
                     'course-index-category' => true,
                     'enrol' => true,
                     'my-index' => true,
            );
    }
    
    public function instance_allow_multiple() {
        return false;
    }
    
    /**
     * Returns true if there is not snipcart product being displayed.
     *
     * @return boolean
     */
    function is_empty() {
    }

    public function get_content() {
        global $OUTPUT, $PAGE, $USER;
        
        if ($this->content !== null) {
            return $this->content;
        }
        
        $PAGE->requires->css('/blocks/snipcart/styles.css');
        
        $plugin = enrol_get_plugin('snipcart');
        
        $manager = \enrol_snipcart\get_snipcartaccounts_manager();
        $currency = $plugin->get_currency_for_country($USER->country);
        $publicapikey = $manager->get_snipcartaccount_info($currency, 'publicapikey');
        

        $shoppingcart = '<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>';
        $shoppingcart.= '<script type="text/javascript" src="https://cdn.snipcart.com/scripts/snipcart.js" id="snipcart"';
        $shoppingcart.= '   data-autopop="false"';
        $shoppingcart.= '   data-api-key="'. $publicapikey .'"></script>';
        $shoppingcart.= '<link type="text/css" href="https://cdn.snipcart.com/themes/base/snipcart.min.css" rel="stylesheet" />';
        $shoppingcart.= '<div class="snipcart-summary">';
        $shoppingcart.= '  <div class="snipcart-image"><a href="#" class="snipcart-checkout"><img src="'.$OUTPUT->pix_url('shopping_cart', 'block_snipcart').'" /></a></div>';
        $shoppingcart.= '  <div class="snipcart-totals"><span class="snipcart-total-items-label">Items:</span> <span class="snipcart-total-items"></span>';
        $shoppingcart.= '  <span class="snipcart-total-price-label">Total: </span><span class="snipcart-total-price"></span></div>';
        $shoppingcart.= '</div>';
        $shoppingcart.= '<div class="checkout">';
        $shoppingcart.= '  <a href="#" class="snipcart-checkout btn btn-small btn-success">' . get_string('checkout', 'block_snipcart') . '</a>';
        $shoppingcart.= '</div>';
        $shoppingcart.= '<div class="snipcart-payment-types">';
        $shoppingcart.= '  <span class="snipcart-payment-type"><img src="'.$OUTPUT->pix_url('amex', 'block_snipcart').'" alt="American Express accepted" /></span>';
        $shoppingcart.= '  <span class="snipcart-payment-type"><img src="'.$OUTPUT->pix_url('master_card', 'block_snipcart').'" alt="Master Card accepted" /></span>';
        $shoppingcart.= '  <span class="snipcart-payment-type"><img src="'.$OUTPUT->pix_url('visa', 'block_snipcart').'" alt="Visa accepted" /></span>';
        $shoppingcart.= '</div>';
        
        $this->content         =  new stdClass;
        $this->content->text   = $shoppingcart;

        return $this->content;
    }

}
