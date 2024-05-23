<?php

namespace AziendeGlobal\LaravelDLocal;

use Exception;

/**
 * dLocal Integration Library
 * Access dLocal for payments integration
 * 
 * @author hcasatti
 *
 */
$GLOBALS["LIB_LOCATION"] = dirname(__FILE__);

class DLOCAL
{

    const version = "1.0.0";

    private $x_login;
    private $x_trans_key;
    private $secret_key;
    private $api_key;

    private $x_date;
    private $headers;
    private $body;
    private $access_data;
    private $sandbox = FALSE;

    function __construct($x_login = null, $x_trans_key = null, $secret_key = null, $api_key = null)
    {
        if (!$secret_key || !$x_login || !$x_trans_key) {
            throw new DLocalException("Invalid arguments. Use X_LOGIN, X_TRANS_KEY and SECRET_KEY");
        }

        $this->x_login = $x_login;
        $this->x_trans_key = $x_trans_key;
        $this->secret_key = $secret_key;
        $this->api_key = $api_key;

        $sysdate = date("Y-m-d H:i:s");
        $sydateFormat = strtotime($sysdate);
        $this->x_date = str_replace('+00:00', '.000Z', gmdate('c', $sydateFormat));

        $this->headers = array(
            "X-Date" => $this->x_date,
            "X-Login" => $this->x_login,
            "X-Trans-Key" => $this->x_trans_key,
            "X-Version" => "2.1",
            "User-Agent" => "MerchantTest / 1.0 ",
        );

        if ($this->api_key) {
            $this->headers["X-Idempotency-Key"] = $this->api_key;
        }
    }

    public function sandbox_mode($enable = NULL)
    {
        if (!is_null($enable)) {
            $this->sandbox = $enable === TRUE;
        }

        return $this->sandbox;
    }

    /**
     * Get Signature 
     */

    public function get_signature($data)
    {
        $key = $this->x_login . $this->x_date . json_encode($data);
        $signature = hash_hmac("sha256", $key, $this->secret_key);

        return $signature;
    }

    /**
     * Create a payment
     * @param array $data
     * @return array(json)
     */
    public function create_payment($data, $sandbox = false)
    {
        $request = array(
            "uri" => "/payments",
            "sandbox" => $sandbox,
            "headers" => $this->headers,
            "headers_custom" => array(
                "signature" => $this->get_signature($data),
            ),
            "data" => $data
        );

        $result = DLOCALRestClient::post($request);

        return $result;
    }

    /**
     * Create a secure payment
     * @param array $data
     * @return array(json)
     */
    public function create_secure_payment($data, $sandbox = false)
    {
        $request = array(
            "uri" => "/secure_payments",
            "sandbox" => $sandbox,
            "headers" => $this->headers,
            "headers_custom" => array(
                "signature" => $this->get_signature($data),
            ),
            "data" => $data
        );

        $result = DLOCALRestClient::post($request);
        return $result;
    }


    /* **************************************************************************************** */
}

/**
 * DLocal cURL RestClient
 */
class DLOCALRestClient
{
    const API_BASE_URL = "https://api.dlocal.com";
    const API_BASE_URL_SANDBOX = "https://sandbox.dlocal.com";

