<?php

namespace Cmfcmf\Bundle\RSABundle;

use Symfony\Component\HttpFoundation\Request;

/**
* @author Matt Drollette <matt@drollette.com>
*/
interface InitializableControllerInterface
{
    public function initialize(Request $request);
}