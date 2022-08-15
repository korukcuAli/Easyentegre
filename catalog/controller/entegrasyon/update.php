<?php

class ControllerEntegrasyonUpdate extends Controller
{

    private $reg='';

    public function __construct($registry)
    {

        parent::__construct($registry);

        $this->reg=$registry;
    }

    public function update_after_xml()
    {

    //$this->entegrasyon->deleteSetting('easyxml');
        $products=$this->config->get('easyxml_updated_products');


         print_r($products);
         return;




        $pre_pro=array();

        $limit=0;
        foreach ($products as $product) {


        }

        if($pre_pro){

            $update_results = $this->entegrasyon->updateMarketplaceProdutcsAfterOrder($pre_pro, HTTPS_SERVER, false,' after xml update');

            //$update_results=array(2329=>array(),2333=>array());

            foreach ($update_results as $key => $item) {



                $index=array_search($key,$products);


                if($index !== false){

                    unset($products[$index]);

                    // print_r($products);
                }

                try {

                    $sql = "update " . DB_PREFIX . "product SET date_modified='2018-04-17 10:00:00' where product_id='" . $key . "'";

                    $this->db->query($sql);


                } catch (Exception $exception) {
                    echo $exception->getMessage();

                }

            }


            $this->entegrasyon->editSetting('easyxml',array('easyxml_updated_products'=>array_values($products)));
        }


        echo '=== After update ===<br>';
        //print_r($products);

    }

