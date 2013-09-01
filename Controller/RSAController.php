<?php

namespace Cmfcmf\Bundle\RSABundle\Controller;

use Cmfcmf\Bundle\RSABundle\Entity\RSA as RSAEntity;
use Cmfcmf\Bundle\RSABundle\InitializableControllerInterface;
use Cmfcmf\Bundle\RSABundle\PrimeNumbers;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * @Route("RSA")
 * @Cache(maxage="0")
 * //@Cache(maxage="86400")
 */
class RSAController extends Controller implements InitializableControllerInterface
{
    private $primeNumbers;
    private $nrPrimeNumbers;

    /**
     * Initialize function called on every request. Not used yet.
     * @param Request $request
     */
    public function initialize(Request $request)
    {
        /*
        $this->get('session')->remove('n');
        $this->get('session')->remove('e');
        $this->get('session')->remove('d');
        */
    }

    /**
     * @Route("/")
     * @Template()
     */
    public function indexAction()
    {
        $this->get('session')->getFlashBag()->add(
            'notice',
            'Hallo, Sie! Hier können Sie ganz einfach RSA Schlüssel generieren sowie Texte verschlüsseln und entschlüsseln.'
        );
        return $this->getKeys();
    }

    /**
     * @Route("/generate")
     * @Template()
     * @Cache(maxage="0")
     */
    public function generateAction(Request $request)
    {
        $rsa = new RSAEntity();

        $primeNumbersObj = new PrimeNumbers();
        $primeNumbers = $primeNumbersObj->primeNumbers;
        $this->primeNumbers = $primeNumbers;
        $this->nrPrimeNumbers = count($primeNumbers);
        unset($primeNumbersObj);
        unset($primeNumbers);

        $p = $this->primeNumbers[mt_rand(0, $this->nrPrimeNumbers - 1)];
        $q = $this->primeNumbers[mt_rand(0, $this->nrPrimeNumbers - 1)];

     //   $rsa->setP($p);
     //   $rsa->setQ($q);

        $form = $this->createFormBuilder($rsa)
            ->add('p', 'text')
            ->add('q', 'text')
            ->add('generieren', 'submit')
            ->getForm();

        $form->handleRequest($request);

        $data = $form->getData();

        if ($data->getP() != "" && $data->getQ() != "" && $form->isSubmitted()) {
            $p = $data->getP();
            $q = $data->getQ();
            $n = bcmul($p, $q);
            $phi = bcmul(bcsub($p, 1), bcsub($q, 1));
            $d = 0;
            $e = 0;

            $this->calcDE($phi, $d, $e);

            $this->setKeys($d, $e, $n);
            return $this->forward('CmfcmfRSABundle:RSA:success', array(
                    'p' => $p, 'q' => $q, 'n' => $n, 'phi' => $phi, 'd' => $d, 'e' => $e)
            );
        }

        return array_merge($this->getKeys(), array('form' => $form->createView(), 'nrPrimeNumbers' => $this->nrPrimeNumbers));
    }

    /**
     * @Route("/success")
     * @Method("POST")
     * @Template()
     * @Cache(maxage="0")
     */
    public function successAction($p, $q, $n, $phi, $d, $e)
    {
        return array('p' => $p, 'q' => $q, 'n' => $n, 'phi' => $phi, 'd' => $d, 'e' => $e);
    }

    /**
     * @Route("/how")
     * @Template()
     */
    public function howAction()
    {
        return array_merge($this->getKeys(), array('sourceCode' => $this->getSourceCode()));
    }

