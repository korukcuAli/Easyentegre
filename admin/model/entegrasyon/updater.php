<?php
class ModelEntegrasyonUpdater extends Model {

    private $error=array();
    private $url='https://www.opencart.gen.tr/index.php?route=api/';
    private $branch_version = "1.0.1";
    private $new_version;

    public function updateTest() {
        $this->error = array();

        //$this->entegrasyon->log('Starting update test');

        if (!function_exists("exception_error_handler")) {
            function exception_error_handler($errno, $errstr, $errfile, $errline ) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        }

        set_error_handler('exception_error_handler');

        // create a tmp folder
        if (!is_dir(DIR_DOWNLOAD . '/tmp')) {
            try {
                mkdir(DIR_DOWNLOAD . '/tmp');
            } catch(ErrorException $ex) {
                $this->error[] = $ex->getMessage();
            }
        }

        // create tmp file
        try {
            $tmp_file = fopen(DIR_DOWNLOAD . '/tmp/test_file.php', 'w+');
        } catch(ErrorException $ex) {
            $this->error[] = $ex->getMessage();
        }

        // open and write over tmp file
        try {
            $output  = '<?php' . "\n";
            $output  .= '$test = \'12345\';' . "\n";
            $output  .= 'echo $test;' . "\n";

            fwrite($tmp_file, $output);
            fclose($tmp_file);
        } catch(ErrorException $ex) {
            $this->error[] = $ex->getMessage();
        }

        // try and read the file

        // remove tmp file
        try {
            unlink(DIR_DOWNLOAD . '/tmp/test_file.php');
        } catch(ErrorException $ex) {
            $this->error[] = $ex->getMessage();
        }

        // delete tmp folder
        try {
            rmdir(DIR_DOWNLOAD . '/tmp');
        } catch(ErrorException $ex) {
            $this->error[] = $ex->getMessage();
        }

        // reset to the OC error handler
        restore_error_handler();

        //  $this->openbay->log('Finished update test');

        if (!$this->error) {
            // $this->openbay->log('Finished update test - no errors');
            return array('error' => 0, 'response' => '', 'percent_complete' => 20, 'status_message' => $this->language->get('text_check_new'));
        } else {
            // $this->openbay->log('Finished update test - errors: ' . print_r($this->error));
            return array('error' => 1, 'response' => $this->error);
        }
    }

    public function updateCheckVersion($beta = 0) {
        $current_version = $this->config->get('module_entegrasyon_version');



        //  $this->openbay->log('Start check version, beta: ' . $beta . ', current: ' . $current_version);

        $post = array('version' => $this->branch_version, 'beta' => $beta);


        $data = $this->call('update/version/', $post);




        if ($this->lasterror == true) {
            // $this->openbay->log('Check version error: ' . $this->lastmsg);

            return array('error' => 1, 'response' => $this->lastmsg . ' (' . VERSION . ')');
        } else {
            if ($data['version'] > $current_version) {
                $this->new_version=$data['version'];
                $this->load->model('setting/setting');
                $saved_data=array('module_entegrasyon_new_version'=>$this->new_version);
                $this->model_setting_setting->editSetting('module', $saved_data);

                // $this->openbay->log('Check version new available: ' . $data['version']);
                return array('error' => 0, 'response' => $data['version'], 'percent_complete' => 40, 'status_message' => $this->language->get('text_downloading'));
            } else {
                //  $this->openbay->log('Check version - already latest');
                return array('error' => 1, 'response' => $this->language->get('text_version_ok') . $current_version);
            }
        }
    }

    public function updateDownload($beta = 0) {
        //$this->openbay->log('Downloading');


        $local_file = DIR_DOWNLOAD . '/entegrasyon_update.zip';
        $handle = fopen($local_file, "w+");
        $post = array('version' => $this->config->get('module_entegrasyon_new_version'), 'current_version' => $this->branch_version);
        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $this->url . 'update/download/',
            CURLOPT_USERAGENT => 'Entegrasyon update script',
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_POSTFIELDS => http_build_query($post, '', "&"),
            CURLOPT_FILE => $handle
        );

        $curl = curl_init();
        curl_setopt_array($curl, $defaults);
        curl_exec($curl);

        $curl_error = curl_error ($curl);
        // $this->openbay->log('Download errors: ' . $curl_error);

        curl_close($curl);

