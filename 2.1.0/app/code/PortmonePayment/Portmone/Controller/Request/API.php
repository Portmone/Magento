<?php

namespace PortmonePayment\Portmone\Controller\Request;


use Magento\Framework\App\Action\Context;

use \Magento\Framework\App\Request\Http;

use \PortmonePayment\Portmone\Model\Portmone;

use Magento\Config\Model\ResourceModel\Config;

class API extends \Magento\Framework\App\Action\Action
{
    public $http;
    protected $portmone;

    public function getRequest()
    {
        return parent::getRequest();
    }

    public function __construct(
        Context $context,
        Http $http,
        Portmone $portmone
    )
    {
        $this->portmone = $portmone;
        $this->http = $http;
        parent::__construct($context);
    }
    public function execute()
    {
        echo $this->getSign();
        exit;
    }

    public function getSign(){
       $post = $this->getPost();
        if($post){
           return $this->portmone->IkSignFormation($post,$this->portmone->getConfigData('secret_key'));
        }else{
            return array(
                'error'=>'something wrong in Sign Formation'
            );
        }
    }
    public function getPost(){
        if(!empty($_POST) && !empty($_POST['ik_co_id'])){
            return $this->http->getPost();
        }else{
            return false;
        }

    }

}