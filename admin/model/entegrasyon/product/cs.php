<?php

class ModelEntegrasyonProductCs extends Model{



    public function sendProduct($product_data,$selected_attributes=array(),$debug=false)
    {


        $status=false;
        $message='';

        if(!$selected_attributes) {
            if (isset($product_setting['selected_attributes'])) {
                $selected_attributes = $product_data['attributes'];
            } else {
                $selected_attributes = array();
            }
        }


        $product_data['attributes']=$this->getAttributes($selected_attributes);

        // $product_data['images']=$this->getImages($product_data['product_id'],$product_data['main_image']);


        $defaults=$product_data['defaults'];

        $post_data['request_data']=$product_data;

        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('cs');



        $send=$this->entegrasyon->clientConnect($post_data,'add_product','cs',$debug,false);



        if($send['status']) {

            $status = true;
            $message .= $send['message'];

            $data = array('commission' => $defaults['commission'], 'message' => $message, 'sale_status' => 0, 'approval_status' => 0, 'product_id' => $product_data['product_id'], 'price' => $product_data['sale_price'], 0);
            if(isset($send['result']['batchId'])){
                $data['request_id'] = $send['result']['batchId'];
                $data['product_status']='İnceleniyor';
            }

            $this->entegrasyon->addMarketplaceProduct($product_data['product_id'], $data, 'cs');
            return array('status'=>$status,'message'=>$message,'price'=>$product_data['sale_price'].' TL');


        } else {

            return array('status'=>$status,'message'=>$send['message']);

        }

    }

    public function getExtraData($product_data)
    {

        return $product_data;

    }

    public function deleteProduct($product_id)
    {

        $this->load->model("entegrasyon/general");

        $this->entegrasyon->deleteMarketplaceProduct($product_id,'ty');

        return array('status' => true, 'message' => 'Ürün Entegrasyon Ayarlarından Silindi. Ürünü Trendyol mağazanızın paneline girerek tamamen silebilirsiniz.');


    }

    public function getProduct($product_id,$debug=false)
    {


        //    $product_id='EGEYELKEN31101918';

        $this->load->model('entegrasyon/general');


        $post_data['request_data'] = array('itemcount' => 1, 'page' => 1, 'barcode' => $product_id, 'approved' => true);

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('cs');

        $result = $this->entegrasyon->clientConnect($post_data, 'get_product', 'cs', $debug);




        $product=array();

        if(!$result['status']) {

            return $result;
        }



        if(!$result['status']) return $result;

        $item=$result['result']['products'][0];



        if($item['mainProductCode']){
            $model =$item['mainProductCode'];
        }else if($item['stockCode']){
            $model =$item['stockCode'];
        }else {

            $model=$item['productCode'];
        }


        $market_id=isset($item['productContentId']) ? $item['productContentId'] : false;
        $product = array(
            'market_id' => isset($item['link']) ? $item['link'] : false,
            'model' => $model,
            'stock_code'=>$item['stockCode'],
            'name' => $item['productName'],
            'list_price' => $item['salesPrice'],
            'barcode' => $item['barcode'],
            'sale_price' => $item['salesPrice'],
            'sale_status' => $item['isActive'],
            'quantity' => $item['stockQuantity'],
            'approval_status' => $item['productStatusType']=='YAYINDA'?1:0

        );


        return $product;

    }



    public function reset_stock($product_info)
    {

        $product_info['quantity']=0;
        $product_info['sale_price']=0;
        $product_info['list_price']=0;
        $product_info['price']=0;
        $post_data['request_data']=$product_info;
        $this->load->model('entegrasyon/general');

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('ty');
        $result=$this->entegrasyon->clientConnect($post_data,'update_basic','ty');


        if($result['status']){
            $this->deleteProduct($product_info['product_id']);
            return array('status'=>true);

        }else {
            $this->deleteProduct($product_info['product_id']);
            return array('status'=>true,'message'=>$result['message']);

        }

    }

    private function getAttributes($selected_attributes)
    {

        $attributes=array();


        foreach ($selected_attributes as $selected_attribute) {



            $val=explode('-',$selected_attribute['value']);
            if (isset($val[2])){
                if ( $val[1] == 2000283 || $val[1] == 2000305){
                    $attributes[]=array(
                        "id"=> $val[1],
                        "valueId"=> $val[2]
                    );
                }else{
                    $attributes[]=array(
                        "id"=> $val[1],
                        "valueId"=> $val[2],
                        "textLength"=> 55
                    );
                }




            }else{
                $attributes[]=array(
                    "id"=> $val[0],
                    "valueId"=> $val[1]
                    //"textLength"=> 0
                );

            }


        }

        return $attributes;

    }


