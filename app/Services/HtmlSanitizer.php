<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', __DIR__ . '/../../storage/cache');
        $config->set('HTML.Doctype', 'HTML5');
        $config->set('Attr.AllowedRel', ['nofollow', 'noopener', 'noreferrer']);
        $config->set('HTML.Allowed', 'p,br,ul,ol,li,strong,em,a[href],code,blockquote');
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('URI.MakeAbsolute', false);
        $this->purifier = new HTMLPurifier($config);
    }

    public function clean(string $html): string
    {
        return $this->purifier->purify($html);
    }
}

