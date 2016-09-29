<?php

require('procesamiento.php');
require('curl.php');
require(__dir__.'/prototype/proto_finder.php');

class html_parser
{
    use curl, procesamiento;
    
    protected $file = null;
    static public $processContent = true;
    public $debugMode = true;
    
    public function __construct($file, $postFields = null)
    {
        $protocol = parse_url($file, PHP_URL_SCHEME);
        if($protocol !== false && ($protocol == 'http' || $protocol == 'https'))
        {
            if(is_array($postFields))
                $this->file = $this->curlPost($file, $postFields);
            else
                $this->file = $this->curlGet($file);
        }
        else
        {
            $this->file = file_get_contents($file);
        }
    }
    
    public function getObjects()
    {
        $structure = $this->preProcesateHTML($this->file, self::$processContent);
        return new ProtoFinder($structure);
    }
    
    public function dump_array()
    {
        var_dump($this->HTMLarray);
    }
}