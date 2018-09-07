<?php

namespace MainBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use MainBundle\Validator\Constraints\ContainsMail as MailSuffixe;

/**
 * Description of User
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser 
{    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @MailSuffixe
     */
    protected $email;

    /**
     * var $aceTheme
     * @ORM\Column(type="string")
     */
    protected $aceTheme = "tomorrow_night"; // to be removed

    /**
     * var $consoleTheme
     * @ORM\Column(type="string")
     */
    protected $consoleTheme = "dark"; // To be removed

    /**
     * var $sizeEditeur
     * @ORM\Column(type="integer")
     */
    protected $sizeEditeur = 12; //To be removed

    /**
     * var $configuration
     * @ORM\Column(type="json_array")
     */
    protected $configuration= [
        'editorFontSize' => 12,
        'consoleTheme' => 'dark',
        'editorTheme' => 'tomorrow_night'
    ];
   
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTime $date=null)
    {
        $this->expiresAt = $date;
    } 
    
    public function setCredentialsExpireAt(\DateTime $date=null)
    {
        $this->expiresAt = $date;
    }  
    
    public  function getCredentialsExpireAt()
    {        
        return $this->credentialsExpireAt;
    }

    public function getAceTheme()
    {
        return $this->aceTheme;
    }

    public function setAceTheme($aceTheme)
    {
        $this->aceTheme = $aceTheme;
    }  

    public function getConsoleTheme()
    {
        return $this->consoleTheme;
    }

    public function setConsoleTheme($consoleTheme)
    {
        $this->consoleTheme = $consoleTheme;
    }  

    public function getSizeEditeur()
    {
        return $this->sizeEditeur;
    }

    public function setSizeEditeur($sizeEditeur)
    {
        $this->sizeEditeur = $sizeEditeur;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param mixed $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }
}
