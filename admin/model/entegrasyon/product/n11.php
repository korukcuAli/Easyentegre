<?php

class ModelEntegrasyonProductN11 extends Model{


    public function sendProduct($product_data,$selected_attributes=array(),$debug=false)
    {

        $status=false;
        $message='';

        /*
                    if (isset($product_setting['selected_attributes'])) {
                        $selected_attributes = $product_data['product_setting']['selected_attributes'];
                    } else {
                        $selected_attributes = array();
                    }*/

        
        if(!$selected_attributes){

            $selected_attributes =  $product_data['attributes'];
        }


        //$product_images=$this->getImages($product_data['product_id'],$product_data['main_image']);


        if(!$product_data['defaults']['shipping_template']){
            $message.='En az bir teslimat şablonu seçmelisiniz.';
            return array('status'=>$status,'message'=>$message);
        }



       /* $this->load->model('entegrasyon/category');
        /*$category_attiribute =  $this->model_entegrasyon_category->getAttributesFromDb($product_data['category_id'], 'n11');

        if (!$category_attiribute){
            $product_data['manufacturer'] = '';

        }*/


       // print_r($product_data);return;

        $post_data['request_data']=$product_data;
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');




        $send=$this->entegrasyon->clientConnect($post_data,'add_product','n11',$debug);



        if ($send['status']) {
            $n11_id=$send['result']['product']['id'];
            $status=true;
            $message.=$product_data['title'].' N11 Mağazanızda Başarıyla Listelendi.';
            $price=$product_data['sale_price'];
            $url=$this->entegrasyon->getMarketPlaceUrl('n11',$n11_id);
            $data=array('stock_id'=>$send['result']['product']['stockItems']['stockItem']['id'],'sale_status'=>$send['result']['product']['saleStatus'],'approval_status'=>1,'commission'=>$product_data['defaults']['commission'],'product_id'=>$n11_id,'price'=>$price,'url'=>$url);
            $this->entegrasyon->addMarketplaceProduct($product_data['product_id'],$data,'n11');
            return array('status'=>$status,'sale_status'=>$send['result']['product']['saleStatus'],'message'=>$message,'price'=>$price.' TL','url'=>$url);
            // $sonuc = $this->model_n11_product->addN11Product($product_id, $saveProduct->product);
            // $id = $saveProduct->product->id;
            //

        }else {

            return array('status'=>$status,'message'=>$send['message']);

        }

        //echo $n11_category_name;

    }

    
    public function getExtraData($product_data)
    {

        return $product_data;

    }

    public function deleteProduct($product_id)
    {
        $this->load->model("entegrasyon/general");

        $product_info = $this->entegrasyon->getProduct($product_id);
        $post_data['request_data']=$this->config->get('n11_setting_model_prefix').$product_info['model'];
        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('gg');
        $deleteProduct=$this->entegrasyon->clientConnect($post_data,'delete_product','n11');

        if ($deleteProduct['status']) {

            $this->entegrasyon->deleteMarketplaceProduct($product_id,'n11');
            return array('status' => true, 'message' => 'Ürün n11 mağazasından silindi.');

        } else  if($deleteProduct['message']=='ürün bulunamadı'){
            $this->entegrasyon->deleteMarketplaceProduct($product_id,'n11');
            return array('status' => true, 'message' => 'Ürün n11 mağazasından silindi.');

        }else {

            return array('status' => false, 'type'=>0, 'message' => $deleteProduct['message']);
        }

        // return array('status' => false, 'message' => $deleteProduct->result->errorMessage);
        //  $this->entegrasyon->deleteMarketplaceProduct($product_id,'n11');


    }



    private function getImages($product_id,$main_image){


        $product_images = $this->entegrasyon->getProductImages($product_id);
        $images['image'] = array();
        $images['image'][] = array(
            'url' => HTTPS_CATALOG . 'image/' . $main_image,
            'order' => 1);
        $sort = 2;
        foreach ($product_images as $product_image) {
            if (is_file(DIR_IMAGE . $product_image['image'])) {
                $images['image'][] = array(
                    'url' => HTTPS_CATALOG . 'image/' . $product_image['image'],
                    'order' => $sort
                );
                $sort++;
            }
        }

        return $images;
    }

