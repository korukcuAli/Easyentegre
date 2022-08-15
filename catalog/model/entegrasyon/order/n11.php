<?php

class ModelEntegrasyonOrderN11 extends Model
{


    public function getOrders($debug=false)
    {

        $orders=array();
        $this->load->model('entegrasyon/general');
        //'New'
        $new_order_list=$this->getN11Orders('New',$debug);
        $approved_list=$this->getN11Orders('Approved',$debug);
        $shipped_list=$this->getN11Orders('Shipped',$debug);


        $order_list=array_merge($new_order_list,$approved_list);
        $order_list=array_merge($shipped_list,$order_list);



        if($order_list){


            foreach ($order_list as $order) {


                if(!$this->entegrasyon->checkOrderByMarketPlaceOrderId($order['id'])){

                    $orders[] = $this->getOrder($order);
                }
            }
        }
        return $orders;

    }


    public function getOrder($order)

    {






        $customer = $order['billingAddress']['fullName'];
        $customer = explode(' ', $customer);
        $first_name = isset($customer[2]) ? $customer[0] . ' ' . $customer[1] : $customer[0];
        $lastname = end($customer);
        $address_data = array();
        $ilce=$order['shippingAddress']['district'];
        $vergidairesi=isset($order['billingAddress']['taxHouse'])?$order['billingAddress']['taxHouse']:'';
        $vergiNo=isset($order['billingAddress']['taxId'])?$order['billingAddress']['taxId']:'';
        $tcID=$order['citizenshipId'];


        $vergiNo=$vergiNo?$vergiNo:$tcID;
        $adres=$order['shippingAddress']['address'] . ' ' . $order['shippingAddress']['city'] . ' ' . $order['shippingAddress']['district'];


        $address_data = array(
            'firstname' => $first_name,
            'lastname' => $lastname,
            'company' => '',
            'tax_office'=>$vergidairesi,
            'tax_id'=>$vergiNo,
            'address_1' =>$adres ,
            'city' => $order['shippingAddress']['city'],
            'postcode' => isset($order['shippingAddress']['postalCode']) ? $order['shippingAddress']['postalCode'] : '',
            'country_id' => 215,
            'town'=>$ilce,

            'zone_id' => ''

        );


        $order_data = array(
            'firstname' => $first_name,
            'lastname' => $lastname,
            'tckimlikno' => $order['citizenshipId'],
            'customer_group_id' => $this->config->get('config_customer_group_id'),
            'email' => $order['buyer']['email'],
            'telephone' => isset($order['shippingAddress']['gsm']) ? $order['shippingAddress']['gsm'] : '',
            'fax' => '',
            'newsletter' => '',
            'password' => isset($order['shippingAddress']['gsm']) ? $order['shippingAddress']['gsm'] : '',
            'status' => 1,
            'approved' => 1,
            'safe' => 1,
            'address' => $address_data,
            'order_type' => 'n11',
            'plength' => '',
            'poption' => '',


        );


        $order_data['order_id'] = $order['orderNumber'];
        $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
        $order_data['invoice_no'] = 0;
        $order_data['store_id'] = $this->config->get('config_store_id');
        $order_data['store_name'] = $this->config->get('config_name');
        $order_data['payment_company_id'] = $vergidairesi;
        $order_data['payment_tax_id'] = $vergiNo;
        if ($order_data['store_id']) {
            $order_data['store_url'] = $this->config->get('config_url');
        } else {
            if ($this->request->server['HTTPS']) {
                $order_data['store_url'] = HTTPS_SERVER;
            } else {
                $order_data['store_url'] = HTTP_SERVER;
            }
        }



        $order_data['order_date'] = date('Y-m-d H:i:s',strtotime(str_replace('/','-',$order['createDate'])));


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

        $shipment_info=null;
        if (isset($order['itemList']['item']['productSellerCode'])) {

            $item = $order['itemList']['item']['shipmentInfo'];
            $shipment_info=$this->getShipmenInfo($item);

        } else {
            $item = $order['itemList']['item'][0]['shipmentInfo'];
            $shipment_info=$this->getShipmenInfo($item);
        }

        $order_data['shipping_info']=$shipment_info;


        $shipping_address = array(
            'shipping_firstname' => $first_name,
            'shipping_lastname' => $lastname,
            'shipping_company' => '',
            'shipping_address_1' => $order['shippingAddress']['address'] . ' ' . $order['shippingAddress']['city'] . ' ' . $order['shippingAddress']['district'],
            'shipping_address_2' => '',
            'shipping_city' => $order['shippingAddress']['city'],
            'shipping_town' => $order['shippingAddress']['district'],
            'shipping_postcode' => isset($order['shippingAddress']['postalCode']) ? $order['shippingAddress']['postalCode'] : '',
            'shipping_country_id' => 215,
            'shipping_zone_id' => '',
            'shipping_method' => 'Kampanya No:'.$shipment_info['campaign_number'].'-'.'Kargo No:'.$shipment_info['shipping_code'].'-'.'Kargo Metodu'.$shipment_info['shipment_method'],
            'shipping_zone' => $order['shippingAddress']['city'],
            'shipping_country' => 'Türkiye',
            'shipping_address_format' => '{firstname} {lastname}
                {company}
                {address_1}
                {address_2}
                {postcode}, {city} - {zone} / {country}',
            'shipping_custom_field' =>$shipment_info,
            'shipping_code' => 'N11'

        );
        $shipment_method='N11';
        if($shipment_info['campaign_number'])$shipment_method.=' - Kampanya No:'.$shipment_info['campaign_number'];
        if($shipment_info['shipping_code'])$shipment_method.=' - Kargo No:'.$shipment_info['shipping_code'];
        if($shipment_info['shipment_method'])$shipment_method.=' - Kargo Metodu:'.$shipment_info['shipment_method'];
        $shipping_address['shipping_method']=$shipment_method;

        $vergi_bilgileri='';
        if($vergidairesi){

            $custom_payment_data=array('Vergi_Dairesi'=>$vergidairesi,'Vergi_No'=>$vergiNo);
            $vergi_bilgileri.='VD:'.$vergidairesi.'-'.'VN:'.$vergiNo;
        }else {
            $custom_payment_data=array('TC_Kimlik_No'=>$vergiNo);
            $vergi_bilgileri.='TC No:'.$tcID;
        }

        $payment_address = array(

            'payment_firstname' => $first_name,
            'payment_lastname' => $lastname,
            'payment_company' => '',
            'payment_address_1' => $order['billingAddress']['address'] . ' ' . $order['billingAddress']['city'] . ' ' . $order['billingAddress']['district'],
            'payment_address_2' => '',
            'payment_city' => $order['billingAddress']['city'],
            'payment_town' => $order['billingAddress']['district'],
            'payment_postcode' => '',
            'payment_country_id' => 215,
            'payment_country' => 'Türkiye',
            'payment_zone_id' => '',
            'payment_code' => 'N11',
            'payment_zone' => $order['billingAddress']['city'],
            'payment_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'payment_custom_field'=>$custom_payment_data

        );




        $payment_method='N11 - '.$vergi_bilgileri;


        $payment_address['payment_method']=$payment_method;
        $order_data['payment_info']=$custom_payment_data;


        /* if($this->config->get('n11_setting_add_tc')){
             $payment_address['payment_custom_field'] = 'Vergi dairesi,:'.$vergidairesi.' - Vergi No:'.$vergiNo;
         }*/


        $order_data = array_merge($order_data, $shipping_address);
        $order_data = array_merge($order_data, $payment_address);


        $order_data['affiliate_id'] = '';
        $order_data['commission'] = '';
        $order_data['marketing_id'] = '';
        $order_data['tracking'] = '';
        $order_data['custom_field']=array('fatura_bilgileri'=>$custom_payment_data,'kargo_bilgileri'=>$shipment_info);



        $order_data['total'] = 0;

        $total = 0;
        $tax = 0;
        $subtotal = 0;



        if (isset($order['itemList']['item']['productSellerCode'])) {

            $product = $order['itemList']['item'];

            $totals = array();
            $tax += 0;
            $subtotal += (float)$order['itemList']['item']['sellerInvoiceAmount'];
            $total += (float)$order['itemList']['item']['sellerInvoiceAmount'];
            $order_data['totals'] = $totals;
            $order_data['products'][] = $this->getProductInfo($product);
            $order_data['total'] =  $order['billingTemplate']['sellerInvoiceAmount'];


        } else {


            foreach ($order['itemList']['item'] as $product) {



                $totals = array();


                $tax += 0;


                $subtotal += (float)$product['sellerInvoiceAmount'];


                $total += (float)$product['sellerInvoiceAmount'];


                $order_data['totals'] = $totals;

                $order_data['products'][] = $this->getProductInfo($product);

                $order_data['total'] = $order['billingTemplate']['sellerInvoiceAmount'];


            }
        }



        $order_data['order_status_id'] = $this->orderStatus;

        $totals = array();



        $totals[] = array(

            'code' => 'sub_total',
            'title' => 'Ara Toplam',
            'value' => $subtotal,
            'sort_order' => 1
        );


        if ($order['billingTemplate']['totalServiceItemOriginalPrice']){
            $totals[] = array(

                'code' => 'sub_total',
                'title' => 'Kargo Ücreti',
                'value' => $order['billingTemplate']['totalServiceItemOriginalPrice'],
                'sort_order' => 3
            );
        }



        /*if($order['billingTemplate']['totalSellerDiscount']){

                $totals[] = array(

                    'code' => 'sub_total',
                    'title' => 'Satıcı İndirimi',
                    'value' => $order['billingTemplate']['totalSellerDiscount'],
                    'sort_order' => 3
                );

            }

 */


        $totals[] = array(

            'code' => 'total',
            'title' => 'Toplam',
            'value' => $total + $order['billingTemplate']['totalServiceItemOriginalPrice'],
            'sort_order' => 9
        );


        $order_data['totals'] = $totals;



        // $order_id = $this->model_n11_order->addOrder($order_data);



        return $order_data;


    }


