<?php

trait curl
{
    protected function curlGet($link, $timeOut = 3)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_URL => $link,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => $timeOut,
                //CURLOPT_FILETIME => false,
                CURLOPT_USERAGENT => 'iPhone',
        ));
        $result =  curl_exec($curl);
        curl_close($curl);
        return $result;
    }
    
    public function curlPost($url, $fields = array())
    {
        $fields_string = '';
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string, '&');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT,'iPhone');
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}