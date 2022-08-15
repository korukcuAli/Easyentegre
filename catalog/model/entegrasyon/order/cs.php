<?php

class ModelEntegrasyonOrderCs extends Model
{

    public function getOrders($debug=false)
    {

        $orderList = array();

        $post_data['request_data']['start'] =date("Y-m-d H:i:s", strtotime("+1 day"));
        $post_data['request_data']['end'] =date("Y-m-d H:i:s", strtotime("-10 day"));

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('cs');

        $orders = $this->entegrasyon->clientConnect($post_data, 'get_orders', 'cs', $debug);



        if ($orders['status']) {


            foreach ($orders['result']['supplierOrderListWithBranch'] as $order) {
                if(!$this->entegrasyon->checkOrderByMarketPlaceOrderId($order['orderId'])){
                    $order=$this->getOrder($order);
                    if(!$this->findOrderFromOrders( $orderList,$order)){
                        $orderList[] = $order ;
                    }else {

                        $orderList=$this->findOrderFromOrders( $orderList,$order);

                    }



                }
            }
        }


        return $orderList;

    }

    public function findOrderFromOrders($orders,$order)
    {



        $result=false;

        foreach ($orders as $index=>$item) {

            if($item['order_id']==$order['order_id']){

                $item['products'][]=$order['products'][0];

                $item['total'] =$item['total'] +$order['total'];
                $item['totals'][0]['value']= $item['totals'][0]['value']+$order['totals'][0]['value'];
                $item['totals'][1]['value']= $item['totals'][1]['value']+$order['totals'][1]['value'];
                $item['totals'][2]['value']= $item['totals'][2]['value']+$order['totals'][2]['value'];

                $result=true;

                //unset($orders[$index]);
                $orders[$index]=$item;

            }

        }



        if($result){
            return $orders;
        }else {
            return false;
        }

    }

    public function getProductFromMarketPlace($model)
    {
        $this->load->model('entegrasyon/general');

        $post_data['request_data']=array('itemcount'=>1,'page'=>1,'barcode'=>$model,'approved'=>true);

        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('cs');
        $result=$this->entegrasyon->clientConnect($post_data,'get_product','cs',false);

        return $result;
    }


    public function getOrder($order)

    {



        $this->load->model('entegrasyon/general');
        $custom_data = $order['receiverName'];
        $first_name = $order['receiverName'];
        $lastname = '';
        $adres = $order['receiverAddress'];
        $sehir = $order['receiverCity'];
        $ilce = $order['receiverRegion'];
        $phone =$order['receiverPhone'];
        $postakodu = '';
        $email = $order['invoiceEmail'];
        $vergiNo=isset($order['senderTaxNumber'])?$order['senderTaxNumber']:'11111111111';
        $vergiDairesi=$order['senderTaxOfficeName'];

        if($this->config->get('cs_setting_add_tc')){

            $adres=$adres.'Vergi/Kimlik No:'.$vergiNo;

        }

        $address_data = array(

            'firstname' => $first_name,
            'lastname' => $lastname,
            'company' => '',
            'tax_office'=>'',
            'tax_id'=>'',
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
            'email' => $email,
            'telephone' => $phone,
            'fax' => '',
            'custom_field' => '',
            'newsletter' => '',
            'password' => '123456',
            'status' => 1,
            'approved' => 1,
            'safe' => 1,
            'address' => $address_data,
            'order_type' => 'cs',
            'plength' => '',
            'poption' => '',


        );


        $order_data['order_id'] = $order['orderId'];
        $order_data['invoice_prefix'] = '';
        $order_data['payment_company_id'] = $vergiDairesi;
        $order_data['payment_tax_id'] = $vergiNo;
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
            'shipping_method' => $order['cargoCompany'],
            'shipping_zone' => $sehir,
            'shipping_country' => 'Türkiye',
            'shipping_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'shipping_custom_field	' => '',

            'shipping_custom_field' =>$shipment_info,
            'shipping_code' => $shipment_info['shipment_method'],


        );

        $shipment_method='Ciceksepeti';
        if($shipment_info['campaign_number'])$shipment_method.=' - Kampanya No:'.$shipment_info['campaign_number'];
        if($shipment_info['shipping_code'])$shipment_method.=' - Kargo No:'.$shipment_info['shipping_code'];
        if($shipment_info['shipment_method'])$shipment_method.=' - Kargo Metodu:'.$shipment_info['shipment_method'];
        $shipping_address['shipping_method']=$shipment_method;

