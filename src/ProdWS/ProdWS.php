<?php

namespace Frknakk\Internetmarke\ProdWS;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use RobRichards\WsePhp\WSSESoap;

class ProdWS extends \SoapClient
{
    protected $config;

    private $wsdl = 'https://prodws.deutschepost.de:8443/ProdWSProvider_1_1/prodws?wsdl';

    public function __construct(ConfigRepository $config)
    {
        $this->config = $config;

        parent::__construct($this->wsdl, [
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
			
			// DEBUG
            // 'trace' => true,
        ]);
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $doc = new \DOMDocument('1.0');
        $doc->loadXML($request);

        // DEBUG
        // $doc->preserveWhiteSpace = false;
        // $doc->formatOutput = true;

        $objWSSE = new WSSESoap($doc);
        $objWSSE->addUserToken($this->config->get('internetmarke.prodws.username'), $this->config->get('internetmarke.prodws.password'), false);

        $request = $objWSSE->saveXML();

        // DEBUG
        // print_r($request);
        // exit;

        return parent::__doRequest($request, $location, $action, $version);
    }

    /**
     * Fetch available products
     *
     * @param  only_sales_products Fetch only sales products?
     * @param  validity_start Start date (fetch future changes)
     * @return array
     */
    public function getProducts($only_sales_products = false, $validity_start = null)
    {
        $params = [
            'mandantID' => $this->config->get('internetmarke.prodws.mandant_id'),
            'dedicatedProducts' => !$only_sales_products,
            'responseMode' => 0,
        ];
        if ($validity_start instanceof \DateTime || $validity_start instanceof \Carbon\Carbon)
        {
            $params['timestamp'] = [
                'date' => $validity_start->format('Y-m-d'),
                'time' => $validity_start->format('H:i:s')
            ];
        }
        $resp = $this->getProductList($params);
        if (!isset($resp->success) || !isset($resp->Response) || !$resp->success) return false;

        $resp_arr = [
            'sales_products' => [],
            'basic_products' => [],
            'additional_products' => [],
        ];

        if (isset($resp->Response->salesProductList->SalesProduct) && is_array($resp->Response->salesProductList->SalesProduct))
        {
            $resp_arr['sales_products'] = $resp->Response->salesProductList->SalesProduct;
        }

        if (isset($resp->Response->basicProductList->BasicProduct) && is_array($resp->Response->basicProductList->BasicProduct))
        {
            $resp_arr['basic_products'] = $resp->Response->basicProductList->BasicProduct;
        }

        if (isset($resp->Response->additionalProductList->AdditionalProduct) && is_array($resp->Response->additionalProductList->AdditionalProduct))
        {
            $resp_arr['additional_products'] = $resp->Response->additionalProductList->AdditionalProduct;
        }

        return $resp_arr;
    }

    /**
     * Determine if product changes are available for the provided date.
     *
     * @param  dt start date
     * @return bool
     */
    public function productChangesAvailable($dt)
    {
        /**
         * Useless?
         * Always returns no changes available, even for 2000-01-01 last query date
         *
         * Same problem for "onlyChanges" and "referenceDate" attributes on $this->getProductList
         */

        $resp = $this->getProductChangeInformation([
            'mandantID' => $this->config->get('internetmarke.prodws.mandant_id'),
            'lastQueryDate' => [
                'date' => $dt->format('Y-m-dP'),
                'time' => $dt->format('H:i:sP'),
            ],
        ]);
        if (!isset($resp->success) || !isset($resp->Response) || !$resp->success) return false;

        return $resp->Response->changesAvailable;
    }
}
