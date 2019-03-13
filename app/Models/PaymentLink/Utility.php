<?php

namespace RZP\Models\PaymentLink;

class Utility
{
    /**
     * It converts the input string into quill format string.
     *
     * @param string $text
     *
     * @return string Quill Format string
     */
    public static function convertTextToQuillFormat(string $text): string
    {
        // Todo: Refactor this logic. There are simple implementation alternatives.

        $quillJSObj = [];

        $isTokenLink = false;

        $tokens = explode(' ', $text);

        foreach ($tokens as $key => $token)
        {
            if (Utility::isTextLink($token) === true)
            {
                $isTokenLink = true;

                $quillJSObj[] = [
                    'insert'     => $token,
                    'attributes' => [
                        'link' => $token,
                    ]
                ];
            }
            else
            {
                if ($isTokenLink === false && sizeof($quillJSObj) > 0)
                {
                    $quillJSObj[sizeof($quillJSObj) - 1]['insert'] .= ' ' . $token;
                }
                else
                {
                    $quillJSObj[] = [
                        'insert' => $token,
                    ];
                }

                $isTokenLink = false;
            }
        }

        return json_encode(['value' => $quillJSObj, 'metaText' => $text]);
    }

    /**
     * It checks if a input text is a url.
     *
     * @param string $text
     *
     * @return bool
     */
    public static function isTextLink(string $text): bool
    {
        $replacePattern1 = '/(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/';
        $replacePattern2 = '/(^|[^\/])(www\.[\S]+(\b|$))/';
        $replacePattern3 = '/(([a-zA-Z0-9\-\_\.])+@[a-zA-Z\_]+?(\.[a-zA-Z]{2,6})+)/';

        return (preg_match($replacePattern1, $text) || preg_match($replacePattern2, $text) ||
                preg_match($replacePattern3, $text));
    }
}