    /**
     * @Route("/rsa")
     * @Template()
     * @Method("GET")
     * @Cache(maxage="0")
     */
    public function rsaAction()
    {
        $keys = $this->getKeys();
        if (empty($keys)) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'Sie müssen zuerst Schlüssel generieren!'
            );
            return $this->redirect($this->generateUrl('cmfcmf_rsa_rsa_generate', $this->getKeys()));
        }
        return $keys;
    }

    /**
     * @Route("/rsa")
     * @Template()
     * @Method("POST")
     * @Cache(maxage="0")
     */
    public function rsaCalcAction(Request $request)
    {
        $keys = $this->getKeys();
        if (empty($keys)) {
            $this->get('session')->getFlashBag()->add(
                'error',
                'Sie müssen zuerst Schlüssel generieren!'
            );
            return $this->redirect($this->generateUrl('cmfcmf_rsa_rsa_generate', $this->getKeys()));
        }

        $text = $request->request->get('normalText');
        $specialText = $request->request->get('specialText');

        if (!empty($text)) {
            $newText = "";
            for ($i = 0; $i < strlen($text); $i++) {
                $newText .= $this->calcRSAOfInt(
                        ord($text[$i]),
                        $request->request->get('n') != "" ? $request->request->get('n') : $keys['n'],
                        $request->request->get('e') != "" ? $request->request->get('e') : $keys['e']
                    ) . ";";
            }
        } else {
            $newText = "";
            $text = explode(';', $specialText);
            for ($i = 0; $i < count($text); $i++) {
                # echo $text[$i] . "<br>";
                $newText .= chr($this->calcIntOfRSA($text[$i], $keys['n'], $keys['d']));
            }
        }

        $this->get('session')->getFlashBag()->add(
            'rawData',
            $newText
        );

        return $this->redirect($this->generateUrl('cmfcmf_rsa_rsa_rsa'));
    }

    /**
     * Helper function to store the keys in session vars.
     *
     * @param $d
     * @param $e
     * @param $n
     */
    private function setKeys($d, $e, $n)
    {
        $this->get('session')->set('n', $n);
        $this->get('session')->set('e', $e);
        $this->get('session')->set('d', $d);
    }

    /**
     * Helper function to get the keys from session vars.
     *
     * @return array
     */
    private function getKeys()
    {
        $n = $this->get('session')->get('n');
        $e = $this->get('session')->get('e');
        $d = $this->get('session')->get('d');
        if (!is_numeric($n) || !is_numeric($d) || !is_numeric($e)) {
            return array();
        }
        return array('n' => $n, 'e' => $e, 'd' => $d);
    }

    /**
     * Encrypt integer with public key.
     *
     * @param $int
     * @param $n
     * @param $e
     *
     * @return int
     */
    private function calcRSAOfInt($int, $n, $e)
    {
        return bcpowmod($int, $e, $n);
    }

    /**
     * Decrypt integer with private key.
     *
     * @param $int
     * @param $n
     * @param $d
     *
     * @return int
     */
    private function calcIntOfRSA($int, $n, $d)
    {
        return bcpowmod($int, $d, $n);
    }

    /**
     * Calculate d and e on an intelligent way.
     *
     * @param $phi
     * @param $d
     * @param $e
     */
    private function calcDE($phi, &$d, &$e)
    {
        if ($phi < 100000) {
            $this->calcDEStupid($phi, $d, $e);
        } else {
            $e = $this->primeNumbers[3]; //mt_rand(0, $this->nrPrimeNumbers - 1)];

            $d = $this->invmod($e, $phi);
        }
    }

    /**
     * Calculate d and e on a stupid way.
     *
     * @param $phi
     * @param $d
     * @param $e
     */
    private function calcDEStupid($phi, &$d, &$e)
    {
        set_time_limit(30);
        for ($e = 0, $d = 0, $de = bcadd($phi, 1); bccomp($e, 0) == 0 && bccomp($d, 0) == 0; $de = bcadd($de, $phi)) {
            for ($dTest = 2; bccomp($dTest, bcsub($de, 1)) < 0; $dTest = bcadd($dTest, 1)) {
                if (bccomp(bcmod($de, $dTest), 0) == 0) {
                    $eTest = bcdiv($de, $dTest);
                    $d = bcadd($dTest, 0, 0);
                    $e = bcadd($eTest, 0, 0);
                    break;
                }
            }
        }
    }

    /**
     * Calculate the inverse modulo.
     *
     * @param $a
     * @param $b
     *
     * @return int
     */
    private function invmod($a, $b)
    {
        $b0 = $b;
        $x0 = 0;
        $x1 = 1;
        if (bccomp($b, 1) == 0) {
            return 1;
        }
        while (bccomp($a, 1) > 0) {
            $q = bcdiv($a, $b);
            $t = $b;
            $b = bcmod($a, $b);
            $a = $t;
            $t = $x0;
            $x0 = bcsub($x1, bcmul($q, $x0));
            $x1 = $t;
        }
        if (bccomp($x1, 0) < 0) {
            $x1 = bcadd($x1, $b0);
        }
        return $x1;
    }


    /***************************************************
     * Display code functions used on the '/how' page. *
     ***************************************************/

    /**
     * @return string The sourcecode of this bundle.
     */
    private function getSourceCode()
    {
        $controller = highlight_string($this->getControllerCode(), true);
        $entity = highlight_string($this->getEntityCode(), true);

        $return = "";
        $return .= <<<EOF
<p><strong>Controller Code</strong></p>
<pre style="word-wrap: break-word;"><code>$controller</code></pre>
<p><strong>Entity Code</strong></p>
<pre style="word-wrap: break-word;">$entity</pre>
EOF;
        $templates = $this->getTemplateCodes();
        foreach ($templates as $template) {
            $return .= "<p><strong>Template Code</strong></p>";
            $return .= "<pre style=\"word-wrap: break-word;\"><code>" . htmlspecialchars($template) . "</code></pre>";
        }

        return $return;
    }

    private function getControllerCode()
    {
        $class = get_class($this);

        $r = new \ReflectionClass($class);

        $code = file($r->getFilename());

        return implode('', $code);
    }

    private function getEntityCode()
    {
        $class = get_class(new RSAEntity());

        $r = new \ReflectionClass($class);

        $code = file($r->getFilename());

        return implode('', $code);
    }

    private function getTemplateCodes()
    {
        $finder = new Finder();
        $finder->files()->in(__DIR__ . '/../Resources/views');

        $return = array();
        foreach ($finder as $file) {
            // Print the absolute path
            $return[] = file_get_contents($file->getRealpath());
        }

        return $return;
    }
}
