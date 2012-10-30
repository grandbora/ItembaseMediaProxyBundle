<?php

namespace IB\MediaProxyBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Response;

use IB\MediaProxyBundle\Exception\WrongHashException;

class ProxyController extends ContainerAware
{
    /**
     * Action to receive proxied media
     *
     * @param  string $hash
     * @return media data
     * @author Thomas Bretzke
     **/
    public function proxyMediaAction($hash)
    {
        $container = $this->container;
        $url = rawurldecode(
            $container->get('request')->query->get('path')
        );
        $checkHash = hash_hmac(
            $container->getParameter('ib_media_proxy.algorithm'),
            $url,
            $container->getParameter('ib_media_proxy.secret')
        );

        if ($checkHash != rawurldecode($hash)) {
            throw new WrongHashException('Sorry!');
        }

        // Get file with curl
        $curlHandle = curl_init($url);

        // Don't return HTTP headers, just contents!
        curl_setopt($curlHandle, CURLOPT_HEADER, false);
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true); 
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

        // Make the call
        $response = curl_exec($curlHandle);

        $headers = array(
            'Content-Type' => curl_getinfo($curlHandle, CURLINFO_CONTENT_TYPE),
            'Cache-Control' => 'private'
        );

        curl_close($curlHandle);

        return new Response($response, 200, $headers);
    }
}