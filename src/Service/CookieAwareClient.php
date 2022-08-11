<?php

namespace App\Service;

use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\HttpBrowser;

class CookieAwareClient
{
    public static function create(array $cookie = null)
    {
        $cookieJar = new CookieJar();
        if ($cookie) {
            $cookieString = '';
            foreach ($cookie as $key => $val) {
                if ('' !== $cookieString) {
                    $cookieString .= '; ';
                }
                $cookieString .= $key.'='.$val;
            }
            $cookie = Cookie::fromString($cookieString);
            $cookieJar->set($cookie);
        }

        return new HttpBrowser(null, null, $cookieJar);
    }
}
