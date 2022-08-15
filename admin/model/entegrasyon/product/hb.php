<?php

class ModelEntegrasyonProductHb extends Model {

    public function sendProduct($product_data,$selected_attributes=array(),$debug=false)
    {

        $status=false;
        $message='';

        /*
        $variants= $this->getVariants($product_data['product_id'],$product_data['category_id']);
        if($variants) {
            if($required_specs) {
                foreach ($required_specs as $row => $required_spec) {

                    if (in_array($required_spec, $variants['selected_attributes'])) {
                        unset($required_specs[$row]);
                    }
                }
            }
        }

*/


        if(!$selected_attributes) {
            if (isset($product_setting['selected_attributes'])) {
                $selected_attributes = $product_data['attributes'];
            } else {
                $selected_attributes = array();
            }
        }

        // $product_data['images']=$this->getImages($product_data['product_id'],$product_data['main_image']);
        $defaults=$product_data['defaults'];
        $post_data['request_data']=$product_data;
        $send['result']['errors'][]='hata 1';
        $send['result']['errors'][]='hata 2';

        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('hb');
        $send=$this->entegrasyon->clientConnect($post_data,'add_product','hb',$debug);


        if($send['status']){

            $status=true;
            $message.='Ürün Hepsiburada Mağazanıza Başarıyla Gönderildi, Ürününüz Hepsiburada tarafından inceleme yapıldıktan sonra satışa açılacaktır';

            $data=array('commission'=>$defaults['commission'],'sale_status'=>0,'approval_status'=>0, 'status'=>$send['result']['product_status'],'request_id'=>$send['result']['trackingId'],'product_id'=>$product_data['product_id'],'price'=>$product_data['sale_price'],0);
            $this->entegrasyon->addMarketplaceProduct($product_data['product_id'],$data,'hb');

            if($send['result']['errors']){
                $errors='';
                foreach ($send['result']['errors'] as $error) {

                    $errors.=','.$error;
                }
                $message='Ürün Gönderildi Ancak üründe şu hatalar tespit edildi; '.$errors.' Hepsiburada panelinden hataları düzetiniz.';
            }
            return array('status'=>$status,'message'=>$message,'price'=>$product_data['sale_price'].' TL');


        } else {

            return array('status'=>$status,'message'=>$send['message']);

        }

    }

    public function getExtraData($product_data)
    {

        return $product_data;

    }

    public function getImages($product_id,$main_image)
    {


        $catalog_url=$this->config->get('config_secure')?HTTPS_CATALOG:HTTP_CATALOG;
        $images = array();
        $images[] = $catalog_url . 'image/' . $main_image;
        $product_images = $this->entegrasyon->getProductImages($product_id);

        foreach ($product_images as $product_image) {
            if (is_file(DIR_IMAGE . $product_image['image'])) {
                $images[] = $catalog_url . 'image/' . $product_image['image'];

            }
        }

        return $images;

    }


    public function deleteProduct($product_id)
    {

        $this->load->model("entegrasyon/general");

        $this->entegrasyon->deleteMarketplaceProduct($product_id,'hb');

        return array('status' => true, 'message' => 'Ürün Entegrasyon Ayarlarından Silindi. Ürünü Trendyol mağazanızın paneline girerek tamamen silebilirsiniz.');


    }


    public function getProducts($data=array(),$debug=false)
    {

        error_reporting(E_ALL);
        ini_set('display_errors', 0);
        $this->load->model('entegrasyon/general');

        $message='';
        $status=true;
        $total=0;
        $products=array();
        $post_data['request_data']=array('itemcount'=>$data['itemcount'],'page'=>$data['page']);
        $post_data['market']=$this->model_entegrasyon_general->getMarketPlace('hb');


        $debug=false;
        $result=$this->entegrasyon->clientConnect($post_data,'get_products','hb',$debug);
        

        if(isset($result['result']['Listings']['Listing'])){

            $total=$result['result']['TotalCount'];

            if(isset($result['result']['Listings']['Listing']['hepsiburadaSku'])){

                $item=$result['result']['Listings']['Listing'];

                $products[]=array(
                    'market_id'=>$item['hepsiburadaSku'],
                    'model'=>$item['merchantSku'],
                    'product_code'=>$item['hepsiburadaSku'],
                    'quantity'=>$item['availableStock'],
                    'stock_code'=>$item['MerchantSku'],
                    'name'=>$item['merchantSku'],
                    'barcode'=>$item['merchantSku'],
                    'list_price'=>$item['price'],
                    'sale_price'=>$item['price'],
                    'sale_status'=>$item['isSalable'],
                    'approval_status'=>$item['hepsiburadaSku']?1:0,
                    'custom_data'=>$item

                );
            }else {
                foreach ($result['result']['Listings']['Listing'] as $item) {
                    if(isset($item['hepsiburadaSku'])){



                        $products[]=array(
                            'market_id'=>$item['hepsiburadaSku'],
                            'model'=>$item['merchantSku'],
                            'barcode'=>$item['merchantSku'],
                            'stock_code'=>$item['merchantSku'],
                            'list_price'=>$item['price'],
                            'quantity'=>$item['availableStock'],
                            'name'=>$item['merchantSku'],
                            'sale_price'=>$item['price'],
                            'sale_status'=>$item['isSalable'],
                            'approval_status'=>$item['hepsiburadaSku']?1:0,
                            'custom_data'=>$item

                        );
                    }
                }

            }

        }else {


            $message='Hepsiburada Ürününüz Mevcut değildir';

        }



        return array('status'=>$status,'total'=>$total,'message'=>$message,'products'=>$products);
    }




}
