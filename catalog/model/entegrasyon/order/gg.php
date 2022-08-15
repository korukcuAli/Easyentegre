<?php

class ModelEntegrasyonOrderGg extends Model
{

    public function getOrders($debug = false)
    {
        $this->load->model('entegrasyon/general');

        $orders = array();

        $post_data['request_data'] = array(true, "S");
        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('gg');

        $order_list = $this->entegrasyon->clientConnect($post_data, 'get_orders', 'gg', $debug);



        if ($order_list['status']) {


            if ($order_list['result']['item_count'] > 0) {

                foreach ($order_list['result']['orders'] as $order) {




                    $checkOrder = $this->entegrasyon->checkOrderByMarketPlaceOrderId($order['saleCode']);


                    if (!$checkOrder) {
                        $orders[] = $this->getOrder($order);
                    }

                }

            }


        }


        return $orders;

    }


    public function getOrder($result)

    {



        $customer = $result['buyerInfo'];

        $first_name = $customer['name'];
        $lastname = $customer['surname'];
        $adres = isset($customer['address']) ? $customer['address'] : '';
        $sehir = isset($customer['city']) ? $customer['city'] : '' . $customer['district'];
        $postakodu = isset($customer['zipCode']) ? $customer['zipCode'] : '';
        $phone = str_replace(' ', '', $customer['phone']);
        $ilce = $customer['district'];
        $saleCode = $result['saleCode'];
        $statusCode = $result['statusCode'];
        $model = $result['model'];
        $address_data = array();
        if($this->config->get('gg_setting_add_tc')){

            $adres=$adres.'Vergi/Kimlik No:';

        }

        $address_data = array(

            'firstname' => $first_name,
            'lastname' => $lastname,
            'tax_office' => '',
            'tax_id' => '',
            'company' => '',
            'address_1' => $adres,
            'address_2' => '',
            'city' => $sehir,
            'town' => $ilce,

            'postcode' => $postakodu,
            'country_id' => 215,
            'zone_id' => ''

        );


        $order_data = array(
            'firstname' => $first_name,
            'order_id' => $saleCode,
            'lastname' => $lastname,
            'tckimlikno' => '',
            'customer_group_id' => $this->config->get('config_customer_group_id'),
            'email' => '',
            'telephone' => $phone,
            'fax' => '',
            'custom_field' => '',
            'newsletter' => '',
            'password' => '123456',
            'status' => 1,
            'approved' => 1,
            'safe' => 1,
            'address' => $address_data,
            'order_type' => 'gg',
            'plength' => '',
            'poption' => '',


        );


        $order_data['invoice_prefix'] = $saleCode;

        $order_data['order_date'] = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $result['moneyDate'])));

        $order_data['comment'] = '';//$this->config->get('n11_order_comment');

        $order_data['language_id'] = $this->config->get('config_language_id');
        $order_data['currency_id'] = 1;
        $order_data['currency_code'] = $this->session->data['currency'];
        $order_data['currency_value'] = 1;//$this->currency->getValue($this->session->data['currency']);
        $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
            $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
        } else {
            $order_data['forwarded_ip'] = '';
        }

        if (isset($this->request->server['HTTP_USER_AGENT'])) {
            $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
        } else {
            $order_data['user_agent'] = '';
        }

        if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
            $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $order_data['accept_language'] = '';
        }
        $shipment_info=$this->getShipmenInfo($result);

        $order_data['shipping_info']=$shipment_info;


        $shipping_address = array(
            'shipping_firstname' => $first_name,
            'shipping_lastname' => $lastname,
            'shipping_company' => '',
            'shipping_address_1' => $adres . ' ' . $ilce . '/' . $sehir,
            'shipping_address_2' => '',
            'shipping_city' => $sehir,
            'shipping_town' => $ilce,
            'shipping_postcode' => $postakodu,
            'shipping_country_id' => 215,
            'shipping_zone_id' => '',
            'shipping_method' => $shipment_info['shipment_method'].'-'.'Gittigidiyor Kampanya no:'.$shipment_info['campaign_number'],
            'shipping_zone' => $sehir,
            'shipping_country' => 'Türkiye',
            'shipping_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'shipping_custom_field	' => 'Kampanya no:'.$shipment_info['campaign_number'],

            'shipping_code' => $shipment_info['shipment_method'],

        );

        $payment_address = array(

            'payment_firstname' => $first_name,
            'payment_lastname' => $lastname,
            'payment_company' => '',
            'payment_address_1' => $adres . ' ' . $ilce . '/' . $sehir,
            'payment_address_2' => '',
            'payment_city' => $sehir,
            'payment_town' => $ilce,
            'payment_postcode' => $postakodu,
            'payment_country_id' => 215,
            'payment_country' => 'Türkiye',
            'payment_zone_id' => '',
            'payment_code' => 'Gittigidiyor',
            'payment_method' => 'Gittigidiyor',
            'payment_zone' => $sehir,
            'payment_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'payment_custom_field'=>''

        );

        if($this->config->get('gg_setting_add_tc')){
            $payment_address['payment_custom_field'] = 'Vergi No';
        }

        $order_data['payment_info']=array('vergi_dairesi'=>'','vergi_yada_kimlik_no'=>'');

        $order_data = array_merge($order_data, $payment_address);
        $order_data = array_merge($order_data, $shipping_address);
        // $order_data['payment_address']=$payment_address;
        // $order_data['shipping_address']=$shipping_address;
        $kdv= 18;

        $order_data['affiliate_id'] = '';
        $order_data['commission'] = '';
        $order_data['marketing_id'] = '';
        $order_data['tracking'] = '';
        $order_data['order_status_id'] = $statusCode;
        $order_data['products'] = array();
        $order_data['total'] = 0;
        $order_data['payment_company_id'] = '';
        $order_data['payment_tax_id'] = '11111111111';

        $total = 0;
        $tax = 0;
        $subtotal = 0;

        $price = $result['price'];

        $totals = array();

        $tax +=0; //((float)number_format(($result['price'] / ((100+$kdv)/100) * ($kdv/100)),2));


        $subtotal += (float)$result['price'];


        $total += (float)$result['price'];

        $option_data = array();
        $order_data['totals'] = $totals;

        $order_data['products'][] = $this->getProductInfo($result);

        $order_data['total'] = $price;


        $price = 2;

        $totals = array();




        $totals[] = array(

            'code' => 'sub_total',
            'title' => 'Ara Toplam',
            'value' => $subtotal,
            'sort_order' => 1
        );


        /*   $totals[] = array(

               'code' => 'sub_total',
               'title' => 'Kargo Ücreti',
               'value' => 0,
               'sort_order' => 3
           );
        */

        $totals[] = array(

            'code' => 'total',
            'title' => 'Toplam',
            'value' => $result['price'],
            'sort_order' => 9
        );


        $order_data['totals'] = $totals;



        $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
        $order_data['invoice_no'] = 0;
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');

        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            if ($this->request->server['HTTPS']) {
                $order_data['store_url'] = HTTPS_SERVER;
            } else {
                $order_data['store_url'] = HTTP_SERVER;
            }
        }

        $order_data['totals'] = $totals;

        $custom_data = array();
        // $custom_data[2] = $result->orderDetail->buyer->taxId ? $result->orderDetail->buyer->taxId : '';
        //$custom_data[1] = $result->orderDetail->buyer->taxOffice ? $result->orderDetail->buyer->taxOffice : '';
        //$custom_data[3] = $result->orderDetail->buyer->tcId ? $result->orderDetail->buyer->tcId : '';


        // $order_data['payment_custom_field'] = $custom_data;
        //$order_data['shipping_custom_field'] = $custom_data;
        //$order_data['custom_field'] = $custom_data;

        return $order_data;

    }


    private function getProductInfo($product)
    {
        $product_info = $this->entegrasyon->getProductByOrderModel($product['model'], $product['model'], $product['model'], 'gg');
        //$kdv= 18;

        if($product_info['product']){
            $model=$product_info['product']['model'];
        }else {
            $model=$product['model'];
        }

        $price=(float)$this->entegrasyon->priceFormat($product['price']);
        $product_data = array(

            'item_id' => $product['model'],
            'product_id' => $product_info['product'] ? $product_info['product']['product_id'] : 0,
            'variant_id' => $product_info['variant_id'],
            'name' => $product['productTitle'],
            'model' => $model,


            'option' => $this->getOrderAtrributes($product),
            'download' => '',
            'quantity' => $product['amount'],
            'subtract' => '',
            'shipment_info' => $this->getShipmenInfo($product),
            'list_price' => (float)($price),
            'price' => (float)($price),
            'total' => (float)($price*$product['amount']),
            'tax' => '',
            'totaltax' => '',
            'discount' => '',
            'reward' => ''
        );


        if ($product['variant']) {

            $get_variant_info = $this->entegrasyon->getVariantByModel('gg',$product['variant'],$product['variant']);

            if($get_variant_info){

                $product_data['product_id']=$get_variant_info['product_id'];
                $product_data['variant_id']=$get_variant_info['variant_id'];
                $variant_infos=explode('|',$get_variant_info['variant_info']);

                $options=array();
                foreach ($variant_infos as $variant_info) {
                    $variant_data=explode('+-',$variant_info);
                    $option_info=$this->entegrasyon->getProductOptionInfoByProductIdAndOptionValueId($get_variant_info['product_id'],  $variant_data[3]);

                    if($option_info){

                        $options[]=array('product_option_id'=>$option_info['option_value_id'],'product_option_value_id'=>$option_info['product_option_value_id'],'name'=>$option_info['option_name'],'value'=>$option_info['value']);
                        //  $this->entegrasyon->updateProductOptionStock2($option_info['product_option_value_id'],$product['quantity']);

                    }

                }


                $product_data['option']=$options;
                //$options=$this->getProductOptionValue($get_variant_info['product_id'], $product_option_value_id)


            }


        }

        return $product_data;

    }

    private function getOrderAtrributes($attributes)
    {

        $options = array();


        return $options;


    }


    private function getShipmenInfo($item)
    {




        return array(

            'shipping_code' => $item['saleCode'] ? $item['saleCode'] : '',
            'campaign_number' => $item['saleCode'] ? $item['saleCode'] : '',
            'shipment_method' => $this->config->get('gg_setting_shipping_company')
        );

    }

    public function updateStock($order)
    {
        $status = true;

        foreach ($order['products'] as $orderedproduct) {

            $model = $orderedproduct['model'];
            $product_info = $this->entegrasyon->getProductByOrderModel($model, 'gg');


            $message = '';
            if ($product_info) {

                $product_info['quantity'] -= (int)$orderedproduct['quantity'];
                //  echo 'Şimdiki Stok'.$product_info['quantity'].'<br>';

                $this->entegrasyon->updateStock($product_info);

                $message = 'Seçeneksiz olan ürününüz bulundu ve stoğu güncellendi!';
                $status = true;

            }

        }
        return array('status' => $status, 'message' => $message);

    }
}





