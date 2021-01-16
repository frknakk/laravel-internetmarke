<?php

namespace Frknakk\Internetmarke;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Encryption\Encrypter;
use RobRichards\WsePhp\WSSESoap;
use \Cache;
use Frknakk\Internetmarke\Types\NamesAddress;
use Frknakk\Internetmarke\Types\OrderResult;
use Frknakk\Internetmarke\Types\VoucherPosition;

class Internetmarke extends \SoapClient
{
    public const FRANKING_ZONE = 'FrankingZone';
    public const ADDRESS_ZONE = 'AddressZone';

    protected $config;
    protected $wsdl = 'https://internetmarke.deutschepost.de/OneClickForAppV3/OneClickForAppServiceV3?wsdl';

    protected $cache_key = null;
    protected $encrypter = null;
    protected $expires_at = null;
    protected $user_token = null;
    protected $wallet_balance = null;
    protected $info_msg = null;

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

        $header = $this->getHeader($doc);

        $partner_id = $doc->createElementNS('http://oneclickforapp.dpag.de/V3', 'PARTNER_ID');
        $partner_id->appendChild($doc->createTextNode($this->config->get('internetmarke.partner_id')));
        $header->appendChild($partner_id);

        $key_phase = $doc->createElementNS('http://oneclickforapp.dpag.de/V3', 'KEY_PHASE');
        $key_phase->appendChild($doc->createTextNode($this->config->get('internetmarke.key_phase')));
        $header->appendChild($key_phase);

        $now_dt = now();

        $request_timestamp = $doc->createElementNS('http://oneclickforapp.dpag.de/V3', 'REQUEST_TIMESTAMP');
        $request_timestamp->appendChild($doc->createTextNode($now_dt->format('dmY-His')));
        $header->appendChild($request_timestamp);

        $partner_signature = $doc->createElementNS('http://oneclickforapp.dpag.de/V3', 'PARTNER_SIGNATURE');
        $partner_signature->appendChild($doc->createTextNode($this->generateSignature($now_dt)));
        $header->appendChild($partner_signature);

        $request = $doc->saveXML();

        // DEBUG
        // print_r($request);
        // exit;

