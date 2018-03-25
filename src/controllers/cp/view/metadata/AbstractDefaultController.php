<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\controllers\cp\view\metadata;


use Craft;
use craft\helpers\UrlHelper;
use flipbox\saml\core\controllers\cp\view\AbstractController;

abstract class AbstractDefaultController extends AbstractController
{

    const TEMPLATE_INDEX = DIRECTORY_SEPARATOR . '_cp' . DIRECTORY_SEPARATOR . 'metadata';

    public function actionIndex()
    {

        $variables['crumbs'] = [
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getUniqueId()),
                'label' => 'SSO Provider'
            ],
            [
                'url'   => UrlHelper::cpUrl($this->getSamlPlugin()->getUniqueId()) . '/metadata',
                'label' => 'Metadata List'
            ],
        ];
        $variables['myProvider'] = null;
        foreach ($this->getProviderRecord()::find()->all() as $provider) {
            $variables['providers'][] = $provider;
            if ($provider->enabled && $provider->getEntityId() == $this->getSamlPlugin()->getSettings()->getEntityId()) {
                $variables['myProvider'] = $provider;
            }
        }
        $variables['pluginHandle'] = $this->getSamlPlugin()->getUniqueId();

        $variables['title'] = Craft::t($this->getSamlPlugin()->getUniqueId(), $this->getSamlPlugin()->name);
        return $this->renderTemplate(
            $this->getTemplateIndex() . static::TEMPLATE_INDEX,
            $variables
        );
    }

}