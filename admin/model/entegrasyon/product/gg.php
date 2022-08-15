<?php

class ModelEntegrasyonProductGg extends Model
{

    public function sendProduct($product_data,$attributes=array(),$debug=false)
    {

        $status = false;


        // print_r($images);
        // return;
        $defaults = $product_data['defaults'];
        $product_data=$this->getExtraData($product_data);

        $message = '';


        $post_data['request_data']=array($product_data);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('gg');
        $send=$this->entegrasyon->clientConnect($post_data,'add_product','gg',$debug);



        if ($send['status']) {
            $gg_id = $send['result']['productId'];
            $url = 'http://urun.gittigidiyor.com/' . $gg_id;
            $status = true;
            $message .= 'Ürün Gittigidiyor Mağazanıza Başarıyla Gönderildi.';

            $data = array('commission' => $defaults['commission'],'sale_status'=>1,'approval_status'=>1, 'product_id' => $gg_id, 'price' => $product_data['sale_price'], 'url' => $url);
            $this->entegrasyon->addMarketplaceProduct($product_data['product_id'], $data, 'gg');
            return array('status' => $status, 'message' => $message, 'price' => $product_data['sale_price'] . ' TL', 'url' => $url);

        } else {

            return $send;

        }


    }

    public function getExtraData($product_data)
    {
        if ($product_data['defaults']['shipping_time'] == 'today') {

            $shippingTime = array('days' => $product_data['defaults']['shipping_time'], 'beforeTime' => $product_data['defaults']['hour'] . ':' . $product_data['defaults']['minute']);

        } else {

            $shippingTime = array('days' => $product_data['defaults']['shipping_time']);

        }

        if ($product_data['defaults']['shipping_template'] == 'S') {

            $kargoDetail = array('cargoCompanyDetail' => array('name' => $product_data['defaults']['shipping_company']));

        } else {

            $kargoDetail = array(
                'cargoCompanyDetail' => array(
                    'name' => $product_data['defaults']['shipping_company'],
                    'cityPrice' => $this->config->get('gg_setting_extra_shipping_price'),
                    'countryPrice' => $this->config->get('gg_setting_extra_shipping_price')
                ));

        }

        // $images = $this->getImages($product_data['product_id'], $product_data['main_image']);
        // $product_data['images']=$images;
        $product_data['kargoDetail']=$kargoDetail;
        $product_data['shippingTime']=$shippingTime;

        return $product_data;
    }

    public function getProduct($product_id,$debug=false)
    {


        //$product_id='07061761';

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=array('product_id'=>$product_id);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('gg');

        $result=$this->entegrasyon->clientConnect($post_data,'get_product','gg',$debug);


//print_r($result['result']['productDetail']);
//return;
        $product=array();

        if(!$result['status']) {

            return $result;
        }



        if(!$result['status']) return $result;

        $item=$result['result']['productDetail'];
        $product = array(
            'market_id' => $item['productId'],
            'name' => $item['product']['title'],
            'barcode' => isset($item['itemId'])?$item['itemId']:$item['productId'],
            'stock_code'=>isset($item['itemId'])?$item['itemId']:$item['productId'],
            'model' => isset($item['itemId'])?$item['itemId']:$item['productId'],
            'list_price' => $item['product']['buyNowPrice'],
            'sale_price' => $item['product']['buyNowPrice'],
            'sale_status' => 1,
            'approval_status' => 1

        );


        return $product;

    }


    private function getVariants($variant_list){


        $variants=array();

        foreach ($variant_list['options'] as $option) {
            $option_attr=explode('|',$option['order_number']);

            $alias='';
            $valueId=0;
            $nameId=0;

            $spec=array();
            foreach ($option['attributes'] as $key => $attribute) {


                $option_info=explode('-',$option_attr[$key]);
                $option_name=$option_info[0];
                $option_order_number=$option_info[1];
                if($key==0){
                    $alias=$option_name.':'.$attribute['name'];
                    $valueId=$attribute['attributeValueId'];
                    $nameId=$attribute['attributeId'];
                }
                $spec[] = array('nameId' => $attribute['attributeId'], 'name' => $option_name, 'valueId' => $attribute['attributeValueId'], 'value' => $attribute['name'], 'orderNumber' => $option_order_number, 'specDataOrderNumber' => $attribute['order_number']);

            }


            $varitemp=array(

                'alias'=>$alias,
                'valueId'=>$valueId,
                'nameId'=>$nameId,

                'variants'=>array('variant'=>array(
                    'quantity'=>$option['quantity'],
                    'variantSpecs'=>$spec
                ))
            );

            $variants['variantGroup'][]=$varitemp;


        }


        return $variants;


    }


    public function deleteProduct($product_id)
    {

        $this->load->model("entegrasyon/general");

        $query = $this->db->query("select gg from " . DB_PREFIX . "es_product_to_marketplace where product_id='" . $product_id . "'");
        if ($query->num_rows) {
            $row = $query->row;
            $product_info = unserialize($row['gg']);
        }

        $products[] = $product_info['product_id'];
        $post_data['request_data']=$products;

        $post_data['market'] = $this->model_entegrasyon_general->getMarketPlace('gg');
        $result=$this->entegrasyon->clientConnect($post_data,'delete_product','gg',false);



        if ($result['status']) {

            $this->entegrasyon->deleteMarketplaceProduct($product_id, 'gg');
            return array('status' => true, 'message' => 'Ürün Gittigidiyor Mağazasından Silindi.');


        } else {

            return $result;
        }




    }


