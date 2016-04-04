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

	public function __construct()
	{
		$this->name = 'reviewscouk';
		$this->tab = 'others';
		$this->version = '1.2.3';
		$this->author = 'Reviews.co.uk Integrations';
		$this->module_key = '7f216a86f806f343c2888324f3504ecf';
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
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

		if (!parent::install() || !$this->registerHook('productfooter') || !$this->registerHook('postUpdateOrderStatus')){
			return false;
        } else {
			return true;
        }     
	}

	public function uninstall()
	{
		Configuration::deleteByName('REVIEWSCOUK_CONFIG_REGION');
		Configuration::deleteByName('REVIEWSCOUK_CONFIG_STOREID');
		Configuration::deleteByName('REVIEWSCOUK_CONFIG_APIKEY');
		Configuration::deleteByName('REVIEWSCOUK_CONFIG_AUTO_MERCHANT');
		Configuration::deleteByName('REVIEWSCOUK_CONFIG_AUTO_PRODUCT');
		Configuration::deleteByName('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET');
		Configuration::deleteByName('REVIEWSCOUK_CONFIG_WIDGET_COLOR');

		return parent::uninstall();
	}

	public function getContent()
	{
		$output = null;

		if (Tools::isSubmit('submit'.$this->name))
		{
		    $reviews_config_region                              = (string)Tools::getValue('REVIEWSCOUK_CONFIG_REGION');
			$reviews_config_storeid 							= (string)Tools::getValue('REVIEWSCOUK_CONFIG_STOREID');
			$reviews_config_apikey								= (string)Tools::getValue('REVIEWSCOUK_CONFIG_APIKEY');
			$reviews_config_display_product_widget				= (string)Tools::getValue('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET');
			$reviews_config_auto_merchant						= (string)Tools::getValue('REVIEWSCOUK_CONFIG_AUTO_MERCHANT');
			$reviews_config_auto_product						= (string)Tools::getValue('REVIEWSCOUK_CONFIG_AUTO_PRODUCT');
		    $reviews_config_widget_color                        = (string)Tools::getValue('REVIEWSCOUK_CONFIG_WIDGET_COLOR');

			if (!$reviews_config_storeid || empty($reviews_config_storeid))
			{
				$output .= $this->displayError($this->l('Please enter your Store ID.'));
				Configuration::updateValue('REVIEWSCOUK_CONFIG_STOREID', $reviews_config_storeid);
			}
			else
			{
				if (Configuration::get('REVIEWSCOUK_CONFIG_STOREID') != $reviews_config_storeid)
				{
					Configuration::updateValue('REVIEWSCOUK_CONFIG_STOREID', $reviews_config_storeid);
					$output .= $this->displayConfirmation($this->l('Store ID set'));
				}
			}

			if (!$reviews_config_apikey || empty($reviews_config_apikey))
			{
				$output .= $this->displayError($this->l('Please enter your API Key'));
				Configuration::updateValue('REVIEWSCOUK_CONFIG_APIKEY', $reviews_config_apikey);
			}
			else
			{
				if (Configuration::get('REVIEWSCOUK_CONFIG_APIKEY') != $reviews_config_apikey)
				{
					Configuration::updateValue('REVIEWSCOUK_CONFIG_APIKEY', $reviews_config_apikey);
					$output .= $this->displayConfirmation($this->l('API Key set'));
				}
			}

			if (!empty($reviews_config_widget_color))
			{
				if (Configuration::get('REVIEWSCOUK_CONFIG_WIDGET_COLOR') != $reviews_config_apikey)
				{
					Configuration::updateValue('REVIEWSCOUK_CONFIG_WIDGET_COLOR', $reviews_config_widget_color);
					$output .= $this->displayConfirmation($this->l('Widget Color Updated'));
				}
			}

			if (!empty($reviews_config_region))
			{
				if (Configuration::get('REVIEWSCOUK_CONFIG_REGION') != $reviews_config_region)
				{
					Configuration::updateValue('REVIEWSCOUK_CONFIG_REGION', $reviews_config_region);
					$output .= $this->displayConfirmation($this->l('Updated Region'));
				}
			}

			if ($reviews_config_display_product_widget || !empty($reviews_config_display_product_widget))
			{
				if (Configuration::get('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET') != $reviews_config_display_product_widget)
				{
					Configuration::updateValue('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET', $reviews_config_display_product_widget);
					$letter = $this->letterGetter($reviews_config_display_product_widget);
					$output .= $this->displayConfirmation($this->l('Product widget will no'.$letter.' be displayed.'));
				}
			}

			if ($reviews_config_auto_merchant || !empty($reviews_config_auto_merchant))
			{
				if (Configuration::get('REVIEWSCOUK_CONFIG_AUTO_MERCHANT') != $reviews_config_auto_merchant)
				{
					Configuration::updateValue('REVIEWSCOUK_CONFIG_AUTO_MERCHANT', $reviews_config_auto_merchant);
					$letter = $this->letterGetter($reviews_config_auto_merchant);
					$output .= $this->displayConfirmation($this->l('Merchant Review requests will no'.$letter.' be queued automatically.'));
				}
			}

			if ($reviews_config_auto_product || !empty($reviews_config_auto_product))
			{
				if (Configuration::get('REVIEWSCOUK_CONFIG_AUTO_PRODUCT') != $reviews_config_auto_product)
				{
					$letter = $this->letterGetter($reviews_config_auto_product);
					Configuration::updateValue('REVIEWSCOUK_CONFIG_AUTO_PRODUCT', $reviews_config_auto_product);
					$output .= $this->displayConfirmation($this->l('Product Review requests will no'.$letter.' be queued automatically.'));
				}
			}
		}

		return $output.$this->displayForm();
	}

	private function letterGetter($val_to_switch)
	{
		switch ($val_to_switch)
		{
			case '2':
				$letter = 't';
				break;
			case '1':
				$letter = 'w';
				break;
			default:
				$letter = 'rris';
				break;
		}
		return $letter;
	}

	public function displayForm()
	{
		$yes_no_options = array(array('id_option' => 1, 'name' => 'Yes'), array('id_option' => 2,	'name' => 'No'));
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


						array('type' => 'select',
								'label' => $this->l('Automatic Merchant Review Requests:'),
								'name' => 'REVIEWSCOUK_CONFIG_AUTO_MERCHANT',
								'required' => true,
								'options' => array(
										'query' => $yes_no_options,
										'id' => 'id_option',
										'name' => 'name'
								)
						),

						array(
								'type' => 'select',
								'label' => $this->l('Automatic Product Review Requests:'),
								'name' => 'REVIEWSCOUK_CONFIG_AUTO_PRODUCT',
								'required' => true,
								'options' => array(
										'query' => $yes_no_options,
										'id' => 'id_option',
										'name' => 'name'
								)
                        ),

						array(
								'type' => 'select',
								'label' => $this->l('Display the product reviews widget:'),
								'name' => 'REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET',
								'required' => true,
								'options' => array(
									'query' => $yes_no_options,
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

		$helper->fields_value['REVIEWSCOUK_CONFIG_REGION'] = Configuration::get('REVIEWSCOUK_CONFIG_REGION');
		$helper->fields_value['REVIEWSCOUK_CONFIG_STOREID'] = Configuration::get('REVIEWSCOUK_CONFIG_STOREID');
		$helper->fields_value['REVIEWSCOUK_CONFIG_APIKEY'] = Configuration::get('REVIEWSCOUK_CONFIG_APIKEY');
		$helper->fields_value['REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET'] = Configuration::get('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET');
		$helper->fields_value['REVIEWSCOUK_CONFIG_AUTO_MERCHANT'] = Configuration::get('REVIEWSCOUK_CONFIG_AUTO_MERCHANT');
		$helper->fields_value['REVIEWSCOUK_CONFIG_AUTO_PRODUCT'] = Configuration::get('REVIEWSCOUK_CONFIG_AUTO_PRODUCT');
		$helper->fields_value['REVIEWSCOUK_CONFIG_WIDGET_COLOR'] = Configuration::get('REVIEWSCOUK_CONFIG_WIDGET_COLOR');

		return $helper->generateForm($fields_form);
	}

    protected function isColor($color){
       return preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/i', $color);
    }

	public function hookproductfooter($params)
	{
		if (Configuration::get('REVIEWSCOUK_CONFIG_DISPLAY_PRODUCT_WIDGET') == '1')
		{
			$product_sku = $params['product']->id;
			$store_id = Configuration::get('REVIEWSCOUK_CONFIG_STOREID');
			$color = Configuration::get('REVIEWSCOUK_CONFIG_WIDGET_COLOR');

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
            writeButton: true 
            });
            </script>
            ';

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

        $products = $params['cart']->getProducts(true);
        $products_array = array();

        foreach ($products as $product)
        {

            $id_image = $product['id_image'];
            $image = new Image($id_image);

            $product_item = array(
                'name' => $product['name'],
                'sku' => $product['id_product'],
                'link' => 'http://'.$_SERVER['SERVER_NAME'].'/index.php?controller=product&id_product='.$id_product,
                'image' => _PS_BASE_URL_._THEME_PROD_DIR_.$image->getExistingImgPath().'.jpg'
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
