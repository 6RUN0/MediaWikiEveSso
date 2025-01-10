<?php

declare(strict_types=1);

namespace MediaWiki\Extension\MWEVESSO;

use MediaWiki\Skin\SkinComponentUtils;

/**
 * This class contains helper methods
 */
class Helper
{
    /**
     * Get redirect url
     *
     * @param string|null $returnto
     *   The returnto parameter for the redirect
     *
     * @return string
     *   The redirect url
     */
    public static function getRedirecUrl(?string $returnto = null): string
    {
        if (is_null($returnto)) {
            $returnto = '';
        } else {
            $returnto = wfArrayToCGI(['returnto' => $returnto]);
        }
        return SkinComponentUtils::makeSpecialUrlSubpage(
            'OAuth2Client',
            'redirect',
            $returnto
        );
    }

    /**
     * Translate message
     *
     * @param string $key
     *   The key of the message
     *
     * @return string
     */
    public static function t(string $key): string
    {
        return wfMessage($key)->text();
    }
}