    private function getOrderAtrributes($attributes,$product_id)
    {


        $options=array();


        if(isset($attributes['attribute']['name'])){

//$option_id=$this->entegrasyon->getOptionIdByName($attributes['attribute']['name']);
            $option_value_id=$this->entegrasyon->getOptionValueIdByName($attributes['attribute']['value']);


            if($option_value_id){

                $option_info=$this->entegrasyon->getProductOptionInfoByProductIdAndOptionValueId($product_id,  $option_value_id);
                $options[]=array('product_option_id'=>$option_info['option_value_id'],'product_option_value_id'=>$option_info['product_option_value_id'],'name'=>$option_info['option_name'],'value'=>$option_info['value']);

            }

        }else {

            foreach ($attributes['attribute'] as $attribute) {

                //  $option_id=$this->entegrasyon->getOptionIdByName($attribute['name']);
                $option_value_id=$this->entegrasyon->getOptionValueIdByName($attribute['value']);


                if($option_value_id) {
                    $option_info = $this->entegrasyon->getProductOptionInfoByProductIdAndOptionValueId($product_id, $option_value_id);
                    $options[] = array('product_option_id' => $option_info['option_value_id'], 'product_option_value_id' => $option_info['product_option_value_id'], 'name' => $option_info['option_name'], 'value' => $option_info['value']);
                }
            }

        }
        return $options;


    }

