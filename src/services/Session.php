<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 */

namespace flipbox\saml\core\services;


use craft\base\Component;

class Session extends Component
{
    const CORE_NAMESPACE = 'saml.core';
    const REQUEST_ID_KEY = 'request.id';

    /**
     * @param $id
     */
    public function setRequestId($id)
    {
        return \Craft::$app->session->set(
            $this->getName(static::REQUEST_ID_KEY),
            $id
        );

    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return \Craft::$app->session->get(
            $this->getName(static::REQUEST_ID_KEY)
        );
    }

    /**
     * @param $key
     * @return string
     */
    protected function getName($key)
    {
        return static::CORE_NAMESPACE . '/' . $key;
    }

}