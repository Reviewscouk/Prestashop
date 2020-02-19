<?php
/**
 * 2014 Reviews.co.uk
*
*  @author    Reviews.co.uk <support@reviews.co.uk>
*  @copyright 2007-2016 Reviews.co.uk
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

if (!defined('_PS_VERSION_'))
	exit;

class ReviewsCoUk extends Module
{

	protected $configOptions = array(
		'REVIEWSCOUK_CONFIG_REGION',
		'REVIEWSCOUK_CONFIG_STOREID',
		'REVIEWSCOUK_CONFIG_APIKEY',
		'REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET',
		'REVIEWSCOUK_CONFIG_AUTO_MERCHANT',
		'REVIEWSCOUK_CONFIG_AUTO_PRODUCT',
		'REVIEWSCOUK_CONFIG_WIDGET_COLOR',
		'REVIEWSCOUK_CONFIG_MERCHANT_RICH_SNIPPET',
		'REVIEWSCOUK_CONFIG_PRODUCT_RICH_SNIPPET',
		'REVIEWSCOUK_CONFIG_WRITE_REVIEW_BUTTON'
	);

	public function __construct()
	{
		$this->name = 'reviewscouk';
		$this->tab = 'others';
		$this->version = '1.2.3';
		$this->author = 'Reviews.co.uk Integrations';
		$this->module_key = '7f216a86f806f343c2888324f3504ecf';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7.6');
		$this->bootstrap = true;

		$this->displayName = $this->l('Reviews.co.uk');
		$this->description = $this->l('Automatically queues Merchant and Product review requests. Also includes a reviews widget for your product pages.');
		$this->confirmUninstall = $this->l('No Reviews.co.uk Integration? :(');

		if (($this->isConfigVarNad('REVIEWSCOUK_CONFIG_APIKEY') + ($this->isConfigVarNad('REVIEWSCOUK_CONFIG_STOREID'))) < 3){
			$this->warning = $this->l('Make sure that your STORE ID and API KEY are set.');
        }

		parent::__construct();
    }

	public function isConfigVarNad($config_var)
	{
		if (!Configuration::get($config_var) || (string)Configuration::get($config_var) == ''){
			return 2;
        } else {
			return 0;
        }
    }

	public function install()
	{
		if (!function_exists('curl_init')){
			$this->setError($this->l('Reviews.co.uk requires cURL.'));
        }

		if (Shop::isFeatureActive()){
			Shop::setContext(Shop::CONTEXT_ALL);
        }

		if (!parent::install() || !$this->registerHook('productfooter') || !$this->registerHook('postUpdateOrderStatus') || !$this->registerHook('footer')){
			return false;
        } else {
			return true;
        }
	}

	public function uninstall()
	{
		foreach($this->configOptions as $configOption){
			Configuration::deleteByName($configOption);
		}

		return parent::uninstall();
	}

	public function getContent()
	{
		$output = null;

		if (Tools::isSubmit('submit'.$this->name)){
			foreach($this->configOptions as $updateOption){
				$val = (string)Tools::getValue($updateOption);
				if(!empty($val)){
					Configuration::updateValue($updateOption, $val);
				}
			}

			$output = $this->displayConfirmation($this->l('Settings Updated'));
		}

		return $output.$this->displayForm();
	}

	protected function yesNoOption($name, $title){
		$yes_no_options = array(array('id_option' => 1, 'name' => 'Yes'), array('id_option' => 2,	'name' => 'No'));
		return array('type' => 'select',
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
				'legend' => array('title' => $this->l('Settings')),
				'input' => array(
						array(
								'type' => 'select',
								'label' => $this->l('Region: '),
								'name' => 'REVIEWSCOUK_CONFIG_REGION',
								'required' => true,
								'options' => array(
										'query' => array(array('id_option'=>1, 'name' => 'UK (Reviews.co.uk)'), array('id_option'=>2, 'name' => 'US (Reviews.io)')),
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
						$this->yesNoOption('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET', 'Display the product reviews widget:'),
						$this->yesNoOption('REVIEWSCOUK_CONFIG_WRITE_REVIEW_BUTTON', 'Show Write Review Button on Product Widget:'),
						array(
								'type' => 'text',
								'label' => $this->l('Product Reviews Widget Hex Color: '),
								'name' => 'REVIEWSCOUK_CONFIG_WIDGET_COLOR',
								'size' => 10,
								'required' => false
						),
						$this->yesNoOption('REVIEWSCOUK_CONFIG_PRODUCT_RICH_SNIPPET', 'Enable Product Rich Snippets:'),
						$this->yesNoOption('REVIEWSCOUK_CONFIG_MERCHANT_RICH_SNIPPET', 'Enable Merchant Rich Snippets:'),

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
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->title = $this->displayName;
		$helper->show_toolbar = true;
		$helper->toolbar_scroll = true;
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = array(
				'save' =>
				array(
						'desc' => $this->l('Save'),
						'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
						'&token='.Tools::getAdminTokenLite('AdminModules'),
				),
				'back' => array(
						'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
						'desc' => $this->l('Back to list')
				)
		);

		foreach($this->configOptions as $configOption){
			$helper->fields_value[$configOption] = Configuration::get($configOption);
		}

		return $helper->generateForm($fields_form);
	}

    protected function isColor($color){
       return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/i', $color);
    }

	public function hookfooter($params){
		// If not product page
		if(!($_GET['id_product'] > 0)){
			if (Configuration::get('REVIEWSCOUK_CONFIG_MERCHANT_RICH_SNIPPET') == '1'){
				return $this->getRichSnippetCode();
			}
		}
	}

	protected function getRichSnippetCode($sku=''){
		$storeId = Configuration::get('REVIEWSCOUK_CONFIG_STOREID');
		$region = Configuration::get('REVIEWSCOUK_CONFIG_REGION');

		$code = '<script src="'.$this->widgetDomain().'rich-snippet/dist.js"></script><script>richSnippet({ store: "'.$storeId.'", sku: "'.$sku.'" })</script>';

		return $code;
	}

	public function hookproductfooter($params)
	{
		if (Configuration::get('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET') == '1')
		{
			$product_sku = $params['product']['id'];
			$store_id = Configuration::get('REVIEWSCOUK_CONFIG_STOREID');
			$color = Configuration::get('REVIEWSCOUK_CONFIG_WIDGET_COLOR');
			$writeButton = Configuration::get('REVIEWSCOUK_CONFIG_WRITE_REVIEW_BUTTON');

            if(!$this->isColor($color)){
                $color = '#10D577';
            }

            $data = '
            <script src="'.$this->widgetDomain().'product/dist.js"></script>
            <div id="widget"></div>
            <script type="text/javascript">
            productWidget("widget",{
            store: "'.$store_id.'",
            sku: "'.$product_sku.'",
            primaryClr: "'.$color.'",
            neutralClr: "#EBEBEB",
            buttonClr: "#EEE",
            textClr: "#333",
            tabClr: "#eee",
            ratingStars: false,
            showAvatars: true,
            writeButton: '.($writeButton == '1'? 'true' : 'false').'
            });
            </script>
            ';

			if (Configuration::get('REVIEWSCOUK_CONFIG_PRODUCT_RICH_SNIPPET') == '1'){
				$data .= $this->getRichSnippetCode($product_sku);
			}

			$smarty = $this->context->smarty;
			$smarty->assign(array('data' => $data));

			return $this->display(__FILE__, 'views/templates/front/widget.tpl');
		}
	}

    protected function prepareOrderData($params){
        $order_id = $params['id_order'];
        $order = new Order((int)$params['id_order']);
        $id_customer = $order->id_customer;
        $customer = new Customer((int)$id_customer);
        $first_name = $customer->firstname;
        $last_name = $customer->lastname;
        $email = $customer->email;

        $products = $order->getProducts();
        $products_array = array();

        foreach ($products as $product)
        {

			$product_id = $product['product_id'];

			$image = Image::getCover($product_id);
			$link = new Link;
			$prod = new Product($product_id, false, Context::getContext()->language->id);
			$imagePath = $link->getImageLink($prod->link_rewrite, $image['id_image'], 'home_default');

            $product_item = array(
                'name' => $product['product_name'],
                'sku' => $product['product_id'],
                'link' => $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/index.php?controller=product&id_product='.$product['product_id'],
                'image' => $_SERVER['REQUEST_SCHEME'].'://'.$imagePath
            );

            $products_array[] = $product_item;
        }

        $orderData = array(
            'name' => $first_name.' '.$last_name,
            'email' => $email,
            'order_id' => $order_id,
            'products' => $products_array
        );

        return $orderData;
    }

	public function hookpostUpdateOrderStatus($params)
	{
		if ($params['newOrderStatus']->shipped == '1')
		{
			if ((Configuration::get('REVIEWSCOUK_CONFIG_AUTO_MERCHANT') == '1') || (Configuration::get('REVIEWSCOUK_CONFIG_AUTO_PRODUCT') == '1')){

                $orderData = $this->prepareOrderData($params);

				if (Configuration::get('REVIEWSCOUK_CONFIG_AUTO_MERCHANT') == '1')
				{
                    $this->apiPostRequest('merchant/invitation', $orderData);
				}

				if (Configuration::get('REVIEWSCOUK_CONFIG_AUTO_PRODUCT') == '1')
				{
                    $this->apiPostRequest('product/invitation', $orderData);
				}
			}
		}
	}

    protected function apiHeaders(){
        return array(
            'Content-Type: application/json',
            'store: '.Configuration::get('REVIEWSCOUK_CONFIG_STOREID'),
            'apikey: '.Configuration::get('REVIEWSCOUK_CONFIG_APIKEY')
        );
    }

    protected function createSubDomain($subdomain){
        $region = Configuration::get('REVIEWSCOUK_CONFIG_REGION');
        return ($region == 2)? 'https://'.$subdomain.'.reviews.io/' : 'https://'.$subdomain.'.reviews.co.uk/';
    }

    protected function apiDomain(){
        return $this->createSubDomain('api');
    }

    protected function widgetDomain(){
        return $this->createSubDomain('widget');
    }

    protected function apiPostRequest($url, $postData){
        $ch = curl_init($this->apiDomain().$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->apiHeaders());
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        $data = curl_exec($ch);
        curl_close($ch);
    }
}
