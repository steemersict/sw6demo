<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Snippet\Filter;

abstract class AbstractFilter
{
    abstract public function getName(): string;

    public function supports(string $name): bool
    {
        return $this->getName() === $name;
    }

    public function readjust(array $result, array $snippetSets): array
    {
        foreach ($snippetSets as $setId => $snippets) {
            foreach ($result as $currentSetId => $currentSnippets) {
                foreach ($currentSnippets['snippets'] as $translationKey => $snippet) {
                    if (isset($result[$setId]['snippets'][$translationKey])) {
                        continue;
                    }

                    if (!isset($snippetSets[$setId]['snippets'][$translationKey])) {
                        $result[$setId]['snippets'][$translationKey] = [
                            'value' => '',
                            'origin' => '',
                            'translationKey' => $translationKey,
                            'author' => '',
                            'id' => null,
                            'setId' => $setId,
                        ];
                        continue;
                    }

                    $result[$setId]['snippets'][$translationKey] = $snippetSets[$setId]['snippets'][$translationKey];
                }
            }
        }

        return $result;
    }
}