    public function getImages($product_id,$main_image)
    {


        $catalog_url=$this->config->get('config_secure')?HTTPS_CATALOG:HTTP_CATALOG;
        $images = array();
        $images[] = $catalog_url . 'image/' . $main_image;
        $product_images = $this->entegrasyon->getProductImages($product_id);

        $i=1;
        foreach ($product_images as $product_image) {
            if($i<5) {
                if (is_file(DIR_IMAGE . $product_image['image'])) {
                    $images[] = $catalog_url . 'image/' . $product_image['image'];

                }
                $i++;
            }

        }

        return $images;

    }


    public function getProducts($data=array(),$debug=false)
    {

        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        $this->load->model('entegrasyon/general');
        $message='';
        $status=false;
        $total=0;
        $products=array();
        $products_uniq=array();
        $post_data['request_data']=array('itemcount'=>$data['itemcount'],'page'=>$data['page'],'approved'=>true);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('cs');

        $result=$this->entegrasyon->clientConnect($post_data,'get_products','cs',$debug,false);



        $pages=0;



        if($result['status']){
            $total=$result['result']['totalCount'];
            $pages=1;//$result['result']['totalPages'];
            if($result['result']['totalCount']>0){
                foreach ($result['result']['products'] as $item) {

                    if($item['mainProductCode']){
                        $model =$item['mainProductCode'];
                    }else if($item['stockCode']){
                        $model =$item['stockCode'];
                    }else {

                        $model=$item['productCode'];
                    }



                    if(!in_array($item['mainProductCode'],$products_uniq)) {
                        $product = array(
                            'market_id' => isset($item['productCode']) ? $item['productCode'] : false,
                            'link' => isset($item['link']) ? $item['link'] : false,
                            'model' => $model ,
                            'stock_code'=>$item['stockCode'],
                            'product_code'=>$item['productCode'],
                            'name' => $item['productName'],
                            'list_price' => $item['salesPrice'],
                            'barcode' => $item['barcode'],
                            'sale_price' => $item['salesPrice'],
                            'sale_status' => ($item['isActive'] && $item['productStatusType']=='YAYINDA')?1:0 ,
                            'quantity' => $item['stockQuantity'],
                            'approval_status' =>1, //$item['productStatusType']=='YAYINDA'?1:0,
                            'custom_data'=>$item

                        );


                        $products[] = $product;

                        $products_uniq[] = $model;

                    }
                }
            }

        }else {


            $message=$result['message'];

        }

        return array('status'=>$result['status'],'total'=>$total,'pages'=>$pages,'message'=>$message,'products'=>$products);
    }
    public function getMarketPlaceProduct($product_id,$category_id,$manufacturer_id)
    {

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=array('itemcount'=>1,'page'=>1,'barcode'=>$product_id,'approved'=>true);


        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('cs');

        $result=$this->entegrasyon->clientConnect($post_data,'get_product','cs',false);



        $language_id = $this->config->get('config_language_id');


        if(!$result['status']) {

            return $result;
        }

        if(!$result['status']) return $result;


        $product=$result['result']['products'][0];



        $product_title=$product['productName'];

        $product_description = array($language_id => array(
            'name' => $product_title,
            'description' => $product['description'],
            'meta_title' => $product_title,
            'meta_description' => $product_title,
            'meta_keyword' => $product_title,
            'tag' => array()//implode(',', explode(' ', $product_title)),

        ));


        $images=array();

        foreach ($product['images'] as $key => $image) {
            $ext = pathinfo($image['url'], PATHINFO_EXTENSION);


            if($this->checkUrl($image)){

                $images[] = array(

                    'image' => $this->entegrasyon->getImage($image, $product_title.'_'.$product['mainProductCode'] . '_' . $key),
                    'sort_order' => 0
                );;
            }


        }

        //  $stockData = $this->getOptionsFromN11($product['stockItems']['stockItem']);



        if($product['listPrice']>$product['salesPrice']){

            $price=$product['listPrice'];
            $product_special = array(0=>array(
                'customer_group_id'=>$this->config->get('config_customer_group_id'),
                'priority'=>0,
                'date_start'=>'',
                'date_end' =>'',
                'price'=>$product['salesPrice']

            ));
        }else {

            $price = $product['salesPrice'];

            $product_special=array();
        }


        if($product['mainProductCode']){
            $model =$product['mainProductCode'];
        }else if($product['stockCode']){
            $model =$product['stockCode'];
        }else {

            $model=$product['productCode'];
        }
        $product_data = array(
            'model' => $model,
            'sku' => '',
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => '',
            'location' => '',
            'quantity' => $product['stockQuantity'],
            'minimum' => 1,
            'keyword' => $this->entegrasyon->createSEOKeyword($product_title).'_'.$product_id.".html",
            'subtract' => 1,
            'image' => $images[0]['image'],
            'product_image' => $images,
            'product_category' =>$category_id?array($category_id):array(),
            'product_special'=>$product_special,
            'stock_status_id' => 2,
            'date_available' => '',
            'manufacturer_id' => $manufacturer_id?$manufacturer_id:"",
            'shipping' => 1,
            'price' => $price,
            'points' => '',
            'length' => '',
            'weight' => '',
            'width' => '',
            'height' => '',
            'weight_class_id' => 1,
            'length_class_id' => 1,
            'height_class_id' => 1,
            'status' => 1,
            'tax_class_id' => '',
            'sort_order' => '',
            'product_description' => $product_description,
            'product_store' => array(0)
        );

        if($this->config->get('cs_setting_barkod_place')){
            $barcode_place = $this->config->get('cs_setting_barkod_place');
            $product_data[$barcode_place] = $product['barcode'];

        }else{

            $product_data['ean'] = $product['barcode'];
        }


        $url=$this->entegrasyon->getMarketPlaceUrl('cs',$product['link']);

        $marketplace_product_data=array('sale_status'=>$product['isActive'],'approval_status'=>1,'commission'=>0,'product_id'=>$product['StockCode'],'price'=>$price,'url'=>$url);


        $marketplace_product_data['cs_category_id']=$product['pimCategoryId'].'|'.$product['categoryName'];
        $marketplace_product_data['product_id']=$product_id;

        return array('status'=>$result['result']['products']?true:false,'product_data'=>$product_data,'marketplace_product_data'=>$marketplace_product_data);


    }


