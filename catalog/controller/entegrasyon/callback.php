<?php

class ControllerEntegrasyonCallback extends Controller
{

    private $req = '';

    public function __construct($registry)
    {

        parent::__construct($registry);
        $this->req = $registry;
    }

    public function index()
    {

        $data = $this->request->post['data'];

        print_r($data);
        echo 'OK';


    }


    public function updatebasic()
    {

        $this->req->set('easybulk', new Easybulk($this->req));
        $results = json_decode(html_entity_decode($this->request->post['data']), 1);



        foreach ($results as $result) {


        $request_info = $this->db->query("select * from " . DB_PREFIX . "es_request where req_id='" . $result['req_id'] . "'")->row;



            if ($request_info) {
                $result['service_type'] = 2;

                //if ($result['status']) {

                    // $this->db->query("delete from " . DB_PREFIX . "es_callback_result where product_code='" . $product_info['product_code'] . "'");
                    $marketplace_data = $this->entegrasyon->getMarketPlaceProductForMarket($request_info['product_id'], $request_info['code']);

                    $data=array(
                        'status'=>$result['status'],
                        'code'=>$request_info['code'],
                        'product_id'=>$request_info['product_id'],
                        'variant_id'=>$request_info['variant_id'],
                        'sale_price' =>$request_info['sale_price'],
                        'message' =>$result['messages']
                    );

                    if($marketplace_data){
                        $this->easybulk->updateAfterBasicUpdate($data,$marketplace_data);
                    }
                    //$this->updateMarketPlaceProduct($result);
                    // $marketplace_data['price'] = $product_info['sale_price'];
                    // $this->entegrasyon->addMarketplaceProduct($product_info['product_id'], $marketplace_data, $product_info['product_code']);

                    //return;
                }

                $this->db->query("delete from " . DB_PREFIX . "es_request where req_id='" . $result['req_id'] . "'");

            }






    }



    public function addandupdateerror($result)
    {


        $query = $this->db->query("select * from " . DB_PREFIX . "es_callback_result where product_code='" . $result['product_code'] . "' and code='" . $result['code'] . "'");

        if ($query->num_rows) {

            $attemp=$query->row['attemp']+1;

            $this->db->query("update " . DB_PREFIX . "es_callback_result SET messages='" . $this->db->escape(serialize($result['messages'])) . "',service_type='" . $result['service_type'] . "', date_modified=NOW() , attemp='".$attemp."' where product_code='" . $result['product_code'] . "' and code='".$result['code']."'");

        } else {
            try
            {
                $this->db->query("insert into " . DB_PREFIX . "es_callback_result SET product_code='" . $result['product_code'] . "', service_type='" . $result['service_type'] . "', code='" . $result['code'] . "', messages='" . $this->db->escape(serialize($result['messages'])) . "', attemp=1, date_created=NOW()  ");

            }catch (Exception $exception){
                print_r($exception);
            }
        }

    }


}