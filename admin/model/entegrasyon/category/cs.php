<?php
class ModelEntegrasyonCategoryCs extends Model {


    public function renderAttributes($result)
    {
        $attributes = array();
        $required=array();
        $cs_attributes=$result['result'];
        $variants=array();





        if(isset($cs_attributes['categoryAttributes'])) {
            $ix = 0;

            foreach ($cs_attributes['categoryAttributes'] as $attr) {

                $attributeValues = array();
                foreach ($attr['attributeValues'] as $attributeValue) {
                    $attributeValues[] = array(
                        'id' => $attr['attributeId'] . '-' . $attributeValue['id'],
                        'name' => $attributeValue['name']
                    );
                }

                $attributes[] = array(
                    'name' => $attr['attributeName'],
                    'values' => $attributeValues,
                    'varianter'=>$attr['varianter'],
                    'type' => $attr['type'],
                    'required' => $attr['required']
                );


                if ($attr['required']) {
                    $required++;
                    $required[] = $attr['attributeName'];
                }


                if($attr['varianter']) {
                    $values=array();
                    foreach ($attr['attributeValues'] as $atr_val) {

                        $values[] = array(
                            'value_id' => $atr_val['id'],
                            'name' => $atr_val['name'],
                            'order_number' => 1
                        );
                    }

                    $variants[] = array(
                        'name' => $attr['attributeName'],
                        'id' => $attr['attributeId'],
                        'values' => $values
                    );
                }

            }
        }

      
        $attributes['required_attributes']=$required;
        $attributes['variants']=$variants;




        return $attributes;

    }



    /*
        public function getCategoryOptions($category_id)
        {
            $options = array();
            $surl = 'https://api.trendyol.com/sapigw/product-categories/'.$category_id.'/attributes';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$surl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
            $attributes = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if(isset($attributes['categoryAttributes'])){


                foreach ($attributes['categoryAttributes'] as $attr) {
                    if($attr['varianter']) {
                        $values=array();
                    foreach ($attr['attributeValues'] as $atr_val) {


                        $values[] = array(
                            'value_id' => $atr_val['id'],
                            'name' => $atr_val['name'],
                            'order_number' => 1
                        );
                    }



                    $options[] = array(
                        'name' => $attr['attribute']['name'],

                        'id' => $attr['attribute']['id'],
                        'values' => $values
                    );
                }
            }

                return $options;
            }else {

                return array();
            }


        }
    */



}