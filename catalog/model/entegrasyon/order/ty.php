<?php

class ModelEntegrasyonOrderTy extends Model
{

    public function getOrders($debug=false)
    {


        $orderList = array();

        $post_data['request_data'] = 'Created';

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('ty');

        $orders = $this->entegrasyon->clientConnect($post_data, 'get_orders', 'ty', $debug);


        if ($orders['status'] && isset($orders['result']['content'])) {


            foreach ($orders['result']['content'] as $order) {


                if(!$this->entegrasyon->checkOrderByMarketPlaceOrderId($order['orderNumber']) && $order['shipmentPackageStatus']!='Cancelled' ){


                    $orderList[] = $this->getOrder($order);


                }
            }
        }

        return $orderList;

    }

    public function getProductFromMarketPlace($model)
    {
        $this->load->model('entegrasyon/general');
        $post_data['request_data']=array('itemcount'=>1,'page'=>1,'barcode'=>$model,'approved'=>true);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('ty');
        return $this->entegrasyon->clientConnect($post_data,'get_product','ty',false);
    }


    public function getOrder($order)

    {





        $this->load->model('entegrasyon/general');
        $custom_data = $order['shipmentAddress']['fullName'];
        $first_name = $order['shipmentAddress']['firstName'];
        $lastname = $order['shipmentAddress']['lastName'];
        $adres = $order['shipmentAddress']['fullAddress'];
        $sehir = $order['shipmentAddress']['city'];
        $ilce = $order['shipmentAddress']['district'];
        $postakodu = '';
        $phone = '';



        $vergidairesi='';
        $vergiNo=isset($order['taxNumber'])?$order['taxNumber']:$order['tcIdentityNumber'];

        if($this->config->get('ty_setting_add_tc')){

            $adres=$adres.'Vergi/Kimlik No:'.$vergiNo;

        }

        $address_data = array(

            'firstname' => $first_name,
            'lastname' => $lastname,
            'tax_office'=>$vergidairesi,
            'tax_id'=>$vergiNo,
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
            'lastname' => $lastname,
            'tckimlikno' => '',
            'customer_group_id' => $this->config->get('config_customer_group_id'),
            'email' => $order['customerEmail'],
            'telephone' => $phone,
            'fax' => '',
            'custom_field' => '',
            'newsletter' => '',
            'password' => '123456',
            'status' => 1,
            'approved' => 1,
            'safe' => 1,
            'address' => $address_data,
            'order_type' => 'ty',
            'plength' => '',
            'poption' => '',


        );


        $order_data['order_id'] = $order['orderNumber'];
        $order_data['invoice_prefix'] = '';
        $order_data['invoice_no'] = 0;
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');

        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            if ($this->request->server['HTTPS']) {
                $order_data['store_url'] = HTTP_SERVER;
            } else {
                $order_data['store_url'] = HTTP_SERVER;
            }
        }


        $order_data['comment'] = $this->config->get('n11_order_comment');
        $order_data['payment_company_id'] = $vergidairesi;
        $order_data['payment_tax_id'] = $vergiNo;
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



        $shipment_info=$this->getShipmenInfo($order);

        $order_data['shipping_info']=$shipment_info;

        $shipping_address = array(
            'shipping_firstname' => $first_name,
            'shipping_lastname' => $lastname,
            'shipping_company' => '',
            'shipping_address_1' => $adres,
            'shipping_address_2' => '',
            'shipping_city' => $sehir,
            'shipping_town' => $ilce,
            'shipping_postcode' => $postakodu,
            'shipping_country_id' => 215,
            'shipping_zone_id' => '',
            'shipping_zone' => $sehir,
            'shipping_country' => 'Türkiye',
            'shipping_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'shipping_custom_field' =>$shipment_info,
            'shipping_code' => $shipment_info['shipment_method'],

        );

        $shipment_method='Trendyol';
        if($shipment_info['campaign_number'])$shipment_method.=' - Kampanya No:'.$shipment_info['campaign_number'];
        if($shipment_info['shipping_code'])$shipment_method.=' - Kargo No:'.$shipment_info['shipping_code'];
        if($shipment_info['shipment_method'])$shipment_method.=' - Kargo Metodu:'.$shipment_info['shipment_method'];
        $shipping_address['shipping_method']=$shipment_method;


        $payment_address = array(

            'payment_firstname' => $order['invoiceAddress']['firstName'],
            'payment_lastname' => $order['invoiceAddress']['lastName'],
            'payment_company' => '',
            'payment_address_1' => $order['invoiceAddress']['fullAddress'],
            'payment_address_2' => '',
            'payment_city' => $order['invoiceAddress']['city'],
            'payment_town' => $order['invoiceAddress']['district'],
            'payment_postcode' => $postakodu,
            'payment_country_id' => 215,
            'payment_country' => 'Türkiye',
            'payment_zone_id' => '',
            'payment_code' => 'Trendyol',
            'payment_zone' => $sehir,
            'payment_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',

            'payment_custom_field'=>array('Vergi Dairesi'=>$vergidairesi,'Vergi/TC Kimlik'=>$vergiNo)

        );



