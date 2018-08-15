<?php
/**
 * Created by PhpStorm.
 * User: dsmrt
 * Date: 2/9/18
 * Time: 10:51 PM
 */

namespace flipbox\saml\core\records;

use flipbox\ember\helpers\ObjectHelper;
use flipbox\ember\helpers\QueryHelper;
use flipbox\ember\records\ActiveRecord;
use flipbox\keychain\records\KeyChainRecord;
use flipbox\saml\core\helpers\SerializeHelper;
use flipbox\saml\core\traits\EnsureSamlPlugin;
use LightSaml\Model\Metadata\EntityDescriptor;
use yii\db\ActiveQuery;

/**
 * Class AbstractProvider
 * @package flipbox\saml\core\records
 */
abstract class AbstractProvider extends ActiveRecord implements ProviderInterface
{
    const METADATA_HASH_ALGO = 'sha256';

    protected $metadataModel;
    protected $cachedKeychain;

    /**
     * @return string|null
     */
    abstract public function getLoginPath();

    /**
     * @return string|null
     */
    abstract public function getLogoutPath();

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (! $this->entityId) {
            $this->entityId = $this->getEntityId();
        }

        if (is_array($this->mapping)) {
            $this->mapping = json_encode($this->mapping);
        }
        /**
         * Remove the signature if it exists.
         */
        if ($this->getMetadataModel()->getSignature()) {
            $this->removeSignature();
        }

        $this->sha256 = hash(static::METADATA_HASH_ALGO, $this->metadata);

        $this->metadata = SerializeHelper::toXml($this->getMetadataModel());

        return parent::beforeSave($insert);
    }

    /**
     * We don't want to save the signature on the metadata and
     * errors were being thrown during serialization so we
     * will just remove it here, manually from the xml
     * and overwrite the metadata and metadataModel
     *
     * @return void
     */
    protected function removeSignature()
    {
        if ($this->getMetadataModel()->getSignature()) {
            $doc = new \DOMDocument('1.0', 'UTF-8');
            $doc->loadXML($this->metadata);
            $doc->documentElement->removeChild(
                $doc->documentElement->getElementsByTagName('Signature')->item(0)
            );

            $this->metadata = $doc->saveXML();
            $this->metadataModel = null;
        }
    }

    /**
     * @return string
     */
    public function getEntityId()
    {
        $metadata = $this->getMetadataModel();
        return $metadata->getEntityID();
    }

    /**
     * @return EntityDescriptor
     */
    public function getMetadataModel(): EntityDescriptor
    {
        if (! $this->metadataModel) {
            $this->metadataModel = EntityDescriptor::loadXml($this->metadata);
        }

        return $this->metadataModel;
    }

    /**
     * @param EntityDescriptor $descriptor
     * @return $this
     */
    public function setMetadataModel(EntityDescriptor $descriptor)
    {
        $this->metadataModel = $descriptor;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->providerType;
    }

    /**
     * @return ActiveQuery
     */
    public function getLink()
    {
        return $this->hasOne(LinkRecord::class, [
            'providerId' => 'id',
        ]);
    }

    /**
     * @return ActiveQuery
     */
    public function getKeychain()
    {
        return $this->hasOne(KeyChainRecord::class, [
            'id' => 'keyChainId',
        ])->viaTable(LinkRecord::tableName(), [
            'providerId' => 'id',
        ]);
    }

    /**
     * @param KeyChainRecord $keyChain
     * @return AbstractProvider
     */
    public function setKeychain(KeyChainRecord $keyChain)
    {
        $this->populateRelation('keychain', $keyChain);
        return $this;
    }

    /**
     * @return string
     */
    public function getMapping()
    {
        if (is_string($this->mapping)) {
            $this->mapping = json_decode($this->mapping,true);
        }

        return $this->mapping;
    }

}