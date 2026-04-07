<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\ResourceModel;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Teams notification channel resource model.
 *
 * Transparently encrypts webhook_url and trigger_secret on save,
 * and decrypts them on load, so callers always work with plaintext values.
 *
 * @SuppressWarnings(PHPMD.MissingImport)
 * The PHP 8.3 #[\Override] attribute is a built-in and does not require a use statement.
 */
class Channel extends AbstractDb
{
    public const TABLE_NAME = 'muon_teamsnotifiercore_channel';
    public const ID_FIELD   = 'channel_id';

    /** @var array<string> Sensitive fields that are stored encrypted. */
    private const ENCRYPTED_FIELDS = ['webhook_url', 'trigger_secret'];

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        private readonly EncryptorInterface $encryptor,
        ?string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialise the resource model table and primary key.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD);
    }

    /**
     * Encrypt sensitive fields before persisting.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(AbstractModel $object): static
    {
        foreach (self::ENCRYPTED_FIELDS as $field) {
            $value = (string) $object->getData($field);
            if ($value !== '') {
                $object->setData($field, $this->encryptor->encrypt($value));
            }
        }

        return parent::_beforeSave($object);
    }

    /**
     * Decrypt sensitive fields after loading.
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(AbstractModel $object): static
    {
        parent::_afterLoad($object);

        foreach (self::ENCRYPTED_FIELDS as $field) {
            $value = (string) $object->getData($field);
            if ($value !== '') {
                $object->setData($field, $this->encryptor->decrypt($value));
            }
        }

        return $this;
    }
}