    public function getProductTest($productSellerCode)
    {

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=$productSellerCode;
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        $result=$this->entegrasyon->clientConnect($post_data,'get_product','n11');


        // print_r($result);
    }


    public function getProducts($data=array(),$debug=false)
    {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);

        $message='';
        $status=true;
        $total=0;
        $products=array();
        $this->load->model('entegrasyon/general');


        $post_data['request_data']=$data;
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        $result=$this->entegrasyon->clientConnect($post_data,'get_products','n11',$debug);

       // print_r($result);
       // return ;


        if($result['status']){

            $total=$result['result']['pagingData']['totalCount'];

            if($result['result']['pagingData']['totalCount']>1){

                foreach ($result['result']['products']['product'] as $item) {





                    $stock_id=0;
                    $quantity=0;
                    if(isset($item['stockItems']['stockItem']['quantity'])){

                        $quantity=$item['stockItems']['stockItem']['quantity'];
                        $stock_id=$item['stockItems']['stockItem']['id'];

                    }else {

                        if(isset($item['stockItems']['stockItem'])){



                            foreach ($item['stockItems']['stockItem'] as $stockItem) {
                                $stock_id=$stockItem['id'];
                                $quantity+=$stockItem['quantity'];

                            } }
                    }


                    $products[]=array(
                        'market_id'=>$item['id'],
                        'product_code'=>$item['id'],

                        'name' =>$item['title'],
                        'stock_id'=>$stock_id,
                        'model'=>$item['productSellerCode'],
                        'stock_code'=>$item['productSellerCode'],
                        'barcode'=>$item['productSellerCode'],
                        'list_price'=>$item['price'],
                        'quantity'=>$quantity,
                        'sale_price'=>$item['displayPrice'],
                        'sale_status'=>$item['approvalStatus']==2?0:$item['saleStatus'],
                        'approval_status'=>$item['approvalStatus'],
                        'custom_data'=>$item

                    );



                }

            } else {

                $status=false;
                $message='N11 Mağazanızda Ürün Bulunamadı!';

            }

        }else {

            $message=$result['message'];

        }


        //print_r($products); return;


