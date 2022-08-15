<?php
class ModelEntegrasyonOrderEptt extends Model{

    public function getOrders($debug=false)
    {

        $orderList=array();

      //  $this->load->model('entegrasyon/general');




        ;
        //$tomorrow = date("Y-m-d H:i:s", strtotime("+1 day"));
        $post_data['request_data']='';//$tomorrow;
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('eptt');

        $result=$this->entegrasyon->clientConnect($post_data,'get_orders','eptt',$debug);
        

        if(isset($result['result']['SiparisKontrolListesiV2Result']['TedarikciSiparisKontrolV2'])) {
            $orders=$result['result']['SiparisKontrolListesiV2Result']['TedarikciSiparisKontrolV2'];



            if (isset($orders['Eposta'])) {
                if(!$this->entegrasyon->checkOrderByMarketPlaceOrderId($orders['SiparisNo'])){
                    $orderList[] = $this->getOrder($orders);
                }


            } else {

                foreach ($orders as $order) {


                    if(!$this->entegrasyon->checkOrderByMarketPlaceOrderId($order['SiparisNo'])){
                        $orderList[] = $this->getOrder($order);
                    }


                }
            }

        }



        return $orderList;

    }



    public function getOrder($order)

    {


        $custom_data=$order['MusteriAdi'].' '.$order['MusteriSoyadi'];
        $first_name = $order['MusteriAdi'];
        $lastname =$order['MusteriSoyadi'];
        $adres = $order['SiparisAdresi'];
        $sehir = $order['SiparisIli'];
        $ilce =$order['SiparisIlce'];
        $postakodu = '';
        $phone = $order['TelefonNo'];
        $email=$order['Eposta'];
        $tcKimlikNo=$order['TCKN'];
        if($this->config->get('eptt_setting_add_tc')){

            $adres=$adres.'Vergi/Kimlik No:'.$tcKimlikNo;

        }

        $address_data = array(

            'firstname' => $first_name,
            'lastname' => $lastname,
            'tax_office'=>'',
            'tax_id'=>'',
            'company' => '',
            'address_1' => $adres,
            'address_2' => '',
            'city' => $sehir,
            'town'=>$ilce,

            'postcode' => $postakodu,
            'country_id' => 215,
            'zone_id' => ''

        );


        $order_data = array(
            'firstname' => $first_name,
            'lastname' => $lastname,
            'tckimlikno' => $tcKimlikNo,
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
            'order_type' => 'eptt',
            'plength' => '',
            'poption' => '',


        );

        $order_data['payment_company_id'] = $tcKimlikNo;
        $order_data['payment_tax_id'] = $tcKimlikNo;
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


        //print_r($this->config);

        // $user = $this->adduser($result);

        /*   if ($this->config->get('gg_setting_user_register')) {


               if (VERSION == '2.3.0.2') {
                   $this->load->model('customer/customer');
               } else {
                   $this->load->model('sale/customer');
               }


               if (VERSION == '2.3.0.2') {

                   $userExists = $this->model_customer_customer->getCustomerByEmail($order_data['email']);

               } else {
                   $userExists = $this->model_sale_customer->getCustomerByEmail($order_data['email']);

               }


               if ($userExists) {

                   $order_data['customer_id'] = $userExists['customer_id'];

               } else {


                   if (VERSION == '2.3.0.2') {

                       $order_data['customer_id'] = $this->model_customer_customer->addCustomer($order_data);

                   } else {
                       $order_data['customer_id'] = $this->model_sale_customer->addCustomer($order_data);

                   }


               }


           } else {

               $order_data['customer_id'] = '';

           }
*/
        // $this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET invoice_prefix = '" . $this->db->escape($data['invoice_prefix']) . "', store_id = '" . (int)$data['store_id'] . "', store_name = '" . $this->db->escape($data['store_name']) . "', store_url = '" . $this->db->escape($data['store_url']) . "', customer_id = '" . (int)$data['customer_id'] . "', customer_group_id = '" . (int)$data['customer_group_id'] . "', firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', custom_field = '" . $this->db->escape(isset($data['custom_field']) ? json_encode($data['custom_field']) : '') . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_custom_field = '" . $this->db->escape(isset($data['payment_custom_field']) ? json_encode($data['payment_custom_field']) : '') . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', payment_code = '" . $this->db->escape($data['payment_code']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "', shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_custom_field = '" . $this->db->escape(isset($data['shipping_custom_field']) ? json_encode($data['shipping_custom_field']) : '') . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', shipping_code = '" . $this->db->escape($data['shipping_code']) . "', comment = '" . $this->db->escape($data['comment']) . "', total = '" . (float)$data['total'] . "', affiliate_id = '" . (int)$data['affiliate_id'] . "', commission = '" . (float)$data['commission'] . "', marketing_id = '" . (int)$data['marketing_id'] . "', tracking = '" . $this->db->escape($data['tracking']) . "', language_id = '" . (int)$data['language_id'] . "', currency_id = '" . (int)$data['currency_id'] . "', currency_code = '" . $this->db->escape($data['currency_code']) . "', currency_value = '" . (float)$data['currency_value'] . "', ip = '" . $this->db->escape($data['ip']) . "', forwarded_ip = '" .  $this->db->escape($data['forwarded_ip']) . "', user_agent = '" . $this->db->escape($data['user_agent']) . "', accept_language = '" . $this->db->escape($data['accept_language']) . "', date_added = NOW(), date_modified = NOW()");

        //Sipariş oluşturuyoruz

        //$n11_shipping = $result->orderDetail->shippingAddress;


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
            'shipping_method' => 'Aras',
            'shipping_code' => 'extraflat1.flat',
            'shipping_zone' => $sehir,
            'shipping_country' => 'Türkiye',
            'shipping_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'shipping_custom_field	' => '',

            'shipping_code' => 'Ptt Kargo',

        );


