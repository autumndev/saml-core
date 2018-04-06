<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services\messages;


use craft\base\Component;
use flipbox\saml\core\AbstractPlugin;
use flipbox\saml\core\helpers\SecurityHelper;
use flipbox\saml\core\records\ProviderInterface;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Model\AbstractSamlModel;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\LogoutRequest as LogoutRequestModel;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\StatusResponse;
use LightSaml\SamlConstants;
use yii\base\Event;

abstract class AbstractLogoutResponse extends AbstractLogout implements SamlResponseInterface
{
    use EnsureSamlPlugin;

    const EVENT_AFTER_MESSAGE_CREATED = 'eventAfterMessageCreated';

    /**
     * @inheritdoc
     */
    public function create(AbstractRequest $samlMessage, array $config = []): StatusResponse
    {
        /** @var LogoutRequestModel $request */
        $request = $samlMessage;

        /**
         * NOTE: $provider is the remote provider
         */
        $provider = $this->getSamlPlugin()->getHttpPost()->getProviderByIssuer(
            $request->getIssuer()
        );

        $ownProvider = $this->getSamlPlugin()->getProvider()->findOwn();

        $logout = new LogoutResponse();

        /**
         * Set remote destination
         */
        $logout->setDestination(

            $provider->getType() === AbstractPlugin::SP ?
                $provider->getMetadataModel()->getFirstSpSsoDescriptor()->getFirstSingleLogoutService(
                /**
                 * We only support post right now
                 */
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )->getLocation() :
                $provider->getMetadataModel()->getFirstIdpSsoDescriptor()->getFirstSingleLogoutService(
                /**
                 * We only support post right now
                 */
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )->getLocation()
        );

        /**
         * Set session id
         */
        $logout->setInResponseTo(
            $request->getSessionIndex()
        );

        /**
         * Set issuer
         */
        $logout->setIssuer(
            $issuer = new Issuer(
                $ownProvider->getEntityId()
            )
        );

        /**
         * Sign the message
         */
        if ($ownProvider->keychain) {
            SecurityHelper::signMessage($logout, $ownProvider->keychain);
        }

        /**
         * Kick off event here so people can manipulate this object if needed
         */
        $event = new Event();
        /**
         * request
         */
        $event->sender = $samlMessage;
        /**
         * response
         */
        $event->data = $logout;
        $this->trigger(static::EVENT_AFTER_MESSAGE_CREATED, $event);

        return $logout;
    }
}