        return parent::__doRequest($request, $location, $action, $version);
    }

    public function login($username, $password, $ignore_terms = false)
    {
        $hashed_key = hash('sha256', $username.':'.$password);
        $cache_key = 'internetmarke_user_'.$hashed_key;
        $expires_at = now()->addMinutes(50);

        $encrypter = new Encrypter(substr($hashed_key, 0, 32), 'AES-256-CBC');

        $user_obj = Cache::remember($cache_key, $expires_at, function () use($encrypter, $expires_at, $username, $password, $ignore_terms) {
            $resp = $this->authenticateUser([
                'username' => $username,
                'password' => $password,
            ]);

            if (!$ignore_terms && isset($resp->showTermsAndConditions) && $resp->showTermsAndConditions) return false;

            return [
                'expires_at' => $expires_at,
                'user_token' => $encrypter->encrypt($resp->userToken),
                'wallet_balance' => (float) ($resp->walletBalance / 100),
                'info_msg' => $resp->infoMessage ?? null
            ];
        });

        if ($user_obj == false)
        {
            Cache::forget($cache_key);
            return false;
        }

        $res = new static($this->config);
        $res->cache_key = $cache_key;
        $res->encrypter = $encrypter;
        $res->expires_at = $user_obj['expires_at'];
        $res->user_token = $encrypter->decrypt($user_obj['user_token']);
        $res->wallet_balance = $user_obj['wallet_balance'];
        $res->info_msg = $user_obj['info_msg'];

        return $res;
    }

    public function updateBalance($cents)
    {
        $this->wallet_balance = (float) ($cents / 100);

        Cache::put($this->cache_key, [
            'expires_at' => $this->expires_at,
            'user_token' => $this->encrypter->encrypt($this->user_token),
            'wallet_balance' => $this->wallet_balance,
            'info_msg' => $this->info_msg
        ], $this->expires_at);
    }

    public function getBalance()
    {
        return $this->wallet_balance;
    }

    public function getInfoMessage()
    {
        return $this->info_msg;
    }

    public function getContractProducts()
    {
        $resp = $this->retrieveContractProducts([
            'userToken' => $this->user_token,
        ]);

        return (isset($resp->products) && is_array($resp->products)) ? $resp->products : [];
    }

    public function getPageFormats()
    {
        $resp = $this->retrievePageFormats();

        return (isset($resp->pageFormat) && is_array($resp->pageFormat)) ? $resp->pageFormat : [];
    }

    public function nextShopOrderId()
    {
        $resp = $this->createShopOrderId([
            'userToken' => $this->user_token,
        ]);

        return $resp->shopOrderId ?? null;
    }

    public function getPublicGallery()
    {
        $resp = $this->retrievePublicGallery();

        return (isset($resp->items) && is_array($resp->items)) ? $resp->items : [];
    }

    public function getPrivateGallery()
    {
        $resp = $this->retrievePrivateGallery([
            'userToken' => $this->user_token,
        ]);

        return $resp;
    }

    public function getPreviewPDF($ppl_id, $layout, $pageformat_id, $image_id = null)
    {
        $resp = $this->retrievePreviewVoucherPDF([
            'productCode' => $ppl_id,
            'voucherLayout' => $layout,
            'pageFormatId' => $pageformat_id,
            'imageID' => $image_id,
        ]);

        return $resp->link ?? null;
    }

    public function getPreviewPNG($ppl_id, $layout, $image_id = null)
    {
        $resp = $this->retrievePreviewVoucherPNG([
            'productCode' => $ppl_id,
            'voucherLayout' => $layout,
            'imageID' => $image_id,
        ]);

        return $resp->link ?? null;
    }

    public function checkoutPDF($order_id, $ppl_id, $ppl_cents, $layout, $pageformat_id, VoucherPosition $position, NamesAddress $receiver = null, NamesAddress $sender = null)
    {
        if ($order_id == null)
        {
            $order_id = $this->nextShopOrderId();
        }

        $pos = [
            'productCode' => $ppl_id,
            'voucherLayout' => $layout,
            'position' => $position->toArray()
        ];

        if ($sender !== null && $receiver !== null)
        {
            $pos['address'] = [
                'sender' => $sender->toArray(),
                'receiver' => $receiver->toArray()
            ];
        }

        $original_exception = null;
        try
        {
            $resp = $this->checkoutShoppingCartPDF([
                'userToken' => $this->user_token,
                'shopOrderId' => $order_id,
                'pageFormatId' => $pageformat_id,
                'positions' => [ $pos ],
                'total' => $ppl_cents,
                'createManifest' => true,
                'createShippingList' => 2,
            ]);

            if (isset($resp->walletBallance))
            {
                $this->updateBalance($resp->walletBallance);
            }

            return OrderResult::fromRaw($resp);
        }
        catch (\SoapFault $ex)
        {
            if ($ex->getMessage() !== 'Checkout shopping cart failed.')
            {
                throw $ex;
            }

            $original_exception = $ex;
        }

        // Check if order already exists
        try
        {
            return $this->getExistingOrder($order_id);
        }
        catch (\SoapFault $ex)
        {
            throw $original_exception;
        }
    }

    public function checkoutPNG($order_id, $ppl_id, $ppl_cents, $layout, NamesAddress $receiver = null, NamesAddress $sender = null)
    {
        if ($order_id == null)
        {
            $order_id = $this->nextShopOrderId();
        }

        $pos = [
            'productCode' => $ppl_id,
            'voucherLayout' => $layout
        ];

        if ($sender !== null && $receiver !== null)
        {
            $pos['address'] = [
                'sender' => $sender->toArray(),
                'receiver' => $receiver->toArray()
            ];
        }

        $original_exception = null;
        try
        {
            $resp = $this->checkoutShoppingCartPNG([
                'userToken' => $this->user_token,
                'shopOrderId' => $order_id,
                'positions' => [ $pos ],
                'total' => $ppl_cents,
                'createManifest' => true,
                'createShippingList' => 2,
            ]);

            if (isset($resp->walletBallance))
            {
                $this->updateBalance($resp->walletBallance);
            }

            return OrderResult::fromRaw($resp);
        }
        catch (\SoapFault $ex)
        {
            if ($ex->getMessage() !== 'Checkout shopping cart failed.')
            {
                throw $ex;
            }

            $original_exception = $ex;
        }

        // Check if order already exists
        try
        {
            return $this->getExistingOrder($order_id);
        }
        catch (\SoapFault $ex)
        {
            throw $original_exception;
        }
    }

    public function getExistingOrder($order_id)
    {
        $resp = $this->retrieveOrder([
            'userToken' => $this->user_token,
            'shopOrderId' => $order_id
        ]);

        return OrderResult::fromRaw($resp);
    }

    public function createAddress()
    {
        return new Types\NamesAddress;
    }

    public function createPosition()
    {
        return new Types\VoucherPosition;
    }

    private function generateSignature($dt)
    {
        $fields = [
            trim($this->config->get('internetmarke.partner_id')),
            trim($dt->format('dmY-His')),
            trim($this->config->get('internetmarke.key_phase')),
            trim($this->config->get('internetmarke.secret_key')),
        ];

        $input_str = implode('::', $fields);

        return substr(md5($input_str), 0, 8);
    }

    private function getHeader(&$doc)
    {
        $envelope = $doc->documentElement;
        $soap_ns = $envelope->namespaceURI;
        $soap_prefix = $envelope->prefix;

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('wssoap', $soap_ns);
        $header = $xpath->query('//wssoap:Envelope/wssoap:Header')->item(0);
        if (!$header) {
            $header = $doc->createElementNS($soap_ns, $soap_prefix.':Header');
            $envelope->insertBefore($header, $envelope->firstChild);
        }

        return $header;
    }
}
