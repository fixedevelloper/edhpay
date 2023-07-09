<?php


namespace App\Http\Controllers\Gateway;


class EkoloPayController
{
    private $config;
    /**
     * EkoloPayController constructor.
     */
    public function __construct()
    {
        $this->config = \App\CentralLogics\Helpers::get_business_settings('ekolopay');
    }
}
