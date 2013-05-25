<?php
class Proxy extends CI_Controller {

    // host restricts
    private static $allowed_host = array('yl.host.com');

    const REQUEST_HOST = 'www.yl.com';

    public function index() {

        // initial curl request
        $url = 'http://' . self::REQUEST_HOST . urldecode($_GET['url']);
        $method = 'GET';
        if (isset($_SERVER["REQUEST_METHOD"])) {
            $method = $_SERVER["REQUEST_METHOD"];
        }


        if (!$url || !in_array($_SERVER['HTTP_HOST'], self::$allowed_host) || !in_array($method, array('GET', 'POST'))) {
            exit(json_encode(array(
                'code' => '-1',
                'msg' => 'ajax参数错误'
            )));
        }

        // requesting
        if ($method == 'POST') {
            $result = $this->_post($url, http_build_query($_POST), 3);
        } else {
            $result = $this->_get($url, 3);
        }

        // sending header
        if ($result['header']) foreach ($result['header'] as $header) {
            if (preg_match('/^(?:Content-Type|Content-Language|Set-Cookie):/i', $header)) {
                header($header, false);
            }
        }

        // output
        print $result['data'];
    }

    /**
    *
    *
    * @link http://cn.php.net/manual/en/function.curl-exec.php
    * @param string $url
    * @param int $timeout
    * @return array
    */
    private function _get($url, $timeout) {
        $result = array();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, ''); // all supported encodings

        curl_setopt($ch, CURLOPT_USERAGENT, "HttpGET/1.0");
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // This request is an AJAX request
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "X-Requested-With: XMLHttpRequest"
            ));
        }

        $data = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $result['data'] = substr($data, $header_size);
        $result['header'] = preg_split('/[\r\n]+/', substr($data, 0, $header_size));

        curl_close($ch);

        return $result;
    }

    /**
     *
     * @param string $url
     * @param string/array $vars
     * @param integer $timeout
     * @return array
     */
    private function _post($url, $vars, $timeout) {
        $time = microtime(true);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        if (preg_match('#^https://#i', $url)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        if ($_COOKIE) {
            curl_setopt($ch, CURLOPT_COOKIE, http_build_query($_COOKIE, '', ';'));
        }
        curl_setopt($ch, CURLOPT_USERAGENT, "HttpGET/1.0");
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // This request is an AJAX request
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "X-Requested-With: XMLHttpRequest"
            ));
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        curl_setopt($ch, CURLOPT_ENCODING, ''); // all supported encodings

        $data = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $result['data'] = substr($data, $header_size);
        $result['header'] = preg_split('/[\r\n]+/', substr($data, 0, $header_size));

        curl_close($ch);

        return $result;
    }
}
?>
