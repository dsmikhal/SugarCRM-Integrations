<?php

/** * DOCEBO, e-learning SAAS * * @link http://www.docebo.com/ * @copyright Copyright &copy; 2004-2013 Docebo */
class DoceboAPI
{
    private $url;
    private $key ;
    private $secret_key;
    private $sso;


    function __construct($Docebo)
    {
        $this->url = $Docebo['url'];
        $this->key = $Docebo['key'];
        $this->secret_key = $Docebo['secret_key'];
        $this->sso = $Docebo['sso'];
    }

    protected function getHash($params)
    {
        $res = array('sha1' => '', 'x_auth' => '');
        $res['sha1'] = sha1(implode(',', $params) . ',' . $this->secret_key);
        $res['x_auth'] = base64_encode($this->key . ':' . $res['sha1']);
        return $res;
    }

    protected function getDefaultHeader($x_auth)
    {
        return array("Host: " . $this->url, "Content-Type: multipart/form-data", 'X-Authorization: Docebo ' . $x_auth,);
    }

    public function call($action, $data_params)
    {
        $curl = curl_init();
        $hash_info = $this->getHash($data_params);
        $http_header = $this->getDefaultHeader($hash_info['x_auth']);
        $opt = array( CURLOPT_URL  => $this->url . '/api/' . $action,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER     => $http_header,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $data_params,
            CURLOPT_CONNECTTIMEOUT => 20, // Timeout to 20 seconds
        );

        curl_setopt_array($curl, $opt);
        $output = curl_exec($curl); // $output contains the output string
        curl_close($curl);// it closes the session
        return $output;
    }


    protected function sso($user)
    {
        $time = time();
        $token = md5($user . ',' . $time . ',' . $this->sso);
        return 'http://' . $this->url . '/doceboLms/index.php?modname=login&op=confirm&login_user=' . strtolower($user) . '&time=' . $time . '&token=' . $token;
    }
}
