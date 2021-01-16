<?php

namespace Frknakk\Internetmarke\Types;

use \Storage;
use \Str;

class OrderResult
{
    protected $download_link;
    protected $manifest_link;
    protected $order_id;
    protected $voucher_id;

    public function getDownloadLink()
    {
        return $this->download_link;
    }

    public function getManifestLink()
    {
        return $this->manifest_link;
    }

    public function getOrderId()
    {
        return $this->order_id;
    }

    public function getVoucherId()
    {
        return $this->voucher_id;
    }

    public function savePNG($filename = null)
    {
        Storage::makeDirectory('internetmarke/tmp');

        if ($filename == null)
        {
            $filename = Str::uuid().'.png';
        }

        $tmp_zip_file = storage_path('app/internetmarke/tmp/'.Str::uuid().'.zip');
        $resp_file = storage_path('app/internetmarke/'.$filename);

        $success = false;
        try
        {
            if (!copy($this->download_link, $tmp_zip_file)) return null;
            $success = copy('zip://'.$tmp_zip_file.'#0.png', $resp_file);
        }
        catch (\Exception $e)
        {
            // ignore
        }
        finally
        {
            unlink($tmp_zip_file);
        }

        return $success ? $resp_file : null;
    }

    public function savePDF($filename = null)
    {
        Storage::makeDirectory('internetmarke');

        if ($filename == null)
        {
            $filename = Str::uuid().'.pdf';
        }

        $resp_file = storage_path('app/internetmarke/'.$filename);

        return copy($this->download_link, $resp_file) ? $resp_file : null;
    }

    public function saveManifest($filename = null)
    {
        Storage::makeDirectory('internetmarke/manifest');

        if ($filename == null)
        {
            $filename = Str::uuid().'.pdf';
        }

        $resp_file = storage_path('app/internetmarke/manifest/'.$filename);

        return copy($this->manifest_link, $resp_file) ? $resp_file : null;
    }

    public static function fromRaw($resp)
    {
        $obj = new static;
        $obj->download_link = $resp->link ?? null;
        $obj->manifest_link = $resp->manifestLink ?? null;
        $obj->order_id = $resp->shoppingCart->shopOrderId ?? null;
        $obj->voucher_id = $resp->shoppingCart->voucherList->voucher[0]->voucherId ?? null;

        return $obj;
    }
}
