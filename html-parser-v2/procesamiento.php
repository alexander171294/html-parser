<?php

require(__dir__.'/prototype/proto_tag.php');

trait procesamiento
{
    
    protected $regexTag = '/<([a-zA-Z0-9]+)([^>]*)\>/m';
    protected $regexAttribs = '/([a-zA-Z-]+)=("|\')([^"]*)("|\')/';
    protected $regexTagEnd = '/\/$/';
    protected $regexEndTag = '/<\/{???}([\s]*)\>/m';
    
    public $completeContents = true;
    
    protected function preProcesateHTML($file, $completeContents = true)
    {
        $this->completeContents = $completeContents;
        $offset = 0;
        return $this->getNextTag($file, $offset);
    }
   
    protected function getNextTag($file, &$offset, $parentTag = null, &$endPositions = null)
    {
        $result = null;
        // Buscamos una nueva etiqueta
        if($this->findTag($file, $result, $offset))
        {
            // hay una etiqueta de cierre entre el originOffset y el nuevo offset?
            if(!$this->findTagCloser($file, $offset, $result->positionsOpenTag['start'], $parentTag, $endPositions))
            {
                // si no hay una etiqueta de cierre entre medio podemos seguir subiendo niveles
                $offset = $result->positionsOpenTag['end'];
                // es una etiqueta sin auto-cierre?
                if(!$result->isClosedTag)
                {
                    // buscamos la siguiente etiqueta creando un nivel
                    // ADVERTENCIA****------------------------------------------- REVISAR SI EXISTE AL MENOS UNA ETIQUETA QUE CIERRE ESTO o tomarlo como autocierre.
                    $out = array();
                    $out['tag'] = $result; 
                    while($r = $this->getNextTag($file, $offset, $result->name, $endPositions))
                    {
                        $out[] = $r;
                    }
                    $out['tag']->positionsCloseTag = $endPositions;
                    if($this->completeContents)
                        $out['tag']->completeContent($file);
                    return $out;
                } else { // es una de auto-cierre (es decir como un <br /> o un IMG)
                    // añadirlo al resultado
                    // seguimos sin crear subnivel
                    return array('tag' => $result);
                }
            } else {
                // si hay una etiqueta de cierre en el medio, hay que bajar un nivel.
                return false;
            }
        }
        if(!empty($parentTag))
        {
            $this->findTagCloser($file, $offset, strlen($file), $parentTag, $endPositions);
        }
        return false;
    }
   
    protected function findTag($file, &$result, $offset)
    {
        $match = null;
        if(preg_match($this->regexTag, $file, $match, PREG_OFFSET_CAPTURE, $offset))
        {
            $result = $this->analyzeTag($match);
            return true;
        } else return false;
    }
    
    protected function analyzeTag($tag)
    {
        $tagName = trim($tag[1][0]);
        $tagStart = $tag[0][1];
        // el ultimo match + tamaño del mismo + caracter de cierre de tag (>)
        $tagEnd = $tag[2][1]+strlen($tag[2][0])+1;
        $tagObject = new ProtoTag();
        $tagObject->name = $tagName;
        $tagObject->positionsOpenTag = array('start' => $tagStart, 'end' => $tagEnd);
        $this->setTagAttrib($tagObject, $tag[2][0]);
        return $tagObject;
    }
    
    protected function setTagAttrib(&$tagObject, $attribs)
    {
        $attribs = trim($attribs);
        $matchs = null;
        preg_match_all($this->regexAttribs, $attribs, $matchs, PREG_SET_ORDER);
        $tagObject->params = array();
        foreach($matchs as $match)
        {
            $paramName = $match[1];
            $paramValue = $match[3];
            $tagObject->params[$paramName] = $paramValue;
        }
        if(preg_match($this->regexTagEnd, $attribs, $match))
        {
            $tagObject->isClosedTag = true;
        }
        return true;
    }
    
    protected function findTagCloser($file, &$offset, $end, $tag, &$positions)
    {
        $match = null;
        $realRegex = $this->regexEndTag;
        $realRegex = str_replace('{???}', preg_quote($tag), $realRegex);
        if(preg_match($realRegex, $file, $match, PREG_OFFSET_CAPTURE, $offset))
        {
            $endFinded = $match[1][1];
            if($endFinded < $end)
            {
                $positions = array();
                $positions['start'] = $match[0][1];
                $positions['end'] = $match[1][1]+strlen($match[1][0])+1;
                $offset = $positions['end'];
                return true;
            }
            return false;
        }
        return false;
    }
}