<?php

declare(strict_types=1);

namespace Muon\TeamsNotifierCore\Model;

/**
 * Resolves ${placeholder} expressions in an Adaptive Card template array.
 *
 * Recursively walks the decoded template array and replaces every occurrence of
 * "${key}" within string leaf values with the corresponding entry from $data.
 * Unknown placeholders (keys not present in $data) are left unchanged.
 * Non-string leaf values (integers, booleans, null) are never modified.
 */
class TemplateVariableSubstitutor
{
    /**
     * Substitute ${placeholder} expressions in the template array using the provided data.
     *
     * @param array $template Decoded Adaptive Card array (from json_decode).
     * @param array $data Flat key→value substitution map.
     * @return array Resolved card array with all known placeholders replaced.
     */
    public function substitute(array $template, array $data): array
    {
        if (empty($data)) {
            return $template;
        }

        return $this->walk($template, $data);
    }

    /**
     * Recursively process an array node, substituting placeholders in string leaves.
     *
     * @param array $node
     * @param array $data
     * @return array
     */
    private function walk(array $node, array $data): array
    {
        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->walk($value, $data);
            } elseif (is_string($value)) {
                $node[$key] = $this->replacePlaceholders($value, $data);
            }
        }

        return $node;
    }

    /**
     * Replace all ${key} occurrences in a string using the data map.
     *
     * @param string $value
     * @param array $data
     * @return string
     */
    private function replacePlaceholders(string $value, array $data): string
    {
        foreach ($data as $placeholder => $replacement) {
            $value = str_replace('${' . $placeholder . '}', (string) $replacement, $value);
        }

        return $value;
    }
}