    private $orderStatus;
    private function getProductInfo($product){

        $this->orderStatus=$product['status'];
        // $product_info = $this->entegrasyon->getProductByOrderModel($product['productSellerCode'],$product['productSellerCode'],$product['productName'],'n11');

        $model=isset($product['sellerStockCode'])?$product['sellerStockCode']:$product['productSellerCode'];

        $product_info = $this->entegrasyon->getProductByOrderModel($model,$model,$product['productName'],'n11');
        if($product_info['product']){
            $basemodel=$product_info['product']['model'];
        }else {
            $basemodel=$model;
        }

        $product_data = array(

            'item_id' => $product['id'],
            'product_id'=>$product_info['status'] ?$product_info['product']['product_id']:0,
            'variant_id'=>$product_info['variant_id'],
            'status'=>$product['status'],
            'name' => $product['productName'],
            'model' => $basemodel,

            'download' => '',
            'quantity' => $product['quantity'],
            'subtract' => '',
            'shipment_info'=>$this->getShipmenInfo($product['shipmentInfo']),
            'list_price' => (float)$product['price'],
            'price' => $product["dueAmount"]?(float)$product['dueAmount']:(float)$product['price'],
            'discount' => $product['totalMallDiscountPrice']? $product['totalMallDiscountPrice']:$product['totalSellerDiscount'],
            'kdv' => "",
            'total' => (float)$product['sellerInvoiceAmount'],
            'tax' => "",
            'totaltax' => "",
            'reward' => ''
        );





        //   $options = isset($product['attributes']['attribute'])?$this->getOrderAtrributes($product['attributes'],$product_data['product_id']):array();

        $product_data['option']=array();

        return $product_data;

    }

    private function getShipmenInfo($item)
    {


        return array(

            'shipping_code'=>$item['campaignNumber'] ? $item['campaignNumber']:$item['shipmentCode'],
            'campaign_number'=>isset($item['campaignNumber']) ? $item['campaignNumber']:'',
            'shipment_method'=>isset($item['shipmentCompany']['name'])?$item['shipmentCompany']['name']:''
        );

    }


    private function getN11Orders($status,$debug=false)
    {


        $filter_data = array(
            'status' => $status,
            'buyerName' => '',
            'orderNumber' => '',
            'productSellerCode' => '',
            'recipient' => '',
            'period' => array('startDate' => '', 'endDate' => date('d/m/Y'))

        );

        $pagingData = array(
            'currentPage' => 0,
            'pageSize' => 100);

        $post_data['request_data']=array('filter_data'=>$filter_data,'paging_data'=>$pagingData);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        $order_list=$this->entegrasyon->clientConnect($post_data,'get_orders','n11',$debug);



        return $order_list['result'];
    }

    public function updateStock($order)
    {

        $status = true;

        foreach ($order['products'] as $orderedproduct) {


            $model=$orderedproduct['model'];
            $product_info = $this->entegrasyon->getProductByOrderModel($model,'n11');
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