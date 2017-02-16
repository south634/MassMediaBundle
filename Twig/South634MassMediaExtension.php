<?php

namespace South634\MassMediaBundle\Twig;

use South634\MassMediaBundle\Util\MassMediaManager;

class South634MassMediaExtension extends \Twig_Extension
{
    /**
     * @var MassMediaManager 
     */
    private $mmm;
    
    /**
     * Constructor
     * 
     * @param MassMediaManager $mmm
     */
    public function __construct(MassMediaManager $mmm)
    {
        $this->mmm = $mmm;
    }
    
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('mass_media_web_path', [$this, 'getWebPath']),
        ];
    }
    
    /**
     * Gets web path for file from its filename
     * 
     * @param string $fileName
     * @return string
     */
    public function getWebPath($fileName)
    {
        return $this->mmm->getWebPath($fileName);
    }
    
    public function getName()
    {
        return 'south634_mass_media_extension';
    }    
}