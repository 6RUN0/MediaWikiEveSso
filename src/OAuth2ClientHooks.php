<?php

namespace MediaWiki\Extension\MWEVESSO;

/**
 * OAuth2Client.php
 * Based on TwitterLogin by David Raison, which is based on the guideline published by Dave Challis at http://blogs.ecs.soton.ac.uk/webteam/2010/04/13/254/
 * @license: LGPL (GNU Lesser General Public License) http://www.gnu.org/licenses/lgpl.html
 *
 * @file OAuth2Client.php
 * @ingroup OAuth2Client
 *
 * @author Joost de Keijzer
 * @author Nischay Nahata for Schine GmbH
 *
 * Uses the OAuth2 library https://github.com/thephpleague/oauth2-client
 *
 */

use MediaWiki\Auth\AuthManager;
use MediaWiki\Auth\Hook\SecuritySensitiveOperationStatusHook;
use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Request\WebRequest;
use MediaWiki\SpecialPage\Hook\AuthChangeFormFieldsHook;
use MediaWiki\Title\Title;
use Skin;

class OAuth2ClientHooks implements
    AuthChangeFormFieldsHook,
    GetPreferencesHook,
    SecuritySensitiveOperationStatusHook,
    SkinTemplateNavigation__UniversalHook
{
    /**
     * {@inheritdoc}
     */
    public function onAuthChangeFormFields(
        $requests,
        $fieldInfo,
        &$formDescriptor,
        $action
    ) {
        $url = "/index.php?title=Special:OAuth2Client/redirect";
        $ret = $this->_getRequest()->getVal("returnto");
        if (!is_null($ret)) {
            $url .= "&returnto=" . $ret;
        }
        $formDescriptor["SSOLogin"] = [
            "section" => "oauth-login",
            "type" => "info",
            "default" => '<div style="text-align: center"><a class = "btn_mwevesso_login" href="' . $url . '">Log in with Eve Online</a></div>',
            "raw" => true
        ];
        $this->_getOutput()->addModules(['mw.evesso.login-page']);
    }

    /**
     * Get the request
     *
     * @return WebRequest
     */
    private function _getRequest(): WebRequest
    {
        return RequestContext::getMain()->getRequest();
    }

    /**
     * Get the output
     *
     * @return OutputPage
     */
    private function _getOutput(): OutputPage
    {
        return RequestContext::getMain()->getOutput();
    }

    /**
     * {@inheritdoc}
     */
    public function onSecuritySensitiveOperationStatus(
        &$status,
        $operation,
        $session,
        $timeSinceAuth
    ) {
        if ($operation !== "ChangeEmail") {
            return true;
        }
        $time = time();
        $login = intval($session->getSecret("hugs", 0));
        $delta = abs($time - $login);
        if ($delta < 300) {
            $status = AuthManager::SEC_OK;
            return;
        }
        $status = AuthManager::SEC_REAUTH;
    }

    /**
     * {@inheritdoc}
     */
    public function onGetPreferences($user, &$preferences)
    {
        $preferences['oauth-persist'] = [
            'type' => 'toggle',
            'label-message' => 'oauth-persist',
            'section' => 'misc'
        ];
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function onSkinTemplateNavigation__Universal($sktemplate, &$links): void
    {

        global $wgOAuth2Client;

        $user = RequestContext::getMain()->getUser();
        if ($user->isRegistered()) {
            return;
        }

        $title = $this->_getRequest()->getVal('title', '');
        if (is_null($title)) {
            $title = '';
        }
        $page = Title::newFromURL($title);

        $inExt = (null == $page || ('OAuth2Client' == substr($page->getText(), 0, 12)) || strstr($page->getText(), 'Logout'));
        /** @var array<string,array> $links */
        $links['user-menu']['anon_oauth_login'] = [ // @phpstan-ignore missingType.iterableValue
            'text' => 'LOG IN with EVE Online',
            'class' => 'btn_mwevesso_login',
            'active' => false
        ];
        if ($inExt) {
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $links['user-menu']['anon_oauth_login']['href'] = Skin::makeSpecialUrlSubpage('OAuth2Client', 'redirect');
        } else {
            // @phpstan-ignore offsetAccess.nonOffsetAccessible
            $links['user-menu']['anon_oauth_login']['href'] = Skin::makeSpecialUrlSubpage(
                'OAuth2Client',
                'redirect',
                wfArrayToCGI(['returnto' => $page])
            );
        }
        $this->_getOutput()->addModules(['mw.evesso.login', 'mw.evesso.modal']);
        // Remove default login links
        unset($links['user-menu']['login']);
        unset($links['user-menu']['anonlogin']);

        // Remove account creation link
        unset($links['user-menu']['createaccount']);
    }

}