        $payment_address = array(

            'payment_firstname' => $first_name,
            'payment_lastname' => '',
            'payment_company' => '',
            'payment_address_1' => $adres,
            'payment_address_2' => '',
            'payment_city' => $sehir,
            'payment_town' => $ilce,
            'payment_postcode' => $postakodu,
            'payment_country_id' => 215,
            'payment_country' => 'Türkiye',
            'payment_zone_id' => '',
            'payment_code' => 'Çiçeksepeti',
            'payment_zone' => $sehir,
            'payment_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'payment_custom_field'=>array('Vergi Dairesi'=>$vergiDairesi,'Vergi/TC Kimlik'=>$vergiNo)

        );

        $payment_method='Çiçeksepeti';
        if($vergiDairesi)$payment_method.='-Vergi Dairesi'.$vergiDairesi;
        if($vergiNo)$payment_method.='-Vergi/TC Kimlik:'.$vergiNo;
        $payment_address['payment_method']=$payment_method;


        $date=str_replace('/','-',$order['orderCreateDate']);

        $date=date_create($date);

        $order_data['order_date'] = date_format($date,'Y-m-d H:i:s');

        $order_data = array_merge($order_data, $payment_address);
        $order_data = array_merge($order_data, $shipping_address);
        // $order_data['payment_address']=$payment_address;
        // $order_data['shipping_address']=$shipping_address;

        $order_data['affiliate_id'] = '';
        $order_data['commission'] = '';
        $order_data['marketing_id'] = '';
        $order_data['tracking'] = '';
        $order_data['custom_field']=array('fatura_bilgileri'=>array('vergi_dairesi'=>$vergiDairesi,'vergi_yada_kimlik_no'=>$vergiNo),'kargo_bilgileri'=>$shipment_info);

        $order_data['payment_info']=array('vergi_dairesi'=>$vergiDairesi,'vergi_yada_kimlik_no'=>$vergiNo);

        $order_data['order_status_id'] = $order['orderProductStatus'];

        $order_data['products'] = array();

        $order_data['total'] = 0;

        $total = 0;
        $tax = 0;
        $subtotal = 0;



        $kdv= $order['tax'];



        $totals = array();

        $tax += ((float)$this->entegrasyon->priceFormat(($order['totalPrice'] / ((100+$kdv)/100) * ($kdv/100))));
        $subtotal += (float)$this->entegrasyon->priceFormat($order['totalPrice'] / ((100+$kdv)/100));



        $total += (float)$order['totalPrice'];


        $order_data['totals'] = $totals;

        $order_data['products'][] = $this->getProductInfo($order);

        $order_data['total'] = $order['invoicePrice'];





        $totals = array();
        if (!$this->config->get('easy_setting_order_price_with_tax')) {

            $totals[] = array(

                'code' => 'tax',
                'title' => 'KDV',
                'value' => $tax,
                'sort_order' => 5
            );

        }
        $totals[] = array(

            'code' => 'sub_total',
            'title' => 'Ara Toplam (KDV HARİÇ)',
            'value' => $subtotal,
            'sort_order' => 1
        );

        /*     $totals[] = array(

                 'code' => 'sub_total',
                 'title' => 'Kargo Ücreti',
                 'value' => isset($order['cargoPrice'])?$order['cargoPrice']:0,
                 'sort_order' => 3
             );*/

        $totals[] = array(

            'code' => 'total',
            'title' => 'Toplam',
            'value' => $order['invoicePrice'],
            'sort_order' => 9
        );

