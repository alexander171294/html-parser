<?php

/*
 * Find Selectors:
 * # for id / or [id=value]
 * . for class / or [class=value]
 * TAGNAME for tagname
 * [property=value]
 * space for divide selectors (or >)
 *
 * example:
 * div.example p#InternalP
 * div.example > p#InternalP
 * div[class=example] > p[id=InternalP]
 * div[class=example] p[id=InternalP]
 *
 * select option[selected]
 * input[type=text]
 * select option[!selected] que no tenga el atributo selected
 * input[type!=text] que el atributo type no sea text
 * 
 * Multiple Class:
 * div.example.winner
 * div[class=example winner]
 * div[class=example][id=mainExample][type=text]
 *
 * Get Number specific
 * div{1} obtiene el div 1 (segundo)
 * div{0..3} obtiene del div 0 al 3
 * div{2..} obtiene del div 2 al ultimo
 * div.wea{4} obtiene el div con class wea número 4
 * div{..3} obtiene contando de atras para adelante el 3 div
 * div{3..3} obtiene del div ultimo al 3
 * div{..} obtiene el último div ¿:last-child? ¿quien te conoce?
 */

class ProtoFinder
{
    protected $structure = null;
    protected $regexSplit = '/(\s>\s|>|\s)/';
    protected $regexID = '/#([a-zA-Z0-9-_]+)/';
    protected $regexClass = '/\.([a-zA-Z0-9-_]+)/';
    protected $regexSelectors = '/\[([a-zA-Z0-9-_]+)=([a-zA-Z0-9-_]+)\]/';
    protected $regexTag = '/([a-zA-Z0-9-_]+)/';
    protected $regexNumbers = '/\{([0-9]*)(\.\.)?([0-9]*)\}/';
    
    public function __construct($structure)
    {
        $this->structure = $structure;
    }
    
    public function find($selector)
    {
        // dividimos los grupos de selectores
        $layers = $this->splitLayers($selector);
        $structure = $this->structure;
        // vamos buscando y obteniendo el resultado
        foreach($layers as $layerSelector)
        {
            $structure = $this->findIn($structure, $layerSelector);
        }
        // devolvemos la estructura final
        return $structure;
    }
    
    protected function findIn($structure, $layerSelector)
    {
        $index = null;
        $groupSearch = $this->evaluateSelector($layerSelector);
        if(isset($groupSearch['filter']))
            $index = $groupSearch['filter'];
        $search = $this->getOfIndex($this->getTagFiltered($structure, $groupSearch), $index);
        return $search;
    }
    
    protected function splitLayers($selector)
    {
        $matches = null;
        if(preg_match_all($this->regexSplit, $selector, $matches, PREG_OFFSET_CAPTURE))
        {
            $out = array();
            foreach($matches[0] as $key => $match)
            {
                $i[] = $match[1]+strlen($match[0]);
                if(isset($i[$key-1]))
                    $prev[] = $i[$key-1];
                else
                    $prev[] = 0;
            }
            foreach($i as $key => $individual)
            {
                $out[] = substr($selector, $prev[$key], $individual-$prev[$key]);
            }
            $out[] = substr($selector, $individual, strlen($selector)-$individual);
            $out = preg_replace($this->regexSplit, null, $out);
            return $out;
        }
        return array($selector);
    }
    
    protected function evaluateSelector($selector)
    {
        $match = null;
        if(preg_match($this->regexNumbers, $selector, $match))
        {
            if(!empty($match[1]) && empty($match[3]) && empty($match[2]))
            {
                // {x}
                $filter['type'] = 'FirstX';
                $filter['x'] = $match[1];
            } else if(!empty($match[1]) && empty($match[3]) && $match[2] == '..')
            {
                // {x..}
                $filter['type'] = 'XToEnd';
                $filter['x'] = $match[1];
            } else if(!empty($match[1]) && !empty($match[3]) && $match[2] == '..')
            {
                // {x..y}
                // si x <= y desde x hasta y
                $filter['x'] = $match[1];
                $filter['y'] = $match[3];
                if($filter['x'] < $filter['y'])
                    $filter['type'] = 'XToY';
                if($filter['x'] == $filter['y'])
                    $filter['type'] = 'LastToY';
            } else if(empty($match[1]) && !empty($match[3]) && $match[2] == '..')
            {
                // {..y}
                $filter['type'] = 'YFromLast';
                $filter['y'] = $match[3];
            } else if(empty($match[1]) && empty($match[3]) && $match[2] == '..')
            {
                // {..}
                $filter['type'] = 'Last';
            }
            $selector = preg_replace($this->regexNumbers, null, $selector);
        }
        // procesar clases 
        $selector = preg_replace($this->regexID, '[id=$1]', $selector);
        // procesar ids
        $selector = preg_replace($this->regexClass, '[class=$1]', $selector);
        // groups
        $matchs = null;
        $groups = array();
        if(preg_match_all($this->regexSelectors, $selector, $matchs, PREG_SET_ORDER))
        {
            foreach($matchs as $match)
            {
                $groups[] = array('atribute' => $match[1], 'value' => $match[2]);
            }
        }
        $selector = preg_replace($this->regexSelectors, null, $selector);
        $match = null;
        if(preg_match($this->regexTag, $selector, $match))
        {
            $groups['tag'] = $match[0];
        }
        if(isset($filter))
        {
            $groups['filter'] = $filter;
        }
        return $groups;
    }
    