        $payment_address = array(

            'payment_firstname' => $first_name,
            'payment_lastname' => $lastname,
            'payment_company' => $order['VergiDaire'].'/'.$order['VergiNo'],
            'payment_address_1' => $adres,
            'payment_address_2' => '',
            'payment_city' => $sehir,
            'payment_town' => $ilce,
            'payment_postcode' => $postakodu,
            'payment_country_id' => 215,
            'payment_country' => 'Türkiye',
            'payment_zone_id' => '',
            'payment_code' => 'ePttAvm',
            'payment_method' => 'EpttAvm',
            'payment_zone' => $sehir,
            'payment_address_format' => '{firstname} {lastname}
{company}
{address_1}
{address_2}
{postcode}, {city} - {zone} / {country}',
            'payment_custom_field	' => '',

        );


        $order_data = array_merge($order_data, $payment_address);
        $order_data = array_merge($order_data, $shipping_address);
        // $order_data['payment_address']=$payment_address;
        // $order_data['shipping_address']=$shipping_address;

        $order_data['order_date'] = date('Y-m-d H:i:s',strtotime($order['IslemTarihi']));
        $order_data['affiliate_id'] = '';
        $order_data['commission'] = '';
        $order_data['marketing_id'] = '';
        $order_data['tracking'] = '';
        $order_data['order_id'] = $order['SiparisNo'];
        $order_data['order_status_id'] = $order['SiparisUrunler']['SiparisUrun']['SiparisDurumu'];

        $order_data['shipping_info']=$this->getShipmenInfo($order);

        $order_data['payment_info']=array('vergi_dairesi'=>$order['VergiDaire'],'vergi_yada_kimlik_no'=>$order['VergiNo']);



        $order_data['products'] = array();

        $order_data['total'] = 0;

        $total = 0;
        $tax = 0;
        $subtotal = 0;


        if(isset($order['SiparisUrunler']['SiparisUrun']['KdvOrani'])){

            $product=$order['SiparisUrunler']['SiparisUrun'];
            $totals = array();


            $tax += ((float)$product['KdvDahilToplamTutar'] / 1.18) * .18;


            $subtotal += (float)$product['KdvDahilToplamTutar'] / 1.18;


            $total += (float)$product['KdvDahilToplamTutar'];


            $order_data['totals'] = $totals;

            $order_data['products'] = $this->getProductInfo($product, $order);

            $order_data['total'] = (float)$product['KdvDahilToplamTutar'];

        }else {
            foreach ($order['SiparisUrunler']['SiparisUrun'] as $product) {

                $totals = array();


                $tax += ((float)$product['KdvDahilToplamTutar'] / 1.18) * .18;


                $subtotal += (float)$product['KdvDahilToplamTutar'] / 1.18;


                $total += (float)$product['KdvDahilToplamTutar'];


                $order_data['totals'] = $totals;

                $order_data['products'] = $this->getProductInfo($product, $order);

                $order_data['total'] = (float)$product['KdvDahilToplamTutar'];


            }

        }


        $totals = array();

        if (!$this->config->get('easy_setting_order_price_with_tax')) {

            $totals[] = array(

                'code' => 'tax',
                'title' => 'KDV (%18)',
                'value' => ($product['KdvDahilToplamTutar'] / 1.18) * .18,
                'sort_order' => 5
            );
        }

        $totals[] = array(

            'code' => 'sub_total',
            'title' => 'Ara Toplam',
            'value' => $subtotal,
            'sort_order' => 1
        );


        $totals[] = array(

            'code' => 'sub_total',
            'title' => 'Kargo Ücreti',
            'value' => 0,
            'sort_order' => 3
        );

        $totals[] = array(

            'code' => 'total',
            'title' => 'Toplam',
            'value' => $product['KdvDahilToplamTutar'],
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


    private function getProductInfo($product,$order){

        $product_info = $this->entegrasyon->getProductByOrderModel($product['UrunBarkod'],$product['UrunBarkod'],$order['UrunAdi'],'eptt');
        if($product_info['product']){
            $model=$product_info['product']['model'];
        }else {
            $model=$product['UrunBarkod'];
        }

        $products[] = array(
            'item_id' => '',
            'product_id'=>$product_info['product'] ?$product_info['product']['product_id']:0,
            'variant_id'=>$product_info['variant_id'],
            'name' => $order['UrunAdi'],
            'model' => $model,

            'option' => $this->getOrderAtrributes($product),
            'download' => '',
            'quantity' => $product['ToplamIslemAdedi'],
            'subtract' => '',
            'shipment_info'=>$this->getShipmenInfo($order),
            'list_price' => (float)$product['KdvDahilToplamTutar'],
            'price' => (float)$product['KdvDahilToplamTutar'],
            'total' => (float)$product['KdvDahilToplamTutar'],
            'tax' => '',
            'totaltax' => '',
            'discount' => '',
            'reward' => ''
        );

        return $products;

    }

    private function getOrderAtrributes($attributes)
    {


        return array();


    }


    private function getShipmenInfo($order)
    {

        $shipping=array(

            'shipping_code'=>'',
            'campaign_number'=>$order['SiparisNo'],
            'shipment_method'=>'PTT Kargo'
        );


        return $shipping;

    }





    public function updateStock($order)
    {
        $status = true;

        foreach ($order['products'] as $orderedproduct) {

            $model=$orderedproduct['model'];
            $product_info = $this->entegrasyon->getProductByOrderModel($model,'eptt');


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