        $payment_method='Trendyol';
        if($vergidairesi)$payment_method.='-Vergi Dairesi'.$vergidairesi;
        if($vergiNo)$payment_method.='-Vergi/TC Kimlik:'.$vergiNo;
        $payment_address['payment_method']=$payment_method;


        $order_data['order_date'] = date('Y-m-d H:i:s', substr($order['orderDate'], 0, 10));

        $order_data = array_merge($order_data, $payment_address);
        $order_data = array_merge($order_data, $shipping_address);

        $order_data['affiliate_id'] = '';
        $order_data['commission'] = '';
        $order_data['marketing_id'] = '';
        $order_data['tracking'] = '';
        $order_data['custom_field']=array('fatura_bilgileri'=>array('vergi_dairesi'=>$vergidairesi,'vergi_yada_kimlik_no'=>$vergiNo),'kargo_bilgileri'=>$shipment_info);
        $order_data['order_status_id'] = $order['shipmentPackageStatus'];

        $order_data['products'] = array();
        $order_data['payment_info']=array('vergi_dairesi'=>$vergidairesi,'vergi_yada_kimlik_no'=>$vergiNo);

        $order_data['total'] = $order['totalPrice'];


        $total = 0;
        $tax = 0;
        $subtotal = 0;


        $totals = array();

        foreach ($order['lines'] as $product) {
            $order_data['products'][] = $this->getProductInfo($product, $order);

        }


        foreach ($order_data['products'] as $product) {
            $tax += (float)$product['totaltax'];
            $subtotal += (float)$product['total'];
            $total += (float)$product['price'];
            if($this->config->get('easy_setting_order_price_with_tax')){
                //$order_data['total'] +=(float)$product['price'];

            }

        }



        if (!$this->config->get('easy_setting_order_price_with_tax')){

            $totals[] = array(

                'code' => 'tax',
                'title' => 'KDV',
                'value' => (float)$tax,
                'sort_order' => 5
            );


        }

        $totals[] = array(

            'code' => 'sub_total',
            'title' => 'Ara Toplam (KDV HARİÇ)',
            'value' => $subtotal,
            'sort_order' => 1
        );





        $totals[] = array(

            'code' => 'total',
            'title' => 'Toplam',
            'value' => $order['totalPrice'],
            'sort_order' => 9
        );


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


    private function getProductInfo($product, $order)
    {
        //print_r($product);return;
        // $product_info = $this->entegrasyon->getProductByMarketId($product['productCode'],'ty');
        //if(!$product_info)
        if (!isset($product['merchantSku'])){
            $product['merchantSku']=$product['barcode'];
        }
        $product_info = $this->entegrasyon->getProductByOrderModel($product['barcode'],$product['merchantSku'],$product['productName'],'ty');

        $kdv= $product['vatBaseAmount'];


        $price=($product['price'] / ((100+$kdv)/100));
        $tax=(($product['price']-$price));

        if($product_info['product']){
            $model=$product_info['product']['model'];
        }else {
            $model=$product['merchantSku'];
        }

        $product_data = array(
            'item_id' => '',
            'product_id'=>$product_info['product'] ?$product_info['product']['product_id']:0,
            'variant_id'=>$product_info['variant_id'],
            'name' => $product['productName'],
            'model' => $model,
            'option' => array(),
            'download' => '',
            'quantity' => $product['quantity'],
            'subtract' => '',
            'shipment_info' => $this->getShipmenInfo($order),
            'list_price' => $this->entegrasyon->priceFormat($product['amount']),
            'price' => $this->config->get('easy_setting_order_price_with_tax')?$product['price']:$this->entegrasyon->priceFormat($price),
            'total' => $this->config->get('easy_setting_order_price_with_tax')?$this->entegrasyon->priceFormat($product['price']*$product['quantity']):$this->entegrasyon->priceFormat($price*$product['quantity']),
            'tax' =>$this->entegrasyon->priceFormat($tax ),
            'totaltax' =>$this->entegrasyon->priceFormat($tax * $product['quantity']),
            'discount' => $this->entegrasyon->priceFormat($product['discount']),
            'reward' => ''
        );

        $get_variant_info = $this->entegrasyon->getVariantByModel('ty',$product['merchantSku'],$product['barcode'],$product_data['model']);
        if (!$get_variant_info){

            $product['merchantSku'] = substr($product['merchantSku'], strlen($this->config->get('ty_setting_model_prefix')));
            $product['barcode'] = substr($product['barcode'], strlen($this->config->get('ty_setting_model_prefix')));
            $product['model'] = isset($product['model'])?substr($product['model'], strlen($this->config->get('ty_setting_model_prefix'))):"";

            $get_variant_info = $this->entegrasyon->getVariantByModel('ty',$product['merchantSku'],$product['barcode'],$product_data['model']);


        }

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

                }

            }

            $product_data['option']=$options;

        }

        return $product_data;

    }



    private function getShipmenInfo($order)
    {


        return array(

            'shipping_code' => $order['cargoTrackingNumber'],
            'campaign_number' => $order['cargoTrackingNumber'],
            'shipment_method' => $order['cargoProviderName']
        );

    }



}





