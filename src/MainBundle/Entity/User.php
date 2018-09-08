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
        return $this->configuration['editorTheme'];
    }

    public function setAceTheme($aceTheme)
    {
        $this->configuration['editorTheme'] = $aceTheme;
    }  

    public function getConsoleTheme()
    {
        return $this->configuration['consoleTheme'];
    }

    public function setConsoleTheme($consoleTheme)
    {
        $this->configuration['consoleTheme'] = $consoleTheme;
    }  

    public function getSizeEditeur()
    {
        return $this->configuration['editorFontSize'];
    }

    public function setSizeEditeur($editorFontSize)
    {
        $this->configuration['editorFontSize'] = $editorFontSize;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }
}
