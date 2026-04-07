<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model\Template;

use Magento\Framework\Exception\LocalizedException;

/**
 * Validates that a string is parseable JSON representing a valid Adaptive Card root object.
 *
 * Checks:
 *  - Valid JSON syntax
 *  - Root is an object (not array or scalar)
 *  - "type" field equals "AdaptiveCard"
 *  - "body" field is present and is an array
 *  - "version" field is present and is a non-empty string
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * Each assertion is a distinct structural check on the Adaptive Card JSON — the complexity
 * is inherent to thorough validation and cannot be reduced further without hiding logic.
 */
class JsonValidator
{
    /**
     * Validate the given JSON string as an Adaptive Card template.
     *
     * @param string $json
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate(string $json): void
    {
        if ($json === '') {
            throw new LocalizedException(__('Template JSON must not be empty.'));
        }

        $decoded = $this->decode($json);
        $this->assertType($decoded);
        $this->assertBody($decoded);
        $this->assertVersion($decoded);
    }

    /**
     * Parse the JSON string and return the decoded array.
     *
     * @param string $json
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function decode(string $json): array
    {
        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new LocalizedException(
                __('Template JSON is not valid JSON: %1', json_last_error_msg())
            );
        }

        if (!is_array($decoded) || array_is_list($decoded)) {
            throw new LocalizedException(
                __('Template JSON root must be an object, not an array or scalar.')
            );
        }

        return $decoded;
    }

    /**
     * Assert the root "type" field equals "AdaptiveCard".
     *
     * @param array $decoded
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function assertType(array $decoded): void
    {
        if (($decoded['type'] ?? '') !== 'AdaptiveCard') {
            throw new LocalizedException(
                __('Template JSON must have "type": "AdaptiveCard" at the root.')
            );
        }
    }

    /**
     * Assert the root "body" field is present and is an array.
     *
     * @param array $decoded
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function assertBody(array $decoded): void
    {
        if (!isset($decoded['body']) || !is_array($decoded['body'])) {
            throw new LocalizedException(
                __('Template JSON must have a "body" array at the root.')
            );
        }
    }

    /**
     * Assert the root "version" field is present and non-empty.
     *
     * @param array $decoded
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function assertVersion(array $decoded): void
    {
        if (!isset($decoded['version']) || !is_string($decoded['version'])
            || $decoded['version'] === '') {
            throw new LocalizedException(
                __('Template JSON must have a non-empty "version" string at the root.')
            );
        }
    }
}
