<?php

namespace DigitalHub\Juno\Model\Service;

class Api
{
    const SANDBOX_ENDPOINT_URL = 'https://sandbox.boletobancario.com/boletofacil/integration/api/v1/';
    const PRODUCTION_ENDPOINT_URL = 'https://www.boletobancario.com/boletofacil/integration/api/v1/';

    const SANDBOX_TOKEN_URL_V2 = 'https://sandbox.boletobancario.com/authorization-server/';
    const PRODUCTION_TOKEN_URL_V2 = 'https://api.juno.com.br/authorization-server/';

    const SANDBOX_ENDPOINT_URL_V2 = 'https://sandbox.boletobancario.com/api-integration/';
    const PRODUCTION_ENDPOINT_URL_V2 = 'https://api.juno.com.br/';

    /**
     * @var \Magento\Framework\Http\ZendClientFactory
     */
    private $zendClientFactory;

    private $accessToken = null;

    public function __construct(
        \Magento\Framework\Http\ZendClientFactory $zendClientFactory
    ) {
        $this->zendClientFactory = $zendClientFactory;
    }

    /**
     * Make HTTP request to API endpoints
     * @param $uri
     * @param string $method
     * @param null $data
     */
    private function __makeRequest($uri, $method = 'GET', $data = null)
    {
        $client = $this->zendClientFactory->create();
        $client->setUri($uri);
        $client->setHeaders(['Content-type' => 'application/json']);
        $client->setParameterPost($data);
        $result = $client->request($method);
        return $result->getBody();
    }

    /**
     * Creates a charge on JUNO API
     *
     * @param $isSandbox
     * @param array $data
     */
    public function issueCharge($isSandbox, $data = [])
    {
        $url = self::SANDBOX_ENDPOINT_URL;
        if (!$isSandbox) {
            $url = self::PRODUCTION_ENDPOINT_URL;
        }

        $url = $url . 'issue-charge';

        return json_decode($this->__makeRequest($url, 'POST', $data));
    }

    /**
     * Fetch payment details on JUNO API
     *
     * @param $isSandbox
     * @param string $paymentToken
     */
    public function fetchPaymentDetails($isSandbox, string $paymentToken)
    {
        $url = self::SANDBOX_ENDPOINT_URL;
        if (!$isSandbox) {
            $url = self::PRODUCTION_ENDPOINT_URL;
        }

        $url = $url . 'fetch-payment-details';
        $url .= "?paymentToken=" . $paymentToken . "&responseType=JSON";

        return json_decode($this->__makeRequest($url, 'GET'), 1);
    }

    /**
     * Make HTTP request to API endpoints
     * @param $uri
     * @param string $method
     * @param null $data
     * @param array $headers
     */
    private function __callApi($uri, $method = 'GET', $headers = [], $data = null)
    {
        // $logger = \Magento\Framework\App\ObjectManager::getInstance()
        //     ->get('Psr\Log\LoggerInterface');
        //     $logger->info('call APi called');
        $headers['Content-type'] = 'application/json';
        $headers['X-API-Version'] = 2;

        // $logger->info(json_encode($headers));
        // $logger->info($uri);
        // $logger->info(json_encode($data));
        $client = $this->zendClientFactory->create();
        $client->setUri($uri);
        $client->setHeaders($headers);

        if ($method == 'POST') {
            $client->setRawData(json_encode($data), 'application/json');
        }

        $result = $client->request($method);
        // $logger->info($result->getBody());
        return $result->getBody();
    }

    /**
     * Make HTTP request to API endpoints
     * @param $uri
     * @param string $method
     * @param null $data
     * @param array $headers
     */
    public function getAccessToken($isSandbox, $basicAuthorization)
    {
        if (!empty($this->accessToken)) {
            return $this->accessToken;
        }

        $uri = self::SANDBOX_TOKEN_URL_V2;
        if (!$isSandbox) {
            $uri = self::PRODUCTION_TOKEN_URL_V2;
        }

        $uri .= 'oauth/token';

        $headers['Content-type'] = 'application/x-www-form-urlencoded';
        $headers['Authorization'] = "Basic {$basicAuthorization}";
        $data['grant_type'] = 'client_credentials';
        $method = 'POST';

        $client = $this->zendClientFactory->create();
        $client->setUri($uri);
        $client->setHeaders($headers);
        $client->setParameterPost($data);
        $result = $client->request($method);

        $response = json_decode($result->getBody());

        if (!empty($response->access_token)) {
            $this->accessToken = $response->access_token;
        }

        return $this->accessToken;
    }

    /**
     * Creates a charge on JUNO API
     *
     * @param $isSandbox
     * @param array $data
     */
    public function callCharge($isSandbox, $headers = [], $data = [])
    {
        $url = self::SANDBOX_ENDPOINT_URL_V2;
        if (!$isSandbox) {
            $url = self::PRODUCTION_ENDPOINT_URL_V2;
        }

        $url = $url . 'charges';

        return json_decode($this->__callApi($url, 'POST', $headers, $data));
    }

    /**
     * Get payment details on JUNO API
     *
     * @param $isSandbox
     * @param string $paymentToken
     */
    public function getPaymentDetails($isSandbox, string $paymentToken, $headers = [])
    {
        $url = self::SANDBOX_ENDPOINT_URL_V2;
        if (!$isSandbox) {
            $url = self::PRODUCTION_ENDPOINT_URL_V2;
        }

        $url = $url . 'charges/'.$paymentToken;
        return json_decode($this->__callApi($url, 'GET', $headers), 1);
    }

    /**
     * Get payment details on JUNO API
     *
     * @param $isSandbox
     * @param string $paymentToken
     */
    public function createPayment($isSandbox, $headers = [], $data = [])
    {
        $url = self::SANDBOX_ENDPOINT_URL_V2;
        if (!$isSandbox) {
            $url = self::PRODUCTION_ENDPOINT_URL_V2;
        }

        $url = $url . 'payments/';
        return json_decode($this->__callApi($url, 'POST', $headers, $data));
    }
}