    public function updatedb()
    {
        error_reporting(0);


        try {

            $this->db->query("ALTER TABLE `".DB_PREFIX."order` ADD `payment_tax_id` INT(15) NULL AFTER `payment_code`;");

        }catch (Exception $exception){


        }

        try {
            $this->db->query("ALTER TABLE `".DB_PREFIX."order` ADD `payment_company_id` INT(15) NULL AFTER `payment_code`;");

        }catch (Exception $exception){

            print_r($exception);
        }



        try {

            $this->db->query("CREATE TABLE `".DB_PREFIX."es_market_product` (
  `market_product_id` int(11) NOT NULL,
  `code` varchar(10) CHARACTER SET utf8 NOT NULL,
  `oc_product_id` int(11) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `model` varchar(255) CHARACTER SET utf8 NOT NULL,
  `barcode` varchar(255) CHARACTER SET utf8 NOT NULL,
  `stock_code` varchar(255) CHARACTER SET utf8 NOT NULL,
  `sale_price` decimal(15,4) NOT NULL,
  `list_price` decimal(15,4) NOT NULL,
  `quantity` int(11) NOT NULL,
  `sale_status` int(1) NOT NULL,
  `approval_status` int(1) NOT NULL,
  `custom_data` longtext CHARACTER SET utf8 NOT NULL,
  `marketplace_product_id` varchar(256) NOT NULL,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;");


            try {
                $this->db->query("ALTER TABLE `".DB_PREFIX."es_market_product` ADD PRIMARY KEY (`market_product_id`);");


            }catch (Exception $exception){


            }



            try {
                $this->db->query("ALTER TABLE `".DB_PREFIX."es_market_product` ADD PRIMARY KEY (`market_product_id`);");

            }catch (Exception $exception){


            }

            try {
                $this->db->query("ALTER TABLE `".DB_PREFIX."es_market_product` MODIFY `market_product_id` int(11) NOT NULL AUTO_INCREMENT;");

            }catch (Exception $exception){


            }



        }catch (Exception $exception){


        }


        try {
            $this->db->query("ALTER TABLE `".DB_PREFIX."es_ordered_product`  ADD `list_price` DECIMAL(15,4) NOT NULL  AFTER `price`;");

        }catch (Exception $exception){


        }


        try {
            $this->db->query("ALTER TABLE `".DB_PREFIX."es_ordered_product`  ADD `discount` DECIMAL(15,4) NOT NULL  AFTER `price`;");

        }catch (Exception $exception){



        }


        try {
            $this->db->query("ALTER TABLE `".DB_PREFIX."es_order`  ADD `shipping_info` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  AFTER `town`");

        }catch (Exception $exception){


        }

        try {
            $this->db->query("ALTER TABLE `".DB_PREFIX."es_order`  ADD `payment_info` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  AFTER `town`");

        }catch (Exception $exception){


        }



        try {
            $this->db->query("ALTER TABLE `".DB_PREFIX."es_ordered_product`  ADD `kdv` INT(2) NOT NULL  AFTER `price`;");

        }catch (Exception $exception){


        }

        try {

            $this->db->query("ALTER TABLE `" . DB_PREFIX . "es_attribute` CHANGE `attribute` `attribute` LONGTEXT  NOT NULL;");

        } catch (Exception $exception) {

            echo $exception->getMessage();
        }
    }



    public function update_competition_price()
    {

        $code = $this->request->post['code'];
        $price = $this->request->post['price'];
        $product_id =$this->request->post['product_id'];;

        $this->load->model('entegrasyon/general');

        // print_r($this->request->post);
        //return;

        $this->model_entegrasyon_general->update_product_price($code,$price,$product_id);

        $debug=false;
        $product_info = $this->entegrasyon->getProduct($product_id);
        $product_info = $this->entegrasyon->getProductForUpdate($code, $product_info, 0, true, HTTPS_SERVER);
        $post_data['request_data']=$product_info;
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace($code);

        $result= $this->entegrasyon->clientConnect($post_data,'update_basic',$code,$debug,false);

        echo json_encode($result);




    }


    public function orders()
    {


        if (isset($this->request->get['mode'])) {
            $mode=true;
        }else {
            $mode=false;
        }

        $debug=false;
        if(isset($this->request->get['debug'])){

            $debug=true;
        }

        $this->load->model('entegrasyon/general');

        if (!$this->model_entegrasyon_general->checkPermission()) {

            echo json_encode(array('status' => false, 'message' => 'Gerçek Mağaza bilgileri kullanıldığı için Demo versiyonda Sipariş Kontrolüne izin verilmemektedir.'));
            return;

        }


/*
                                                                             $this->load->model('entegrasyon/order/n11');
                                                                             $res =$this->model_entegrasyon_order_n11->getOrders();
                                                                        print_r($res);
                                                                        return;

*/



        $stokUpdateData = array();
        $total_new = 0;

        $order_temp = 0;
        $ordered_product_temp = 0;
        $defined_ordered_product_temp = 0;
        $this->load->model('entegrasyon/order');
        $marketplaces = $this->model_entegrasyon_general->getMarketPlaces();
        $orders = array();

        foreach ($marketplaces as $marketplace) {
            if ($marketplace['status']) {
                $this->load->model('entegrasyon/order/' . $marketplace['code']);
                $orders[$marketplace['code']] = $this->{'model_entegrasyon_order_' . $marketplace['code']}->getOrders($debug);
            }

        }

        $total_order_count = array();


        $updated_products=array();


        foreach ($orders as $code => $orderpatch) {

//print_r($orderpatch);
//return;

            $order_count = 0;
            foreach ($orderpatch as $order) {


                $order_temp++;

                if (!$this->model_entegrasyon_order->getOrder($order['order_id'])) {

                    //Siparişleri Veritabanına Yazdırıyoruz
                    $this->model_entegrasyon_order->addOrder($order, $code);
                    //Sipariş edilen ürünlerler katalog stoğundan düşülüyor.
                    $updated_product_ids = $this->entegrasyon->updateProductStock($order['products']);



                    if($updated_product_ids){
                        foreach ($updated_product_ids as $product_id) {
                            if(!in_array($product_id,$updated_products)){
                                $updated_products[]=$product_id;
                            }

                        }

                    }

                    //Stokları Değişen Ürünleri mevcut pazaryerlerinde güncelliyoruz!!

                    // die;
                    $order_count++;
                    $total_new++;

                }


            }


            $total_order_count[$code] = $order_count;


        }


        if($updated_products && $this->config->get('easy_setting_update_after_market_sale') ) {

            $update_result = $this->entegrasyon->updateMarketplaceProdutcsAfterOrder($updated_products, HTTPS_SERVER, $mode,' After Check Order By Cron');
        }
        if($mode){

            // $updated_data=array('questions'=>$questions,'update_result'=>$update_result);
            // if($this->config->get('easy_notification')){

            $this->load->model('entegrasyon/general');

            if($this->config->get('easy_setting_notification')){

                $marketplaces = $this->model_entegrasyon_general->getMarketPlaces();
                $order_data=array('total' => $total_new, 'markets' => $total_order_count);
                $question_data=$this->questions(true);
                $notification_data=array('market_data'=>$marketplaces,'order_data'=>$order_data,'question_data'=>$question_data);

                if($order_data['total'] || $question_data['total']) {

                    $result = $this->entegrasyon->sendNotification($notification_data, false);;

                    if ($this->config->get('easy_setting_sms_notification') && $this->config->get('easy_setting_sms_numbers') ){
                        $this->model_entegrasyon_order->sendSms($order_data);
                      }

                }
                //  }

            }

        }

        if($mode){
            $this->entegrasyon->updateCronRecycle();

            $logmesage='Cron ile sipariş kontrolü yapıldı.'.json_encode(array('total' => $total_new, 'markets' => $total_order_count));

            $this->entegrasyon->log('',$logmesage,false);

        }else {

            $logmesage='Kullanıcı isteği ile sipariş kontrolü yapıldı.'.json_encode(array('total' => $total_new, 'markets' => $total_order_count));

            $this->entegrasyon->log('',$logmesage,false);
        }


        //print_r($updated_products);
        echo json_encode(array('total' => $total_new, 'markets' => $total_order_count));

    }






    public function questions($mode=false)
    {

        $total_new=0;

        $this->load->model('entegrasyon/general');

        if (!$this->model_entegrasyon_general->checkPermission()) {

            echo json_encode(array('status' => false, 'message' => 'Gerçek Mağaza bilgileri kullanıldığı için Demo versiyonda Sipariş Kontrolüne izin verilmemektedir.'));
            return;

        }/*


                                                                     $this->load->model('entegrasyon/question/ty');
                                                                     $res =$this->model_entegrasyon_question_ty->getQuestions();
                                                                print_r($res);
                                                                return;

*/

        //
        $this->load->model('entegrasyon/question');
        $marketplaces = $this->model_entegrasyon_general->getMarketPlaces();

        $questions = array();

        foreach ($marketplaces as $marketplace) {
            if ($marketplace['status'] && ($marketplace['code']=='ty' || $marketplace['code']=='gg' || $marketplace['code']=='n11'  )) {
                $this->load->model('entegrasyon/question/' . $marketplace['code']);
                $questions[$marketplace['code']] = $this->{'model_entegrasyon_question_' . $marketplace['code']}->getQuestions();
            }

        }

        $total_question_count = array();



        foreach ( $questions as $code => $questionpatch) {
            $question_count = 0;




            foreach ($questionpatch['result'] as $question) {



                $question_info=$this->model_entegrasyon_question->getQuestion($question['id']);

                if (!$question_info) {


                    //soruları Veritabanına Yazdırıyoruz
                    $this->model_entegrasyon_question->addQuestion($question, $code);


                    $total_new++;
                    $question_count++;


                }else if($question['new_message'] && $question_info['answered'] ) {


                    $this->model_entegrasyon_question->updateQuestion($question);
                    $total_new++;
                    $question_count++;

                }else if($question['rejected']){
                    $this->model_entegrasyon_question->updateRejectedQuestion($question);



                }


            }

            $total_question_count[$code] = $question_count;

        }

        if($mode){

            return array('total' => $total_new, 'markets' => $total_question_count);
        }else{
            echo json_encode(array('total' => $total_new, 'markets' => $total_question_count));


        }

    }


/*
    public function product()
    {



        $this->load->model('entegrasyon/general');

        if (!$this->model_entegrasyon_general->checkPermission()) {

            echo json_encode(array('status' => false, 'message' => 'Gerçek Mağaza bilgileri kullanıldığı için Demo versiyonda Ürün Güncellemesine izin verilmemektedir.'));
            return;

        }


        echo '<html dir="ltr" lang="en">
<head>
<meta charset="UTF-8" /></head><body>';
        date_default_timezone_set('Europe/Istanbul');
        $product_count = 0;
        set_time_limit(0);
        ini_set("memory_limit", '-1');
        ini_set('max_execution_time', 118000);
        //$last_update_date='2020-05-26 15:30:50';//$this->model_entegrasyon_general->getLastUpdateSession(1);
        $last_update_date = $this->config->get('module_entegrasyon_last_update');//$this->model_entegrasyon_general->getLastUpdateSession(1);


        if (!$last_update_date) {

            $last_update_date = date("Y-m-d H:i:s", strtotime("-2 day"));
        }

        $marketPlaces = $this->model_entegrasyon_general->getMarketPlaces();


        //  echo "Son Güncelleme Tarihi=".$last_update_date.'<br>';

        $marketplace_products = $this->model_entegrasyon_general->getUpdatableProducts();


        $i = 1;

        if ($marketplace_products) {
            echo '<h2 style="color:darkred">Kalan güncellenecek Ürün Sayısı:' . count($marketplace_products) . '</h2><h3 style="color:darkgreen">Güncelleme Devam Ediyor, Lütfen Bekleyiniz...</h3><br>';

            echo 'Başlangıç:' . strtotime('h:i:s', time()) . '<br>';
            foreach ($marketplace_products as $key => $marketplace_product) {
                //  $product_modified_date=strtotime($marketplace_product['date_modified']);

                //Son Güncelleme Tarihi ile sıradaki ürününn son güncelleme tarihini karşılaştır.
                //Update edilebilir en az 1 ürün vasa yeni bir update session Oluştur.
                // $update_session_id=$this->model_entegrasyon_general->createUpdateSession(1);


                foreach ($marketPlaces as $marketPlace) {
                    $product = $this->entegrasyon->getProduct($marketplace_product['product_id'],$marketPlace['code']);

                    if ($marketPlace['status']) {

                        if ($marketplace_product[$marketPlace['code']]) {

                            // echo $product['name'].'-'.$marketPlace['name'].' de Güncellenebilir';
                            $product_info = $this->entegrasyon->getProductForUpdate($marketPlace['code'], $product,0,true,HTTPS_SERVER);


                            $product_info['model'] = $this->config->get($marketPlace['code'] . '_setting_model_prefix') . $product_info['model'];

                            $post_data['request_data'] = $product_info;
                            $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace($marketPlace['code']);

                            $result = $this->entegrasyon->clientConnect($post_data, 'update_basic', $marketPlace['code'], false);

                            if ($result['status']) {

                                $marketplace_data = unserialize($product_info[$marketPlace['code']]);
                                $marketplace_data['commission'] = $product_info['defaults']['commission'];
                                $marketplace_data['price'] = $product_info['sale_price'];

                                $this->entegrasyon->addMarketplaceProduct($product_info['product_id'], $marketplace_data, $marketPlace['code']);


                                echo $i . ' : ' . $marketPlace['name'] . ' : ' . $product_info['name'] . '<strong style="color: #0baf5c"> Güncellendi</strong><br>';

                            } else {

                                $error = $this->entegrasyon->getError($product_info['product_id'], $marketPlace['code']);
                                if ($error) {
                                    $this->entegrasyon->updateError($product_info['product_id'], $marketPlace['code'], 3, $result['message']);
                                } else {
                                    $this->entegrasyon->addError($product_info['product_id'], $marketPlace['code'], 3, $result['message']);
                                }

                                echo $i . ' : ' . $marketPlace['name'] . ' : ' . $product_info['name'] . '<strong style="color: darkred> Güncellenmedi!</strong> ' . $result["message"] . '<br>';


                            }


                            $logmesage=$product_info['model'] . ' Action:Update - Cron - Product';;

                            $logmesage.=' - Update content:'.'Stok & Fiyat';

                            $logmesage.='-Stock - :'.$product_info['quantity'].' - Sale Price:'.$product_info['sale_price'].' - List Price:'.$product_info['list_price'];

                            $logmesage.='- Result:'.$result['message'];

                            $this->entegrasyon->log($marketPlace['code'],$logmesage,false);


                        }


                    }


                }


                try {

                    $sql = "update " . DB_PREFIX . "product SET date_modified='2018-04-17 10:00:00' where product_id='" . $marketplace_product['product_id'] . "'";

                    $this->db->query($sql);


                } catch (Exception $exception) {
                    echo $exception->getMessage();

                }

                $i++;
                if ($i == 10) {

                    echo 'Bitiş' . strtotime('h:i:s', time()) . '<br>';
                    echo '<meta http-equiv="refresh" content="1;url=index.php?route=entegrasyon/update/product" />';

                    return;
                }

            }

        } else {

            echo 'Güncellenecek Ürün Bulunamadı! Güncelleme yapılabilemesi için en az bir pazaryerinde bulunan bir ürününüz olmalı ve bu ürünün özellileri Admin/katalog/product sayfasında değiştirilmiş olmalıdır.';

        }

        // echo 'hohoho';

        $query = $this->db->query("select now() as last_date");
        $last_date = $query->row['last_date'];


        $this->entegrasyon->editSetting('module_entegrasyon', array('module_entegrasyon_last_update' => $last_date, 'module_entegrasyon_status' => $this->config->get('module_entegrasyon_status'), 'module_entegrasyon_version' => $this->config->get('module_entegrasyon_version')));


    }
*/

    public function update_products_by_market()
    {

        $this->load->model('entegrasyon/general');

        if (!$this->model_entegrasyon_general->checkPermission()) {

            echo json_encode(array('status' => false, 'message' => 'Gerçek Mağaza bilgileri kullanıldığı için Demo versiyonda Ürün Güncellemesine izin verilmemektedir.'));
            return;
        }


        $code = $this->request->get['code'];


        echo '<html dir="ltr" lang="en">
<head>
<meta charset="UTF-8" /></head><body>';
        date_default_timezone_set('Europe/Istanbul');
        $product_count = 0;
        set_time_limit(0);
        ini_set("memory_limit", '-1');
        ini_set('max_execution_time', 118000);
        //$last_update_date='2020-05-26 15:30:50';//$this->model_entegrasyon_general->getLastUpdateSession(1);
        $last_update_date = $this->config->get('module_entegrasyon_last_update');//$this->model_entegrasyon_general->getLastUpdateSession(1);


        if (!$last_update_date) {

            $last_update_date = date("Y-m-d H:i:s", strtotime("-2 day"));
        }

        $marketPlace = $this->model_entegrasyon_general->getMarketPlace($code);


        //  echo "Son Güncelleme Tarihi=".$last_update_date.'<br>';
        $page=0;
        $limit=100;
        $filter_data = array('market' => $code);
        $total_marketplace_products = $this->model_entegrasyon_general->getUpdatableProductsTotalTest($filter_data);




        $total_page=round($total_marketplace_products/$limit);

        $post_data['market'] = $marketPlace;

        $baslangic = microtime(TRUE);
        $itemCount=0;
        //  for($a=0;$a<$total_page;$a++){
        //   $filter_data['limit']=$limit;
        //  $filter_data['start']=$a*$limit;
        $marketplace_products = $this->model_entegrasyon_general->getUpdatableProductsTest($filter_data);


        $post_data['request_data']=array();

        foreach ($marketplace_products  as $key => $marketplace_product ) {

            $product = $this->entegrasyon->getProduct($marketplace_product['product_id']);

            if($product){

                $product_info = $this->entegrasyon->getProductForUpdate($marketPlace['code'], $product,0,true,HTTPS_SERVER);

                $product_info['model'] = $this->config->get($marketPlace['code'] . '_setting_model_prefix') . $product_info['model'];

                $post_data['request_data'][] = array(
                    'name'=>$product_info['name'],
                    'model'=>$product_info['model'],
                    'quantity'=>$product_info['quantity'],
                    'list_price'=>$product_info['list_price'],
                    'sale_price'=>$product_info['sale_price'],
                    //'product_setting'=>$product_info['product_setting'],
                    'marketplace_data'=>$product_info[$code],
                    'have_discount'=>$product_info['have_discount'],
                    'variants'=>$product_info['variants']
                );


                if($product_info['variants']['status']){

                    $itemCount=$itemCount + count($product_info['variants']['variants']);

                }else {

                    $itemCount++;
                }



                if($itemCount==100){



                    $result = $this->entegrasyon->clientConnect($post_data, 'update_stock', $marketPlace['code'], true);
                    $itemCount=0;

                    return;
                    echo '100 Iteme ulaşıldı';

                    echo $key;

                }



            }




            //print_r($post_data['request_data']);



            //  echo '------ 5 Saniye Bekliyorum';
            // sleep(5);

            /*   foreach ($post_data['request_data'] as $key => $product_get) {


                    echo 'paket no'.($a+1).'-'.($key+1).'-'.$product_get['name'].'<br>';
                }*/



            // print_r( $post_data['request_data']);

        }

        $bitis = microtime(TRUE);
        $time_t =($bitis - $baslangic)*1000;
        $time_t = round($time_t,5);

        echo 'Bu sayfa '.$time_t.' saniyede yüklendi.';



    }

    public function add_product()
    {

        echo '<html dir="ltr" lang="tr">
<head>
<meta charset="UTF-8" /></head>';

        $this->load->model("entegrasyon/general");

        if (isset($this->request->get['model'])) {
            $model=$this->request->get['model'];
        }
        $admin = 'admin';
        if (isset($this->request->get['admin'])) {
            $admin=$this->request->get['admin'];
        }


        if (isset($this->request->get['code'])) {
            $code = $this->request->get['code'];


            $debug = false;
            if (isset($this->request->get['debug'])) {
                $debug = true;

            }

            $send = false;
            if (isset($this->request->get['send'])) {
                $send = true;

            }

            $product = $this->entegrasyon->getProductByModel($model,$model);

            $product_id = $product['product_id'];

            $message = '';

            // $this->load->model('entegrasyon/product/' . $code);

            $product_info = $this->entegrasyon->getProduct($product_id);


            $category_setting = $this->entegrasyon->getMarketPlaceCategory($product_id, $code);




            $manufacturer_setting = $this->entegrasyon->getMarketPlaceManufacturer($product_info['manufacturer_id'], $code);
            $product_setting = $this->entegrasyon->getSettingData($code, 'product', $product_id);


            if (!isset($product_setting[$code . '_category_id'])) {
                if ($category_setting == 2) {

                    $message .= ' Ürününüz hiç bir kategori ile ilişkilendirilmemiş! Önce Ürününüzü bir kategoriye ekleyiniz. ';

                } else {

                    if (!isset($category_setting[$code . '_category_id'])) {
                        $message .= 'Kategori Eşletirmesi Yapmalısınız! ';
                    }

                }
            }


            $product_data['defaults'] = $this->entegrasyon->getDefaults($category_setting, $manufacturer_setting, $product_setting, $code);


            if ($code == 'ty' || $code == 'hb') {
                if (!$product_info['manufacturer_id']) {
                    $message .= ' Ürününüz bir markaya ait olmalıdır!. Katalog->Ürünler bölümünden ürününüze bir marka ekleyin';

                } else if ($code == 'ty') {

                    if (!isset($manufacturer_setting['ty_manufacturer_id'])) {
                        $message .= ' Marka Eşleştirmesi yapmalısınız!.';
                    } else {

                        $product_data['manufacturer_id'] = $manufacturer_setting['ty_manufacturer_id'];

                    }

                }

            }

            if ($message) {


                echo  $message;

                return;
            }


            $category_info = isset($product_setting[$code . '_category_id']) ? $product_setting[$code . '_category_id'] : $category_setting[$code . '_category_id'];

            $category_info = explode('|', $category_info);


            $product_data['category_id'] = $category_info[0];
            $product_data['product_setting'] = $product_setting;
            $product_data['category_setting'] = $category_setting;
            $product_data['product_id'] = $product_id;


            if ($this->config->get($code . '_setting_barkod_place')) {

                $product_data['product_setting'][$code . '_barcode'] = $product_info[$this->config->get($code . '_setting_barkod_place')];

            }

            if ($this->config->get($code . '_setting_main_product_id')) {


                $product_data['product_setting'][$code . '_main_product_id'] = $product_info[$this->config->get($code . '_setting_main_product_id')];

            }


            $product_data['model'] = $this->config->get($code . '_setting_model_prefix') . $product_info['model'];
            $product_info['model'] = $product_data['model'];


            $product_data['quantity'] = $product_info['quantity'];
            $product_data['special'] = $product_info['special'];

            if ($this->config->get("easy_setting_price_place")) {

                $product_info['price'] =   $product_info[$this->config->get("easy_setting_price_place")];
                $product_data['list_price'] =   $product_info[$this->config->get("easy_setting_price_place")];

            }


            $product_data['list_price'] = $this->entegrasyon->calculatePrice($product_info['price'], $product_data['defaults'], $product_info['tax_class_id'], $code,$product_info);
            $product_data['sale_price'] = $product_info['special'] && $product_data['defaults']['product_special'] ? $this->entegrasyon->calculatePrice($product_info['special'], $product_data['defaults'], $product_info['tax_class_id'], $code,$product_info) : $product_data['list_price'];

            $product_data['main_image'] = $product_info['image'];
            $product_data['title'] = $product_info['name'];
            $product_data['description'] = $product_info['description'];
            $product_data['weight'] = $product_info['weight'];
            $product_data['tag'] = $product_info['tag'];


            $manufacturer = $this->entegrasyon->getManufacturer($product_info['manufacturer_id']);
            if ($manufacturer) {

                $product_data['manufacturer'] = $manufacturer['name'];

            }
            $product_data['tax_class_id'] = $product_info['tax_class_id'];
            $product_data['kdv'] = $this->entegrasyon->getKdvRange($product_info['tax_class_id']);


            if ($product_data['defaults']['additional_content']) {
                $information = $this->entegrasyon->getInformationDescriptions($product_data['defaults']['additional_content']);
                $product_data['description'] .= $information;

            }

            if (isset($product_setting[$code . '_product_desciption'])) {

                $product_data['description'] = $product_setting[$code . '_product_desciption'];
            }



            if (isset($product_setting[$code . '_product_list_price'])) {
                if ($product_setting[$code . '_product_list_price'] > $product_setting[$code . '_product_sale_price']) {
                    $product_info['have_discount'] = true;
                }
                $product_data['list_price'] = $product_setting[$code . '_product_list_price'];
            }


            $attributes=$this->entegrasyon->getSelectedAttributes($code,$product_setting,$category_setting);
            
            $product_data['product_setting']['selected_attributes'] = $attributes;
            $need_select = $this->entegrasyon->checkRequiredAttributes($category_info[0], $code, $attributes, $product_id);

            if ($need_select) {

                $message = 'Girmeniz Gereken Zorunlu Özellikler:' . implode('-', $need_select);

                echo  $message;
                return;


            }

            $product_data['variants'] = array();
            if ($this->config->get($code . '_setting_variant')) {

                if ($this->entegrasyon->isVarianterProduct($product_id)) {
/*
                    $matched_options = $this->entegrasyon->isOptionsMatched($category_info[0], $code);

                    if($matched_options && $code!='hb' && $code!='cs'){

                        $message = 'Eşleştirmeniz Gereken Seçenekler Var:' . implode('-', $matched_options);

                        echo $message;

                        //echo json_encode(array('status' => false, 'message' => $message));
                        return;

                    }*/

                    $product_variants = $this->entegrasyon->getPoductVariants($product_id);
                    $market_variants = $this->entegrasyon->getMarketVariant($product_variants, $code, $category_info[0], $product_id, $product_data['model'],HTTPS_SERVER);

                    if ($code == 'ty' || $code == 'gg' || $code == 'cs') {

                        $attributes = $this->entegrasyon->deleteIfInAttbutes($market_variants, $attributes);
                    }


                    if($market_variants['status']){
                        $product_data['variants']['variants']['options'] = $market_variants['variants'];

                    }else {

                        $message = $market_variants['message'];
                        echo $message;

                        //echo json_encode(array('status' => false, 'message' => $message));
                        return;
                    }


                    // print_r($market_variants['variants']);
                }

            }


            if($this->config->get('easy_setting_list_price') && $this->config->get($code.'_setting_product_special') ){

                $product_data['list_price']=$product_data['sale_price'];
                $product_data['special']=false;

            }


            if($this->config->get('easy_setting_critical_stock'))
            {

                $product_data['quantity'] =$product_data['quantity'] <=$this->config->get('easy_setting_critical_stock')?0:$product_data['quantity'];
            }

            $product_data['images']=$this->entegrasyon->getImagesByMarketPlace($product_id,$product_info['image'],$code,HTTPS_SERVER);

            $product_data['attributes'] = $attributes;

            include_once $admin.'/model/entegrasyon/product/'.$code.'.php';
            // include_once $admin.'/config.php';

            if(!$send){
                print_r($product_data);
                return;
            }
            $class="ModelEntegrasyonProduct".ucfirst($code);



            $result= (new $class($this->reg))->sendProduct($product_data, $attributes, $debug);

            // $result = $this->{"model_entegrasyon_product_" . $code}->sendProduct($product_data, $attributes, $debug);

            print_r($result);

            //$json = json_encode($result);

            //echo $json;

        }


    }


    public function get_product()
    {

        echo '<html dir="ltr" lang="tr">
<head>
<meta charset="UTF-8" /></head>';

        $this->load->model("entegrasyon/general");

        if (isset($this->request->get['model'])) {
            $model=$this->request->get['model'];
        }
        $admin = 'admin';
        if (isset($this->request->get['admin'])) {
            $admin=$this->request->get['admin'];
        }


        if (isset($this->request->get['code'])) {
            $code = $this->request->get['code'];


            $debug = false;
            if (isset($this->request->get['debug'])) {
                $debug = true;

            }


            $send = false;
            if (isset($this->request->get['send'])) {
                $send = true;

            }


            include_once $admin.'/model/entegrasyon/product/'.$code.'.php';


            $class="ModelEntegrasyonProduct".ucfirst($code);

            $result= (new $class($this->reg))->getMarketPlaceProduct($model,0,0,true);

            // $result = $this->{"model_entegrasyon_product_" . $code}->sendProduct($product_data, $attributes, $debug);

            print_r($result);

            //$json = json_encode($result);

            //echo $json;

        }


    }

    public function update_product()
    {


        $this->load->model("entegrasyon/general");


        //$code='gg';
        //$product_id=15943;

        $send = false;
        if (isset($this->request->get['send'])) {
            $send = true;

        }

        if (isset($this->request->get['model'])) {
            $model=$this->request->get['model'];
        }


        if (isset($this->request->get['mode'])) {
            $mode=$this->request->get['mode'];
        }else {

            $mode=0;
        }

        if (isset($this->request->get['code'])) {
            $code=$this->request->get['code'];


            $debug=false;
            if (isset($this->request->get['debug'])) {
                $debug=true;

            }


            $product=$this->entegrasyon->getProductByModel($model,$model);
            $product_id=$product['product_id'];

            $marketplace_data=$this->entegrasyon->getMarketPlaceProductForMarket($product_id,$code);
        

            if(!$marketplace_data){

                echo json_encode(array('status'=>false,'message'=>'Ürün mağazada bulunamadı, işlem yapabilmek için önce ürünü mağazaya göndermelisiniz.'));
                return;

            }

            $product_info = $this->entegrasyon->getProduct($product_id);


            if(!$product_info){

                echo json_encode(array('status'=>false,'message'=>'Ürün Kataloğunuzda bulunamadı'));

                return;

            }

            $category_setting=$this->entegrasyon->getMarketPlaceCategory($product_id,$code);
            $manufacturer_setting=$this->entegrasyon->getMarketPlaceManufacturer($product_info['manufacturer_id'],$code);
            $product_setting=$this->entegrasyon->getSettingData($code,'product',$product_id);
            $defaults=$this->entegrasyon->getDefaults($category_setting,$manufacturer_setting,$product_setting,$code);





            $commission =$defaults['commission'];


            $product_info = $this->entegrasyon->getProductForUpdate($code,$this->entegrasyon->getProduct($product_id),$commission,true,HTTPS_SERVER);

            $product_info['model']=$this->config->get($code.'_setting_model_prefix').$product_info['model'];;
            //  $result= $this->{$code}->updateBasic($product_info);


            $post_data['request_data']=$product_info;
            $post_data['market']=$this->model_entegrasyon_general->getMarketPlace($code);
            if(!$send){
                print_r($post_data);
                return;
            }




            if ($mode) {
                $result = $this->entegrasyon->clientConnect($post_data, 'update_all', $code, $debug, false);

            } else {
                $result = $this->entegrasyon->clientConnect($post_data, 'update_basic', $code, $debug, false);

            }

            // $result=$this->entegrasyon->clientConnect($post_data,'update_basic',$code,$debug,false);

            print_r($result);


        } }


    public function update_after_order()
    {

        if($this->config->get('easy_setting_update_after_oc_sale')) {
            $order_id=$this->request->post['order_id'];




            if ($order_id) {
                $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");
                $products = array();



                foreach ($product_query->rows as $product) {
                    $products[] = $product['product_id'];
                }
                $result = $this->entegrasyon->updateMarketplaceProdutcsAfterOrder($products, HTTPS_SERVER);

                //print_r($result);

            }
        }

    }

    public function logs()
    {

        if(isset($this->request->get['date'])){
            $date=   $this->request->get['date'];
        }else {
            $date=date("d.m.y");
        }
        $old=false;
        if(isset($this->request->get['old'])){
            $old=true;
        }
        echo "&date=".$date;

        echo '<html dir="ltr" lang="tr"><head><meta charset="UTF-8" /></head><body>';
        if ($old){
            $results=array();
            if ($file = fopen(DIR_LOGS.'easyentegre.log', "r")) {
                while(!feof($file)) {
                    $line = fgets($file);
                    $results[]=$line;
                    # do same stuff with the $line
                }
                fclose($file);
            }
        }else{
            $results=array();
            if ($file = fopen(DIR_LOGS.'easyentegre-'.$date.'.log', "r")) {
                while(!feof($file)) {
                    $line = fgets($file);
                    $results[]=$line;
                    # do same stuff with the $line
                }
                fclose($file);
            }
        }


        $results=array_reverse($results);

        echo implode('<br>',$results);

    }


    public function test()
    {

        $code = $this->request->get['code'];

        $this->load->model('entegrasyon/general');


        $this->load->model('entegrasyon/order/'.$code);
        $res =$this->{"model_entegrasyon_order_".$code}->getOrders();
        print_r($res);
        return;
    }


}

