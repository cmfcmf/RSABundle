<?php

namespace Cmfcmf\Bundle\RSABundle\EventListener;

use Cmfcmf\Bundle\RSABundle\InitializableControllerInterface;
use Symfony\Bundle\WebProfilerBundle\Controller\ExceptionController;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Cmfcmf\Bundle\RSABundle\Twig\Extension\CodeExtension;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * @author Christian Flach <cmfcmf.flach@gmail.com>
 * @author Matt Drollette <matt@drollette.com>
 */
class ControllerListener
{
    protected $extension;

    public function __construct(CodeExtension $extension)
    {
        $this->extension = $extension;
    }

    public function onKernelController(FilterControllerEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $this->extension->setController($event->getController());
        }

        $controller = $event->getController();

        if (!is_array($controller)) {
            // not a object but a different kind of callable. Do nothing
            return;
        }

        $controllerObject = $controller[0];

        // skip initializing for exceptions
        if ($controllerObject instanceof ExceptionController) {
            return;
        }
        if ($controllerObject instanceof InitializableControllerInterface) {
            // this method is the one that is part of the interface.
            $controllerObject->initialize($event->getRequest());
        }
    }
}
