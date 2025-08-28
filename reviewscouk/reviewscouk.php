<?php
/**
 * 2024 REVIEWS.io
 *
 *  @author    REVIEWS.io <support@reviews.io>
 *  @copyright 2007-2023 REVIEWS.io
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
if (!defined('_PS_VERSION_')) exit;
class ReviewsCoUk extends Module
{
    protected $configOptions = array(
        'REVIEWSCOUK_CONFIG_REGION',
        'REVIEWSCOUK_CONFIG_STOREID',
        'REVIEWSCOUK_CONFIG_APIKEY',
        'REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET',
        'REVIEWSCOUK_CONFIG_AUTO_MERCHANT',
        'REVIEWSCOUK_CONFIG_AUTO_PRODUCT',
        'REVIEWSCOUK_USE_ID',
        'REVIEWSCOUK_CONFIG_PRODUCT_WIDGET_SKU_OPTION',
        'REVIEWSCOUK_CONFIG_WIDGET_COLOR',
        'REVIEWSCOUK_CONFIG_PRODUCT_RICH_SNIPPET',
        'REVIEWSCOUK_CONFIG_WRITE_REVIEW_BUTTON'
    );
    public function __construct()
    {
        $this->name = 'reviewscouk';
        $this->tab = 'others';
        $this->version = '1.2.7';
        $this->author = 'REVIEWS.io';
        $this->module_key = '7f216a86f806f343c2888324f3504ecf';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array(
            'min' => '1.5',
            'max' => '8.99.99'
        );
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('REVIEWS.io');
        $this->description = $this->l('Automatically queue Merchant and Product review requests, and display reviews on product pages.');
        $this->confirmUninstall = $this->l('No REVIEWS.io Integration? :(');
        if (($this->isConfigVarNad('REVIEWSCOUK_CONFIG_APIKEY') + ($this->isConfigVarNad('REVIEWSCOUK_CONFIG_STOREID'))) < 3) {
            $this->warning = $this->l('Make sure that your STORE ID and API KEY are set.');
        }
    }
    public function isConfigVarNad($config_var)
    {
        if (!Configuration::get($config_var) || (string)Configuration::get($config_var) == '') {
            return 2;
        } else {
            return 0;
        }
    }
    public function install()
    {
        if (!function_exists('curl_init')) {
            $this->_errors[] = $this->l('Reviews.co.uk requires cURL.');
            return false;
        }
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
        if (!parent::install() || !$this->registerHook('displayProductAdditionalInfo') || !$this->registerHook('actionOrderStatusPostUpdate')) {
            return false;
        }
        return true;
    }
    public function uninstall()
    {
        foreach ($this->configOptions as $configOption) {
            Configuration::deleteByName($configOption);
        }
        return parent::uninstall();
    }
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            foreach ($this->configOptions as $updateOption) {
                $val = Tools::getValue($updateOption);
                if ($val !== false && $val !== '') {
                    Configuration::updateValue($updateOption, $val);
                }
            }
            $output = $this->displayConfirmation($this->l('Settings Updated'));
        }
        return $output . $this->displayForm();
    }
    protected function yesNoOption($name, $title)
    {
        $yes_no_options = array(
            array(
                'id_option' => 1,
                'name' => 'Yes'
            ),
            array(
                'id_option' => 2,
                'name' => 'No'
            )
        );
        return array(
            'type' => 'select',
            'label' => $this->l($title),
            'name' => $name,
            'required' => true,
            'options' => array(
                'query' => $yes_no_options,
                'id' => 'id_option',
                'name' => 'name'
            )
        );
    }
    public function displayForm()
    {
        $fields_form = array();
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings')
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Region: '),
                    'name' => 'REVIEWSCOUK_CONFIG_REGION',
                    'required' => true,
                    'options' => array(
                        'query' => array(
                            array(
                                'id_option' => 1,
                                'name' => 'UK (Reviews.co.uk)'
                            ),
                            array(
                                'id_option' => 2,
                                'name' => 'US (Reviews.io)'
                            )
                        ),
                        'id' => 'id_option',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Your Reviews.co.uk Store ID'),
                    'name' => 'REVIEWSCOUK_CONFIG_STOREID',
                    'size' => 20,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Your Reviews.co.uk API Key'),
                    'name' => 'REVIEWSCOUK_CONFIG_APIKEY',
                    'size' => 20,
                    'required' => true
                ),
                $this->yesNoOption('REVIEWSCOUK_CONFIG_AUTO_MERCHANT', 'Automatic Merchant Review Requests:'),
                $this->yesNoOption('REVIEWSCOUK_CONFIG_AUTO_PRODUCT', 'Automatic Product Review Requests:'),
                $this->yesNoOption('REVIEWSCOUK_USE_ID', 'Use Product IDs instead of References:'),
                $this->yesNoOption('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET', 'Display the Product Reviews Widget:'),
                $this->yesNoOption('REVIEWSCOUK_CONFIG_WRITE_REVIEW_BUTTON', 'Show Write Review Button on Product Widget:'),
                array(
                    'type' => 'select',
                    'label' => $this->l('Location For Grabbing SKU\'s (References Selected) : '),
                    'name' => 'REVIEWSCOUK_CONFIG_PRODUCT_WIDGET_SKU_OPTION',
                    'required' => false,
                    'options' => array(
                        'query' => array(
                            array(
                                'id_option' => 1,
                                'name' => 'Use Attributes (Legacy)'
                            ),
                            array(
                                'id_option' => 2,
                                'name' => 'Use Reference Field'
                            )
                        ),
                        'id' => 'id_option',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Product Reviews Widget Hex Color: '),
                    'name' => 'REVIEWSCOUK_CONFIG_WIDGET_COLOR',
                    'size' => 10,
                    'required' => false
                ),
                $this->yesNoOption('REVIEWSCOUK_CONFIG_PRODUCT_RICH_SNIPPET', 'Enable Product Rich Snippets:'),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = array(
            'save' => array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
        foreach ($this->configOptions as $configOption) {
            $helper->fields_value[$configOption] = Configuration::get($configOption);
        }
        return $helper->generateForm($fields_form);
    }
    protected function isColor($color)
    {
        return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/i', $color);
    }

    public function hookDisplayProductAdditionalInfo($params)
    {
        if (Configuration::get('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET') == '1') {
            $product_sku = Configuration::get('REVIEWSCOUK_USE_ID') == '1' ? $params['product']['id'] : $this->getSku($params);
            $store_id = Configuration::get('REVIEWSCOUK_CONFIG_STOREID');
            $color = Configuration::get('REVIEWSCOUK_CONFIG_WIDGET_COLOR');
            $writeButton = Configuration::get('REVIEWSCOUK_CONFIG_WRITE_REVIEW_BUTTON');
            if (!$this->isColor($color)) {
                $color = '#10D577';
            }

            $data = '

            <script src="' . $this->widgetDomain() . 'polaris/build.js"></script>

						<div id="ReviewsWidget"></div>

            <script type="text/javascript">

						new ReviewsWidget("#ReviewsWidget", {

							//Your REVIEWS.io account ID and widget type:

							store: "' . $store_id . '",

							widget: "polaris",



							//Content settings (store_review,product_review,questions). Choose what to display in this widget:

							options: {

								types: "product_review,questions",

								lang: "en",

								//Possible layout options: bordered, large and reverse.

								layout: "",

								//How many reviews & questions to show per page?

								per_page: 15,

								//Product specific settings. Provide product SKU for which reviews should be displayed:

								product_review:{

										//Display product reviews - include multiple product SKUs seperated by Semi-Colons (Main Indentifer in your product catalog )

										sku: "' . $product_sku . '",

										hide_if_no_results: false,

										enable_rich_snippets: ' . (Configuration::get('REVIEWSCOUK_CONFIG_PRODUCT_RICH_SNIPPET') == '1' ? true : false) . '

								},

								//Questions settings:

								questions:{

										hide_if_no_results: false,

										enable_ask_question: true,

										show_dates: true,

										//Display group questions by providing a grouping variable, new questions will be assigned to this group.

										grouping:"' . $product_sku . '",

								},



								//Header settings:

								header:{

										enable_summary: true, //Show overall rating & review count

										enable_ratings: true,

										enable_attributes: true,

										enable_image_gallery: true, //Show photo & video gallery

										enable_percent_recommended: false, //Show what percentage of reviewers recommend it

										enable_write_review: ' . ($writeButton == '1' ? 'true' : 'false') . ', //Show "Write Review" button

										enable_ask_question: true, //Show "Ask Question" button

										enable_sub_header: true, //Show subheader

								},



								//Filtering settings:

								filtering:{

										enable: true, //Show filtering options

										enable_text_search: true, //Show search field

										enable_sorting: true, //Show sorting options (most recent, most popular)

										enable_overall_rating_filter: true, //Show overall rating breakdown filter

										enable_ratings_filters: true, //Show product attributes filter

										enable_attributes_filters: true, //Show author attributes filter

								},



								//Review settings:

								reviews:{

										enable_avatar: true, //Show author avatar

										enable_reviewer_name:  true, //Show author name

										enable_reviewer_address:  true, //Show author location

										reviewer_address_format: "city, country", //Author location display format

										enable_verified_badge: true, //Show "Verified Customer" badge

										enable_reviewer_recommends: true, //Show "I recommend it" badge

										enable_attributes: true, //Show author attributes

										enable_product_name: true, //Show display product name

										enable_images: true, //Show display review photos

										enable_ratings: true, //Show product attributes (additional ratings)

										enable_share: true, //Show share buttons

										enable_helpful_vote: true, //Show "was this helpful?" section

										enable_helpful_display: true, //Show how many times times review upvoted

										enable_report: true, //Show report button

										enable_date: true, //Show when review was published

								},

							},

							//Translation settings

							translations: {

								"Verified Customer": "Verified Customer"

							},

							//Style settings:

							styles: {

								//Base font size is a reference size for all text elements. When base value gets changed, all TextHeading and TexBody elements get proportionally adjusted.

								"--base-font-size": "16px",



								//Button styles (shared between buttons):

								"--common-button-font-family": "inherit",

								"--common-button-font-size":"16px",

								"--common-button-font-weight":"500",

								"--common-button-letter-spacing":"0",

								"--common-button-text-transform":"none",

								"--common-button-vertical-padding":"10px",

								"--common-button-horizontal-padding":"20px",

								"--common-button-border-width":"2px",

								"--common-button-border-radius":"0px",



								//Primary button styles:

								"--primary-button-bg-color": "#0E1311",

								"--primary-button-border-color": "#0E1311",

								"--primary-button-text-color": "#ffffff",



								//Secondary button styles:

								"--secondary-button-bg-color": "transparent",

								"--secondary-button-border-color": "#0E1311",

								"--secondary-button-text-color": "#0E1311",



								//Star styles:

								"--common-star-color": "' . $color . '",

								"--common-star-disabled-color": "rgba(0,0,0,0.25)",

								"--medium-star-size": "22px",

								"--small-star-size": "19px",



								//Heading styles:

								"--heading-text-color": "#0E1311",

								"--heading-text-font-weight": "600",

								"--heading-text-font-family": "inherit",

								"--heading-text-line-height": "1.4",

								"--heading-text-letter-spacing": "0",

								"--heading-text-transform": "none",



								//Body text styles:

								"--body-text-color": "#0E1311",

								"--body-text-font-weight": "400",

								"--body-text-font-family": "inherit",

								"--body-text-line-height": "1.4",

								"--body-text-letter-spacing": "0",

								"--body-text-transform": "none",



								//Input field styles:

								"--inputfield-text-font-family": "inherit",

								"--input-text-font-size": "14px",

								"--inputfield-text-font-weight": "400",

								"--inputfield-text-color": "#0E1311",

								"--inputfield-border-color": "rgba(0,0,0,0.2)",

								"--inputfield-background-color": "transparent",

								"--inputfield-border-width": "1px",

								"--inputfield-border-radius": "0px",



								"--common-border-color": "rgba(0,0,0,0.15)",

								"--common-border-width": "1px",

								"--common-sidebar-width": "190px",



								//Slider indicator (for attributes) styles:

								"--slider-indicator-bg-color": "rgba(0,0,0,0.1)",

								"--slider-indicator-button-color": "#0E1311",

								"--slider-indicator-width": "190px",



								//Badge styles:

								"--badge-icon-color": "#0E1311",

								"--badge-icon-font-size": "inherit",

								"--badge-text-color": "#0E1311",

								"--badge-text-font-size": "inherit",

								"--badge-text-letter-spacing": "inherit",

								"--badge-text-transform": "inherit",



								//Author styles:

								"--author-font-size": "inherit",

								"--author-text-transform": "none",



								//Author avatar styles:

								"--avatar-thumbnail-size": "60px",

								"--avatar-thumbnail-border-radius": "100px",

								"--avatar-thumbnail-text-color": "#0E1311",

								"--avatar-thumbnail-bg-color": "rgba(0,0,0,0.1)",



								//Product photo or review photo styles:

								"--photo-video-thumbnail-size": "80px",

								"--photo-video-thumbnail-border-radius": "0px",



								//Media (photo & video) slider styles:

								"--mediaslider-scroll-button-icon-color": "#0E1311",

								"--mediaslider-scroll-button-bg-color": "rgba(255, 255, 255, 0.85)",

								"--mediaslider-overlay-text-color": "#ffffff",

								"--mediaslider-overlay-bg-color": "rgba(0, 0, 0, 0.8))",

								"--mediaslider-item-size": "110px",



								//Pagination & tabs styles (normal):

								"--pagination-tab-text-color": "#0E1311",

								"--pagination-tab-text-transform": "none",

								"--pagination-tab-text-letter-spacing": "0",

								"--pagination-tab-text-font-size": "16px",

								"--pagination-tab-text-font-weight": "600",



								//Pagination & tabs styles (active):

								"--pagination-tab-active-text-color": "#0E1311",

								"--pagination-tab-active-text-font-weight": "600",

								"--pagination-tab-active-border-color": "#0E1311",

								"--pagination-tab-border-width": "3px",

							},

							});

            </script>

            ';
            $smarty = Context::getContext()->smarty;
            $smarty->assign(array(
                'data' => $data
            ));
            return $this->display(__FILE__, 'views/templates/front/widget.tpl');
        }
        return '';
    }
    protected function prepareOrderData($params)
    {
        $order_id = $params['id_order'];
        $order = new Order((int)$params['id_order']);
        $id_customer = $order->id_customer;
        $customer = new Customer((int)$id_customer);
        $first_name = $customer->firstname;
        $last_name = $customer->lastname;
        $email = $customer->email;
        $products_array = [];

        try {
            $products_array = $this->formatProducts($order->getProducts());
        } catch (\Exception $e) {
            \Logger::addLog('REVIEWS.io Error: Failed to get products data.', 2);
        }
        $orderData = array(
            'name' => $first_name . ' ' . $last_name,
            'email' => $email,
            'order_id' => $order_id,
            'products' => $products_array
        );
        return $orderData;
    }

    public function formatProducts($products)
    {
        $products_array = array();
        foreach ($products as $p) {
            $product = new Product((int)$p['id_product'], true, Context::getContext()->language->id);
            $combinations = (isset($p['product_attribute_id']))
                ? $product->getAttributeCombinationsById((int)$p['product_attribute_id'], Context::getContext()->language->id)
                : array();
            $comb = count($combinations) > 0 ? (object) $combinations[0] : null;
            $image = Image::getCover((int)$p['id_product']);
            $link = new Link();
            if ($image && isset($image['id_image'])) {
                $image_url = $link->getImageLink($product->link_rewrite, $image['id_image'], 'large_default');
                $full_image_url = (strpos($image_url, 'http') === 0) ? $image_url : $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/' . $image_url;
            } else {
                $full_image_url = '';
            }
            $description = !empty($product->description) ? $product->description : $product->description_short;
            $productId = isset($comb) ? $p['product_attribute_id'] : $p['id_product'];
            array_push(
                $products_array,
                array(
                    'id' => $productId,
                    'link' => $product->getLink(),
                    'name' => $product->name,
                    'brand' => !empty($product->manufacturer_name) ? $product->manufacturer_name : '',
                    'sku' => Configuration::get('REVIEWSCOUK_USE_ID') == '1' ? $productId : $this->getAttribute($product, $comb, 'reference'),
                    'gtin' => $this->getAttribute($product, $comb, 'ean13'),
                    'mpn' => $this->getAttribute($product, $comb, 'upc'),
                    'image_url' => $full_image_url,
                    'category' => implode(' > ', $this->getProductCategories($product)),
                    'tags' => $product->getTags(Context::getContext()->language->id),
                    'meta_title' => !empty($product->meta_title) ? $product->meta_title : $product->name,
                    'description' => $product->meta_description
                )
            );
        }

        return $products_array;
    }

    private function getProductCategories($product)
    {
        $categories = array();
        $productCategoriesFull = $product->getProductCategoriesFull($product->id);
        foreach ($productCategoriesFull as $category) {
            array_push($categories, $category['name']);
        }
        return $categories;
    }

    private function getSku($params)
    {
        $skuLocationOption = (int) Configuration::get('REVIEWSCOUK_CONFIG_PRODUCT_WIDGET_SKU_OPTION');

        switch ($skuLocationOption) {
            case 1:
                return $this->getAttribute($params['product'], null, 'widget_reference');
            case 2:
                return $this->getSkuFromReference($params['product']);
            default:
                return $this->getAttribute($params['product'], null, 'widget_reference');
        }
    }

    private function getSkuFromReference($product)
    {
        if (empty(($product))) {
            return '';
        }

        $skus = [];
        $reference = isset($product['reference']) ? (string) $product['reference'] : '';
        if (empty($reference)) {
            return '';
        }

        $skuArray = preg_split("/[,;\s]/", $reference);

        foreach ($skuArray as $sku) {
            $sku = trim($sku);
            if (!empty($sku)) {
                $skus[] = $sku;
            }
        }

        return implode(';', $skus);
    }

    private function getAttribute($product, $combination, $selector)
    {
        if (isset($combination) && is_object($combination) && !empty($combination->{$selector})) {
            return $combination->{$selector};
        }

        if (is_array($product)) {
            if (!empty($product[$selector])) {
                return $product[$selector];
            }
        } elseif (is_object($product) && !empty($product->{$selector})) {
            return $product->{$selector};
        }

        if ($selector == 'widget_reference') {
            $attributes = is_array($product) ? (isset($product['attributes']) ? $product['attributes'] : []) : 
                         (isset($product->attributes) ? $product->attributes : []);
            
            if (!empty($attributes) && is_array($attributes)) {
                $skus = [];
                foreach ($attributes as $attrArray) {
                    if (isset($attrArray['reference'])) {
                        $skus[] = $attrArray['reference'];
                    }
                }
                return implode(';', $skus);
            }
        }

        return '';
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        if ($params['newOrderStatus']->shipped == '1') {
            if ((Configuration::get('REVIEWSCOUK_CONFIG_AUTO_MERCHANT') == '1') || (Configuration::get('REVIEWSCOUK_CONFIG_AUTO_PRODUCT') == '1')) {
                try {
                    $orderData = $this->prepareOrderData($params);
                    if (Configuration::get('REVIEWSCOUK_CONFIG_AUTO_MERCHANT') == '1') {
                        $this->apiPostRequest('merchant/invitation', $orderData);
                    }
                    if (Configuration::get('REVIEWSCOUK_CONFIG_AUTO_PRODUCT') == '1') {
                        $this->apiPostRequest('product/invitation', $orderData);
                    }
                } catch (\Exception $e) {
                    \Logger::addLog('REVIEWS.io Error: Failed to get updated order status.', 2);
                }
            }
        }
    }
    protected function apiHeaders()
    {
        return array(
            'Content-Type: application/json',
            'store: ' . Configuration::get('REVIEWSCOUK_CONFIG_STOREID'),
            'apikey: ' . Configuration::get('REVIEWSCOUK_CONFIG_APIKEY')
        );
    }
    protected function createSubDomain($subdomain)
    {
        $region = Configuration::get('REVIEWSCOUK_CONFIG_REGION');
        return ($region == 2) ? 'https://' . $subdomain . '.reviews.io/' : 'https://' . $subdomain . '.reviews.co.uk/';
    }
    protected function apiDomain()
    {
        return $this->createSubDomain('api');
    }
    protected function widgetDomain()
    {
        return $this->createSubDomain('widget');
    }
    protected function apiPostRequest($url, $postData)
    {
        if (!Configuration::get('REVIEWSCOUK_CONFIG_STOREID') || !Configuration::get('REVIEWSCOUK_CONFIG_APIKEY')) {
            return;
        }
        
        $ch = curl_init($this->apiDomain() . $url);
        if (!$ch) {
            return;
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->apiHeaders());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        $data = curl_exec($ch);
        curl_close($ch);
    }
}
