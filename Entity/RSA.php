<?php

namespace Cmfcmf\Bundle\RSABundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * RSA
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class RSA
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="p", type="integer")
     * @Assert\NotBlank()
     */
    private $p;

    /**
     * @var integer
     *
     * @ORM\Column(name="q", type="integer")
     * @Assert\NotBlank()
     */
    private $q;

    /**
     * @var integer
     *
     * @ORM\Column(name="n", type="integer")
     */
    private $n;

    /**
     * @var integer
     *
     * @ORM\Column(name="phi", type="integer")
     */
    private $phi;

    /**
     * @var integer
     *
     * @ORM\Column(name="d", type="integer")
     */
    private $d;

    /**
     * @var integer
     *
     * @ORM\Column(name="e", type="integer")
     */
    private $e;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set p
     *
     * @param integer $p
     * @return RSA
     */
    public function setP($p)
    {
        $this->p = $p;
    
        return $this;
    }

    /**
     * Get p
     *
     * @return integer 
     */
    public function getP()
    {
        return $this->p;
    }

    /**
     * Set q
     *
     * @param integer $q
     * @return RSA
     */
    public function setQ($q)
    {
        $this->q = $q;
    
        return $this;
    }

    /**
     * Get q
     *
     * @return integer 
     */
    public function getQ()
    {
        return $this->q;
    }

    /**
     * Set n
     *
     * @param integer $n
     * @return RSA
     */
    public function setN($n)
    {
        $this->n = $n;
    
        return $this;
    }

    /**
     * Get n
     *
     * @return integer 
     */
    public function getN()
    {
        return $this->n;
    }

    /**
     * Set phi
     *
     * @param integer $phi
     * @return RSA
     */
    public function setPhi($phi)
    {
        $this->phi = $phi;
    
        return $this;
    }

    /**
     * Get phi
     *
     * @return integer 
     */
    public function getPhi()
    {
        return $this->phi;
    }

    /**
     * Set d
     *
     * @param integer $d
     * @return RSA
     */
    public function setD($d)
    {
        $this->d = $d;
    
        return $this;
    }

    /**
     * Get d
     *
     * @return integer 
     */
    public function getD()
    {
        return $this->d;
    }

    /**
     * Set e
     *
     * @param integer $e
     * @return RSA
     */
    public function setE($e)
    {
        $this->e = $e;
    
        return $this;
    }

    /**
     * Get e
     *
     * @return integer 
     */
    public function getE()
    {
        return $this->e;
    }
}