    public function getImages($product_id, $main_image)
    {

        $catalog_url = HTTP_CATALOG;
        $catalog_url = str_replace('https', 'http', $catalog_url);

        //$this->load->model('catalog/product');
        //  $this->load->model('entegrasyon/general');

        $product_images = $this->entegrasyon->getProductImages($product_id);

        $images = array();
        $images['photo'] [] = array('url' => $catalog_url . 'image/' . $this->entegrasyon->imageControl($main_image),
            'photoId' => 0);

        $sort = 1;
        foreach ($product_images as $product_image) {
            if ($sort < 8) {
                if (is_file(DIR_IMAGE . $this->entegrasyon->imageControl($product_image['image']))) {
                    $images['photo'][] = array(
                        'url' => $catalog_url . 'image/' . $this->entegrasyon->imageControl($product_image['image']),
                        'photoId' => $sort
                    );
                    $sort++;
                }
            }
        }


        return $images;

    }


    public function getProducts($data = array(),$debug=false)
    {

        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        $message = '';
        $status = false;
        $total = 0;
        $products = array();
        $this->load->model('entegrasyon/general');

        //$result = $this->gg->getProducts($data['page'] * 0, $data['itemcount'], 'A', true);
        if(!$this->config->get('gg_setting_product_status')){

            $status='A';
            $sale_status=1;

        }else {

            $status='U';
            $sale_status=0;
        }

        $post_data['request_data']=array($data['page']*100, $data['itemcount'], $status, true);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('gg');

        $result=$this->entegrasyon->clientConnect($post_data,'get_products','gg',$debug);


        if ($result['status']) {


            $total = $result['result']['productCount'];
            if($result['result']['productCount']>0){
                $render= isset($result['result']['products']['product'][0])?$result['result']['products']['product']:$result['result']['products'];
                foreach ($render as $item) {




                    $products[] = array(
                        'market_id' => $item['productId'],
                        'name' => $item['product']['title'],
                        'product_code'=>$item['productId'],

                        'quantity'=>isset($item['productCount'])?$item['productCount']:0,
                        'barcode' => isset($item['itemId'])?$item['itemId']:$item['productId'],
                        'stock_code'=>isset($item['itemId'])?$item['itemId']:$item['productId'],
                        'model' => isset($item['itemId'])?$item['itemId']:$item['productId'],
                        'list_price' => $item['product']['buyNowPrice'],
                        'sale_price' => $item['product']['buyNowPrice'],
                        'sale_status' => $sale_status,
                        'approval_status' => 1,
                        'custom_data'=>$item

                    );

                }}
        }

        //print_r($products);
        //return ;

        return array('status' => $result['status'], 'total' => $total, 'message' => $result['message'], 'products' => $products);
    }


    public function getMarketPlaceProduct($product_id,$category_id,$manufacturer_id,$debug=false)
    {

        $this->load->model('entegrasyon/general');

        $post_data['request_data']=array('product_id'=>$product_id);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('gg');

        $result=$this->entegrasyon->clientConnect($post_data,'get_product','gg',$debug);

        
        if($debug){

            print_r($result);
            return ;

        }

        if(!$result['status']) {

            return $result;
        }
        $language_id = $this->config->get('config_language_id');

        if(!$result['status']) return $result;


        $product=$result['result']['productDetail'];
        $product_title=$product['product']['title'];

        $product_description = array($language_id => array(
            'name' => $product_title,
            'description' => $product['product']['description'],
            'meta_title' => $product_title,
            'meta_description' => $product_title,
            'meta_keyword' => $product_title,
            'tag' =>array()// implode(',', explode(' ', $product['title'])),

        ));



        $images=array();
        if (isset($product['product']['photos']['photo']['url'])) {

            $image = $product['product']['photos']['photo'];
            $images[] = array(

                'image' => $this->entegrasyon->getImage($image['url'], $product_title),
                'sort_order' => 0
            );
        } else {
            foreach ($product['product']['photos']['photo'] as $key => $image) {
                $images[] = array(

                    'image' => $this->entegrasyon->getImage($image['url'], $product_title . '_' . $key),
                    'sort_order' => 0
                );
            }
        }



        //  $stockData = $this->getOptionsFromN11($product['stockItems']['stockItem']);

        $price = $product['product']['buyNowPrice'];


        $product_data = array(
            'model' => isset($product['itemId'])?$product['itemId']:$product['productId'],
            'sku' => '',
            'upc' => '',
            'ean' => '',
            'jan' => '',
            'isbn' => '',
            'mpn' => '',
            'location' => '',
            'quantity' => $product['product']['productCount'],
            'minimum' => 1,
            'keyword' => $this->entegrasyon->createSEOKeyword($product_title).'_'.$product_id.".html",
            'subtract' => 1,
            'image' => $images[0]['image'],
            'product_image' => $images,
            'product_category' =>$category_id?array($category_id):array(),
            'product_special'=>array(),
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


        if($this->config->get('gg_setting_barkod_place')){
            $barcode_place = $this->config->get('gg_setting_barkod_place');
            $product_data[$barcode_place] = isset($product['itemId'])?$product['itemId']:$this->entegrasyon->createSEOKeyword($product_title);
        }else{
            $product_data['ean'] = isset($product['itemId'])?$product['itemId']:$this->entegrasyon->createSEOKeyword($product_title);

        }
        $url=$this->entegrasyon->getMarketPlaceUrl('gg',$product_id);

        $marketplace_product_data=array('sale_status'=>$product['summary']['listingStatus']=='A'?1:0,'approval_status'=>1,'commission'=>0,'product_id'=>$product_id,'price'=>$price,'url'=>$url);

        $marketplace_product_data['gg_category_id']=$product['product']['categoryCode'].'|'.$product_title;
        $marketplace_product_data['product_id']=$product_id;
        return array('status'=>true,'product_data'=>$product_data,'marketplace_product_data'=>$marketplace_product_data);


    }


}