        return array('status'=>$status,'total'=>$total,'message'=>$message,'products'=>$products);


    }


    public function getProduct($product_id,$debug=false)
    {


     // $product_id='11d0asdasd419051545';

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=array('product_id'=>$product_id);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        $result=$this->entegrasyon->clientConnect($post_data,'get_product','n11',$debug);



        $product=array();

        if(!$result['status']) {

          return $result;

        }



        if(!$result['status']) return $result;

        $item=$result['result']['product'];


        $stock_id=0;
        $quantity=0;
        if(isset($item['stockItems']['stockItem']['quantity'])){

            $quantity=$item['stockItems']['stockItem']['quantity'];
            $stock_id=$item['stockItems']['stockItem']['id'];

        }else {

            if(isset($item['stockItems']['stockItem'])){



                    $stock_id=$item['stockItems']['stockItem']['id'];
                    $quantity+=$item['stockItems']['stockItem']['quantity'];

                }
        }



        $market_id=isset($item['productContentId']) ? $item['productContentId'] : false;
        $product = array(
            'market_id'=>$item['id'],
            'name' =>$item['title'],
            'stock_id'=>$stock_id,
            'model'=>$item['productSellerCode'],
            'stock_code'=>$item['productSellerCode'],
            'barcode'=>$item['productSellerCode'],
            'list_price'=>$item['price'],
            'quantity'=>$quantity,
            'sale_price'=>$item['displayPrice'],
            'sale_status'=>$item['saleStatus'],
            'approval_status'=>$item['approvalStatus']

        );


        return $product;

    }

    public function getMarketPlaceProduct($product_id,$category_id,$manufacturer_id,$debug=false)
    {

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=array('product_id'=>$product_id);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('n11');
        $result=$this->entegrasyon->clientConnect($post_data,'get_product','n11',$debug);




        if($debug){

            print_r($result);
            return ;

        }

        if(!$result['status']){

            return $result;
        }

        $language_id = $this->config->get('config_language_id');

        if(!$result['status']) {

            return $result;
        }


        $product=$result['result']['product'];
        $product_description = array($language_id => array(
            'name' => $product['title'],
            'description' => $product['description'],
            'meta_title' => $product['title'],
            'meta_description' => $product['title'],
            'meta_keyword' => $product['title'],
            'tag' =>array()// implode(',', explode(' ', $product['title'])),

        ));


        if (isset($product['images']['image']['url'])) {

            $image = $product['images']['image'];
            $images[] = array(

                'image' => $this->entegrasyon->getImage($image['url'], $product['title']),
                'sort_order' => 0
            );
        } else {
            foreach ($product['images']['image']as $key => $image) {
                $images[] = array(

                    'image' => $this->entegrasyon->getImage($image['url'], $product['title'] . '_' . $key),
                    'sort_order' => 0
                );
            }
        }


        $stockData = $this->getOptionsFromN11($product['stockItems']['stockItem']);


        $price = $product['displayPrice'];





        $product_price=$this->getProductPrice($product);


        //$special=$special_price?($special_price[0]['price']==$price?array():$special_price):array();

        $product_data = array(
            'model' => $product['productSellerCode'],
            'sku' => '',
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => $stockData['gtin'],
            'location' => '',
            'quantity' => $stockData['quantity'],
            'minimum' => 1,
            'keyword' => $this->entegrasyon->createSEOKeyword($product['title']).".html",
            'subtract' => 1,
            'image' => $images[0]['image'],
            'product_image' => $images,
            'product_category' =>$category_id?array($category_id):array(),
            'product_special'=>array(),
            'stock_status_id' => 2,
            'date_available' => '',
            'manufacturer_id' => $manufacturer_id?$manufacturer_id:"",
            'shipping' => 1,
            'price' => $product_price['list_price'],
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

        if($product_price['list_price']>$product_price['sale_price']){

            $product_data['product_special'] = array(0=>array(
                'customer_group_id'=>$this->config->get('config_customer_group_id'),
                'priority'=>0,
                'date_start'=>'',
                'date_end' =>'',
                'price'=>$product_price['sale_price']

            ));
        }


        if($this->config->get('n11_setting_barkod_place')){
            $barcode_place = $this->config->get('n11_setting_barkod_place');
            $product_data[$barcode_place] = $product['productSellerCode'];

        }else{
            $product_data['ean'] = $product['productSellerCode'];

        }


        $url=$this->entegrasyon->getMarketPlaceUrl('n11',$product['id']);

        $marketplace_product_data=array('stock_id'=>$stockData['stock_id'],'sale_status'=>$product['saleStatus'],'approval_status'=>$product['approvalStatus'],'commission'=>0,'product_id'=>$product['id'],'price'=>$price,'url'=>$url);

        $marketplace_product_data['n11_category_id']=$product['category']['id'].'|'.$product['category']['fullName'];
        $marketplace_product_data['product_id']=$product_id;
        return array('status'=>true,'product_data'=>$product_data,'marketplace_product_data'=>$marketplace_product_data);


    }

    public function getOptionsFromN11($n11Options)
    {

        $data = array();

        $quantity = 0;
        $gtin = 0;
        $stock_id=0;



        if (isset($n11Options['quantity'])) {

            $quantity = $n11Options['quantity'];
            $stock_id= $n11Options['id'];
            $gtin = isset($n11Options['gtin']) ? $n11Options['gtin'] : 0;

        } else {

            // if(!$stock_id)$stock_id= $n11Options['id'];

            foreach ($n11Options as $n11Option) {

                $quantity += $n11Option['quantity'] ? $n11Option['quantity'] : 0;
                $gtin = isset($n11Options['gtin']) ? $n11Options['gtin'] : 0;

            }

        }

        $data['quantity'] = $quantity;
        $data['gtin'] = $gtin;
        $data['stock_id'] = $stock_id;

        return $data;

    }


    public function getProductPrice($product)
    {


        $n11Options=$product['stockItems']['stockItem'];

        if (isset($n11Options['currencyAmount'])) {

            $list_price = $n11Options['currencyAmount'];
            $sale_price = $n11Options['displayPrice'];

        } else {

            // if(!$stock_id)$stock_id= $n11Options['id'];

           // foreach ($n11Options as $n11Option) {

                $list_price = $n11Options[0]['currencyAmount'];
                $sale_price = $n11Options[0]['displayPrice'];

           // }

        }






        return array('list_price'=>$list_price,'sale_price'=>$sale_price);



        // return $product_special;


    }

    // }




}