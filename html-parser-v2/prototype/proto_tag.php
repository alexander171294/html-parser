<?php

class ProtoTag
{
    public $name = null;
    public $isClosedTag = false;
    public $params = array();
    public $content = null;
    public $positionsOpenTag = array();
    public $positionsCloseTag = array();
    
    public function completeContent($file)
    {
        $this->content = substr($file, $this->positionsOpenTag['end'], $this->positionsCloseTag['start']-$this->positionsOpenTag['end']);
        $this->content = trim($this->content);
    }
}