    private static function build_request($request)
    {
        if (!extension_loaded("curl")) {
            throw new DLocalException("cURL extension not found. You need to enable cURL in your php.ini or another configuration you have.");
        }

        if (!isset($request["method"])) {
            throw new DLocalException("No HTTP METHOD specified");
        }

        if (!isset($request["uri"])) {
            throw new DLocalException("No URI specified");
        }

        $API_BASE_URL = self::API_BASE_URL;

        if (isset($request["sandbox"]) && $request["sandbox"]) {
            $API_BASE_URL = self::API_BASE_URL_SANDBOX;
        }

        // Set headers
        $headers = array("accept: application/json");
        $json_content = true;
        $form_content = false;
        $default_content_type = true;

        if (isset($request["headers"]) && is_array($request["headers"])) {
            foreach ($request["headers"] as $h => $v) {
                //$h = strtolower($h);
                //$v = strtolower($v);

                if ($h == "content-type") {
                    $default_content_type = false;
                    $json_content = $v == "application/json";
                    $form_content = $v == "application/x-www-form-urlencoded";
                }

                array_push($headers, $h . ": " . $v);
            }
        }

        if ($default_content_type) {
            array_push($headers, "content-type: application/json");
        }

        if (isset($request["headers_custom"]) && is_array($request["headers_custom"]) && count($request["headers_custom"]) > 0) {
            if (isset($request["headers_custom"]['signature'])) {
                $authorization = "Authorization: V2-HMAC-SHA256, Signature: " . $request["headers_custom"]['signature'];
                array_push($headers, $authorization);
            }
        }

        // Set parameters and url
        if (isset($request["params"]) && is_array($request["params"]) && count($request["params"]) > 0) {
            $request["uri"] .= (strpos($request["uri"], "?") === false) ? "?" : "&";
            $request["uri"] .= self::build_query($request["params"]);
        }

        // Build $connect
        $connect = curl_init();

        curl_setopt($connect, CURLOPT_USERAGENT, "DLocal PHP SDK v" . DLOCAL::version);
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($connect, CURLOPT_CAINFO, $GLOBALS["LIB_LOCATION"] . "/cacert.pem");
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $request["method"]);
        curl_setopt($connect, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($connect, CURLOPT_URL, $API_BASE_URL . $request["uri"]);

        // Set data
        if (isset($request["data"])) {
            if ($json_content) {
                if (gettype($request["data"]) == "string") {
                    json_decode($request["data"], true);
                } else {
                    $request["data"] = json_encode($request["data"]);
                }

                if (function_exists('json_last_error')) {
                    $json_error = json_last_error();
                    if ($json_error != JSON_ERROR_NONE) {
                        throw new DLocalException("JSON Error [{$json_error}] - Data: " . $request["data"]);
                    }
                }
            } else if ($form_content) {
                $request["data"] = self::build_query($request["data"]);
            }

            curl_setopt($connect, CURLOPT_POSTFIELDS, $request["data"]);
        }

        return $connect;
    }

    private static function exec($request)
    {
        // private static function exec($method, $uri, $data, $content_type) {

        $connect = self::build_request($request);

        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);

        if ($api_result === FALSE) {
            throw new DLocalException(curl_error($connect));
        }

        $response = array(
            "status" => $api_http_code,
            "response" => json_decode($api_result, true)
        );

        if ($response['status'] >= 400) {
            $message = $response['response']['message'];
            if (isset($response['response']['cause'])) {
                if (isset($response['response']['cause']['code']) && isset($response['response']['cause']['description'])) {
                    $message .= " - " . $response['response']['cause']['code'] . ': ' . $response['response']['cause']['description'];
                } else if (is_array($response['response']['cause'])) {
                    foreach ($response['response']['cause'] as $cause) {
                        $message .= " - " . $cause['code'] . ': ' . $cause['description'];
                    }
                }
            }

            throw new DLocalException($message, $response['status']);
        }

        curl_close($connect);

        return $response;
    }

    private static function build_query($params)
    {
        if (function_exists("http_build_query")) {
            return http_build_query($params, "", "&");
        } else {
            foreach ($params as $name => $value) {
                $elements[] = "{$name}=" . urlencode($value);
            }

            return implode("&", $elements);
        }
    }

    public static function get($request)
    {
        $request["method"] = "GET";

        return self::exec($request);
    }

    public static function post($request)
    {
        $request["method"] = "POST";

        return self::exec($request);
    }

    public static function put($request)
    {
        $request["method"] = "PUT";

        return self::exec($request);
    }

    public static function delete($request)
    {
        $request["method"] = "DELETE";

        return self::exec($request);
    }
}

class DLocalException extends Exception
{
    public function __construct($message, $code = 500, Exception $previous = null)
    {
        // Default code 500
        parent::__construct($message, $code, $previous);
    }
}