    private function findImg($str)
    {

        preg_match_all('/(http|https):\/\/[^ ]+(\.gif|\.jpg|\.jpeg|\.png)/',$str, $out);

        return $out[0][0];

    }


    public function checkUrl($url)
    {
        //$url='https://cdn.dsmcdn.com//ty5/product/media/images/20200622/12/3284861/61173429/1/1_org.jpg';

        $status=true;
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);



        if($httpCode != 200 ) {
            /* Handle 404 here. */
            $status=false;

        }

        curl_close($handle);


        return $status;
    }


    public function imageControl($image)
    {


        $bosluk = strpos($image, ' ');

        if ($bosluk) {
            if (is_file(DIR_IMAGE . $image)) {

                $degisti=$this->imageTemizle($image);



                $produduct_image = $this->db->query("select * from " . DB_PREFIX . "product WHERE image='" . $image . "' ");

                if ($produduct_image->num_rows) {

                    $product_id = $produduct_image->row['product_id'];

                    $this->db->query("UPDATE " . DB_PREFIX . "product SET image='" . $degisti . "' WHERE product_id=" . (int)$product_id . " ");

                    rename(DIR_IMAGE.$image, DIR_IMAGE.$degisti);

                    return $degisti;
                } else {

                    $produduct_image = $this->db->query("select * from " . DB_PREFIX . "product_image WHERE image='" . $image . "' ");


                    if ($produduct_image->num_rows)

                        $image_id = $produduct_image->row['product_image_id'];

                    $this->db->query("UPDATE " . DB_PREFIX . "product_image SET image='" . $degisti . "' WHERE product_id='" . (int)$image_id . "' ");

                    rename(DIR_IMAGE.$image, DIR_IMAGE.$degisti);

                    return $degisti;

                }
            }
        }else {

            return $image;

        }


    }








    private function searchName($name,$attributes)
    {

        foreach ($attributes as $attribute){

            if($this->entegrasyon->replaceSpace($attribute['name'])==$this->entegrasyon->replaceSpace($name)){

                return $attribute['values'];

            }


        }

    }


    private function searchValue($value,$valuesArray)
    {


        foreach ( $valuesArray as $item) {
            $val1=$this->entegrasyon->replaceSpace($item['name']);
            $findMe=$this->entegrasyon->replaceSpace($value);


            $pos=stripos($val1,$findMe);

            //  echo $val1.'-'.$findMe.'<br>';


            if($pos!==false){

                return $item['id'];

            }

        }

    }




}