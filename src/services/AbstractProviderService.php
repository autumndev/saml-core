<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/10/18
 * Time: 12:12 AM
 */

namespace flipbox\saml\core\services;


use craft\base\Component;
use craft\helpers\Json;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\records\AbstractProvider;
use flipbox\saml\core\records\LinkRecord;
use flipbox\saml\core\records\ProviderInterface;

abstract class AbstractProviderService extends Component
{
    /**
     * @var ProviderInterface[]
     */
    private $cache = [];

    /**
     * @return string
     */
    abstract public function getRecordClass();

    /**
     * @return AbstractProvider
     */
    abstract public function findOwn(): AbstractProvider;

    /**
     * @param array $condition
     * @return AbstractProvider
     */
    public function find($condition = [])
    {
        /** @var AbstractProvider $class */
        $class = $this->getRecordClass();
        if (isset($condition['entityId']) && isset($this->cache[$condition['entityId']])) {

            return $this->cache[$condition['entityId']];
        }

        /** @var AbstractProvider $provider */
        $provider = $class::find()->where($condition)->one();

        $this->cache[$provider->getEntityId()] = $provider;
        return $provider;
    }

    /**
     * @return AbstractProvider
     */
    public function findByIdp()
    {
        return $this->findByType('idp');
    }

    /**
     * @return AbstractProvider
     */
    public function findBySp()
    {
        return $this->findByType('sp');
    }

    /**
     * @param $type
     * @return AbstractProvider
     */
    protected function findByType($type)
    {
        if (! in_array($type, ['sp', 'idp'])) {
            throw new \InvalidArgumentException("Type must be idp or sp.");
        }
        return $this->find([
            'enabled'      => 1,
            'providerType' => $type,
        ]);
    }

    /**
     * @param $entityId
     * @return AbstractProvider
     */
    public function findByEntityId($entityId)
    {
        return $this->find([
            'entityId' => $entityId,
        ]);
    }

    /**
     * @param AbstractProvider $record
     * @param bool $runValidation
     * @param null $attributeNames
     * @return AbstractProvider
     * @throws \Exception
     */
    public function save(AbstractProvider $record, $runValidation = true, $attributeNames = null)
    {
        if ($record->isNewRecord) {
            $record->loadDefaultValues();
        }

        //save record
        if (! $record->save($runValidation, $attributeNames)) {
            throw new \Exception(Json::encode($record->getErrors()));
        }

        return $record;
    }

    /**
     * @param AbstractProvider $provider
     * @param KeyChainRecord $keyChain
     * @param bool $runValidation
     * @param null $attributeNames
     * @throws \Exception
     */
    public function linkToKey(
        AbstractProvider $provider,
        KeyChainRecord $keyChain,
        $runValidation = true,
        $attributeNames = null
    )
    {
        if (! $provider->id && ! $keyChain->id) {
            throw new \Exception('Provider id and keychain id must exist before linking.');
        }
        $linkAttributes = [
            'providerId' => $provider->id,
            'keyChainId' => $keyChain->id,
        ];
        if (! $link = LinkRecord::find()->where($linkAttributes)->one()) {
            $link = new LinkRecord($linkAttributes);
        }
        if (! $link->save($runValidation, $attributeNames)) {
            throw new \Exception(Json::encode($record->getErrors()));
        }
    }

}