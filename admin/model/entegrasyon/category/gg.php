<?php
class ModelEntegrasyonCategoryGg extends Model {


    public function renderAttributes($result)
    {

        $attributes = array();
        $required=array();
        if (isset($result['result']['specs']['spec'])) {



            foreach ($result['result']['specs']['spec'] as $spec) {

                if($spec['varianter']){

                    $value_list=array();
                    $values=is_array($spec['specValues']['specValue'])?$spec['specValues']['specValue']:array(1=>$spec['specValues']['specValue']);


                    foreach ($values as $value) {

                        $value_list[]=$value['value'];
                      }

                    $attributes[] = array(
                        'name' => $spec['name'],
                        'values' =>$value_list ,
                        'type' => $spec['type'],
                        'required' => 1,
                        'varianter'=>1,
                    );

                        $required[]=$spec['name'];


                }else {
if($spec['required']){

                    $attributes[] = array(
                        'name' => $spec['name'],
                        'values' => is_array($spec['values']['value'])?$spec['values']['value']:array(1=>$spec['values']['value']),
                        'type' => $spec['type'],
                        'required' => $spec['required'],
                        'varianter'=>$spec['varianter'],
                    );


                    if((int)$spec['required']){
                        $required[]=$spec['name'];
                    }
}

                }

            }


        }


        $variants=array();

        foreach ($result['result']['specs']['spec'] as $spec) {

            if($spec['varianter']) {


                $value_list=array();
                $values=is_array($spec['specValues']['specValue'])?$spec['specValues']['specValue']:array(1=>$spec['specCalues']['specValue']);


                foreach ($values as $value) {

                    //$value_list[]=$value['value'];

                    $value_list[] = array(
                        'value_id' => $value['valueId'],
                        'name' => $value['value'],
                        'order_number' => $value['orderNumber']
                    );
                }


                $variants[] = array(
                    'name' => $spec['name'],

                    'id' => $spec['nameId'],
                    'order_number'=>$spec['orderNumber'],
                    'values' => $value_list
                );

            }


            }

        $attributes['variants']=$variants;


        $attributes['required_attributes']=$required;

        return $attributes;

    }



    public function getCategoryOptions($category_id)
    {

        $url="http://www.opencart.gen.tr/index.php?route=api/entegrasyon/gg_attributes&category_id=".$category_id;

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $result= curl_exec($ch);
            curl_close($ch);
            $options=json_decode($result);

        return  $options->options;

}


    }