        $order_data['totals'] = $totals;
        $custom_data = array();
        return $order_data;


    }


    private function getProductInfo($product)
    {

        $product_info = $this->entegrasyon->getProductByOrderModel($product['barcode'],$product['code'],$product['name'],'cs');

        if($product_info['product']){
            $model=$product_info['product']['model'];
        }else {
            if($this->config->get('cs_setting_model_prefix')){
                $model2 = explode(mb_strtoupper($this->config->get('cs_setting_model_prefix')), $product['code']);
                $product_info = $this->entegrasyon->getProductByOrderModel($product['barcode'],$this->config->get('cs_setting_model_prefix').$model2[2],$product['name'],'cs');

            }

            if($product_info['product']){
                $model=$product_info['product']['model'];
            }else {

                $model=$product['barcode'];
            }
        }
        $kdv= $product['tax'];

        $price=(float)$this->entegrasyon->priceFormat($product['totalPrice'] - ($product['totalPrice'] / ((100+$kdv)/100) * ($kdv/100)),2);
        $tax=(float)$this->entegrasyon->priceFormat(($product['totalPrice'] / ((100+$kdv)/100) * ($kdv/100)),2);




        $product_data = array(
            'item_id' => '',
            'product_id'=>$product_info['product'] ?$product_info['product']['product_id']:0,
            'variant_id'=>$product_info['variant_id'],
            'name' => $product['name'],
            //  'model' => $product_info?isset($product_info['product']['model'])?$product_info['product']['model']:$product['productCode']:'',
            'model' => $model,
            'option' => $this->getOrderAtrributes($product),
            'download' => '',
            'quantity' => $product['quantity'],
            'subtract' => '',
            'shipment_info' => '',
            'list_price' => (float)($price-$tax),
            'price' => $this->config->get('easy_setting_order_price_with_tax')?$product['price']:$this->entegrasyon->priceFormat($price),
            'total' => $this->config->get('easy_setting_order_price_with_tax')?$this->entegrasyon->priceFormat($product['price']*$product['quantity']):$this->entegrasyon->priceFormat($price*$product['quantity']),
            'tax'   => $this->entegrasyon->priceFormat($tax),
            'totaltax'   => (float)($tax * $product['quantity']),
            'discount'   => (float)$product['discount'],
            'reward' => ''

        );

        $get_variant_info = $this->entegrasyon->getVariantByModel('cs',$product['productCode'],$product['productCode']);

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

        return $product_data;

    }

    private function getOrderAtrributes($attributes)
    {

        $options=array();
        foreach ($attributes['orderItemTextListModel'] as $attribute) {
            $options[$attribute['text']] = $attribute['value'];

        }

        return $options;


    }


    private function getShipmenInfo($order)
    {
        if ($order['cargoNumber']){


            return array(

                'shipping_code' => $order['cargoNumber'],
                'campaign_number' => $order['cargoNumber'],
                'shipment_method' => $order['cargoCompany']
            );
        }else{

            return array(

                //'shipping_code' => $order['cargoNumber'],
                'shipping_code' => $order['partialNumber'],
                // 'campaign_number' => $order['shipmentTrackingUrl'],
                'campaign_number' => $order['partialNumber'],
                'shipment_method' => $order['cargoCompany']
            );
        }




    }

    public function updateStock($order)
    {


        $isVarianter = false;
        $status=false;
        $message='';


        foreach ($order['products'] as $orderedproduct) {


            // print_r($orderedproduct);
            $quantity=$orderedproduct['quantity'];
            $size = 1;//$orderedproduct['option']['product_size'];

            //echo $size;



            $marketPlaceProductInfo=$this->getProductFromMarketPlace($orderedproduct['model']);


            if($marketPlaceProductInfo['result']['totalElements']){



                $model=$marketPlaceProductInfo['result']['content'][0]['productMainId'];

                if ($size != 'Tek Ebat') {
                    $isVarianter = true;
                    $modelLenght = strlen($orderedproduct['model']);

                    $model=$orderedproduct['model'];

                    $product_info = $this->entegrasyon->getProductByOrderModel($model,'cs');


                    //Product Founded
                    if ($product_info) {
                        $product_id = $product_info['product_id'];

                        $matched_option_info = $this->entegrasyon->getOptionValueForUpdateStock($size);


                        if ($matched_option_info) {

                            $product_option_stock = $this->entegrasyon->updateProductOptionStock($matched_option_info['oc_option_value_id'], $quantity, $product_id);

                            //$product_option_stock=true;
                            if($product_option_stock){

                                $status=true;
                                $message='Ürün Varyantı Başarıyla Güncellendi';

                            }else {

                                $message='Ürün Varyantı Güncellenemedi!';
                            }

                        } else {

                            $message= 'Ürün Seçenek değeri eşleştirilmemiş!';

                        }


                    }else {

                        $message= 'Ürün bulunamadı!';
                    }


                }else {

                    //echo 'Ürün Seçenekli Değil!';

                    $product_info = $this->entegrasyon->getProductByOrderModel($model,'cs');


                    if($product_info){

                        $product_info['quantity'] -= (int)$orderedproduct['quantity'];
                        //  echo 'Şimdiki Stok'.$product_info['quantity'].'<br>';

                        $this->entegrasyon->updateStock($product_info);

                        $message= 'Seçeneksiz olan ürününüz bulundu ve stoğu güncellendi!';
                        $status =true;

                    }else {

                        $message= 'Seçeneksiz olan ürününüz bulunamadı!';
                    }



                }


            }

            //  $product_size=$order[];


        }

        return array('status'=>$status,'message'=>$message);

    }


}