    protected function getTagFiltered($structure, $groupFilter)
    {
        // get the tag if exist
        if(isset($groupFilter['tag']))
        {
            $tag = $groupFilter['tag'];
            unset($groupFilter['tag']);
            $filter = null;
            if(isset($groupFilter['filter']))
            {
                $filter = $groupFilter['filter'];
                unset($groupFilter['filter']);
            }
            return $this->searchInLvl($structure, array_values($groupFilter), $tag, $filter);
        } else
            $filter = null;
            if(isset($groupFilter['filter']))
            {
                $filter = $groupFilter['filter'];
                unset($groupFilter['filter']);
            }
            return $this->searchInLvl($structure, array_values($groupFilter), null, $filter);
    }
    
    protected function searchInLvl($layer, $filters, $tag=null, $index = null)
    {
        $out = array();
        foreach($layer as $key => $object)
        {
            // comprobamos si este tag cumple los requisitos
            if($key === 'tag')
            {
                // si usa tag
                if($tag !== null)
                {
                    // si el tag corresponde y los filtros también
                    if($object->name === $tag && $this->evaluateFilters($filters, $object))
                        $out[] = $layer;
                }
                else
                {
                    // si los filtros corresponden
                    if($this->evaluateFilters($filters, $object))
                        $out[] = $layer;
                }
            } else {
                // buscamos en el resto de los objetos internos
                $out_children = $this->searchInLvl($object, $filters, $tag, $index);
                if(count($out_children)>0)
                    $out = array_merge($out, $out_children);
            }
        }
        return $out;
    }
    
    protected function evaluateFilters($filters, $object)
    {
        // se cumplen todos los filtros?
        foreach($filters as $key => $filter)
        {
            
            // existe este atributo?
            if(isset($object->params[$filter['atribute']]))
            {
                // tiene este valor?
                if(strpos($object->params[$filter['atribute']], $filter['value']) === false) return false;
            } else return false;
        }
        return true;
    }
    
    protected function getOfIndex($preOut, $index)
    {
        // obtener el específico por cantidad //
        if(is_array($index))
        {
            if($index['type'] == 'Last' && count($preOut) > 0)
            {
                return array($preOut[count($preOut)-1]);
            }
            if($index['type'] == 'YFromLast' && count($preOut) > 0)
            {
                // obtener el Y contando desde el último {..3}
                if(isset($preOut[count($preOut)-$index['y']]))
                    return $preOut[count($preOut)-$index['y']];
                else
                    return array();
            }
            if($index['type'] == 'LastToY')
            {
                // obtener desde el último hasta y {3..3}
                if(isset($preOut[count($preOut)-$index['y']]))
                    return array_slice($preOut, count($preOut)-$index['y'], count($preOut)-1);
                else
                    return array();
            }
            if($index['type'] == 'XToY')
            {
                // obtener de x a y {3..4}
                // hay suficientes indices?
                if(count($preOut) >= $index['y']-1)
                {
                    return array_slice($preOut, $index['x']-1, $index['y']-$index['x']+1);
                } else { // sino hasta el último
                    return array_slice($preOut, $index['x']-1, count($preOut)-$index['x']);
                }
            }
            if($index['type'] == 'FirstX')
            {
                if(isset($preOut[$index['x']-1]))
                    return array($preOut[$index['x']-1]);
                else
                    return array();
            }
        }
        return $preOut;
    }
}