        return array('error' => 0, 'response' => $curl_error, 'percent_complete' => 60, 'status_message' => $this->language->get('text_extracting'));
    }


    private function get_admin_folder()
    {

        $pats=array();

        foreach (explode('/',DIR_APPLICATION) as $path) {

            if($path){

                $pats[]=$path;

            }
        }

        return end($pats);

    }

    public function updateExtract() {

        $admin_path=$this->get_admin_folder();


        $this->error = array();

        $web_root = preg_replace('/system\/$/', '', DIR_SYSTEM);

        if (!function_exists("exception_error_handler")) {
            function exception_error_handler($errno, $errstr, $errfile, $errline ) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        }

        set_error_handler('exception_error_handler');


        try {


            if(strtolower($admin_path)!='admin'){

                //  echo 'admin Yolu farklı';
                rename(DIR_APPLICATION,$web_root.'admin');
                //  echo 'isim değişti';
                sleep(5);

            }

            $zip = new ZipArchive();

            if ($zip->open(DIR_DOWNLOAD . 'entegrasyon_update.zip')) {

                $zip->extractTo($web_root);
                $zip->close();

                if(strtolower($admin_path)!='admin'){
                    sleep(5);
                    //  echo 'admin Yolu farklı';
                    rename($web_root.'admin',DIR_APPLICATION);
//echo 'eski haline döndü';
                }

            } else {

                $this->error[] = $this->language->get('text_fail_patch');
            }
        } catch(ErrorException $ex) {
            // $this->openbay->log('Unable to extract update files');
            $this->error[] = $ex->getMessage();
        }

        // reset to the OC error handler
        restore_error_handler();

        if (!$this->error) {
            return array('error' => 0, 'response' => '', 'percent_complete' => 80, 'status_message' => $this->language->get('text_remove_files'));
        } else {
            return array('error' => 1, 'response' => $this->error);
        }
    }

    public function updateRemove($beta = 0) {
        $this->error = array();

        $web_root = preg_replace('/system\/$/', '', DIR_SYSTEM);

        if (!function_exists("exception_error_handler")) {
            function exception_error_handler($errno, $errstr, $errfile, $errline ) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        }

        // $this->openbay->log('Get files to remove, beta: ' . $beta);

        $post = array('beta' => $beta);

        $files = $this->call('update/getRemoveList/', $post);

        // $this->openbay->log("Remove Files: " . print_r($files, 1));

        if (!empty($files['asset']) && is_array($files['asset'])) {
            foreach($files['asset'] as $file) {
                $filename = $web_root . implode('/', $file['locations']['location']) . '/' . $file['name'];

                if (file_exists($filename)) {
                    try {
                        unlink($filename);
                    } catch(ErrorException $ex) {
                        //  $this->openbay->log('Unable to remove file: ' . $filename . ', ' . $ex->getMessage());
                        $this->error[] = $filename;
                    }
                }
            }
        }

        // reset to the OC error handler
        restore_error_handler();

        if (!$this->error) {
            return array('error' => 0, 'response' => '', 'percent_complete' => 90, 'status_message' => $this->language->get('text_running_patch'));
        } else {
            $response_error = '<p>' . $this->language->get('error_file_delete') . '</p>';
            $response_error .= '<ul>';

            foreach($this->error as $error_file) {
                $response_error .= '<li>' . $error_file . '</li>';
            }

            $response_error .= '</ul>';

            return array('error' => 1, 'response' => $response_error, 'percent_complete' => 90, 'status_message' => $this->language->get('text_running_patch'));
        }
    }

    public function updateUpdateVersion($beta = 0) {
        //  $this->openbay->log('Updating the version in settings');



        $post = array('version' => $this->branch_version, 'beta' => $beta);

        $data = $this->call('update/version/', $post);




        if ($this->lasterror == true) {

            return array('error' => 1, 'response' => $this->lastmsg . ' (' . VERSION . ')');
        } else {

            $settings = array();




            $last_date=$this->config->get('module_entegrasyon_last_update');
            if(!$last_date){
                $query = $this->db->query("select now() as last_date");
                $last_date = $query->row['last_date'];
            }

            $this->model_setting_setting->editSetting('module_entegrasyon', array('module_entegrasyon_last_update' => $last_date, 'module_entegrasyon_status' => 1, 'module_entegrasyon_version' => $data['version']));
            return array('error' => 0, 'response' => $data['version'], 'percent_complete' => 100, 'status_message' => $this->language->get('text_updated_ok') . $data['version']);

        }
    }

    public function setUrl($url) {
        $this->url = $url;
    }

    public function getNotifications() {
        $data = $this->call('update/getNotifications/');
        return $data;
    }

    public function version() {
        $data = $this->call('update/getStableVersion/');

        // print_r($data);

        if ($this->lasterror == true) {
            $data = array(
                'error' => true,
                'msg' => $this->lastmsg . ' (' . VERSION . ')',
            );

            return $data;
        } else {

            if($data){
                $this->load->model('setting/setting');
                $this->model_setting_setting->editSettingValue('mir','mir_marketplaces', serialize($data['marketplaces']));
            }

            return $data;
        }
    }
    private function call($call, array $post = null, array $options = array(), $content_type = 'json') {


        $data = array(
            'language' => 1,
            'server' => 1,
            'domain' => $_SERVER['HTTP_HOST'],
            'udi'=> $this->config->get('mir_domain_id'),
            'entegrasyon_version' => (int)$this->config->get('module_entegrasyon_version'),
            'data' => $post,
            'content_type' => $content_type
        );




        $useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";

        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $this->url . $call,
            CURLOPT_USERAGENT => $useragent,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_POSTFIELDS => http_build_query($data, '', "&")
        );


        $curl = curl_init();


        curl_setopt_array($curl, ($options + $defaults));
        $result = curl_exec($curl);

        curl_close($curl);

        if ($content_type == 'json') {
            $encoding = json_decode($result,1);//mb_detect_encoding($result);


            /* some json data may have BOM due to php not handling types correctly */
            if ($encoding == 'UTF-8') {
                $result = preg_replace('/[^(\x20-\x7F)]*/', '', $result);
            }

            $result = json_decode($result, 1);


            /*
                        $this->load->model('setting/setting');
                        $this->model_setting_setting->editSettingValue('mir','mir_marketplaces', serialize($result['marketplaces']));
            */

            if($result){
                $this->lasterror = $result['error'];
                $this->lastmsg = $result['msg'];
            }


            if (!empty($result['data'])) {

                return $result['data'];
            } else {
                return false;
            }


        } elseif ($content_type == 'xml') {
            $result = simplexml_load_string($result);
            $this->lasterror = $result->error;
            $this->lastmsg = $result->msg;

            if (!empty($result->data)) {
                return $result->data;
            } else {
                return false;
            }
        }
    }


}