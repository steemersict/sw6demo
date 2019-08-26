<?php declare(strict_types=1);

namespace Shopware\Storefront\Theme;

use Shopware\Storefront\Theme\Exception\InvalidThemeException;
use Shopware\Storefront\Theme\Exception\ThemeCompileException;
use Shopware\Storefront\Theme\StorefrontPluginConfiguration\File;
use Shopware\Storefront\Theme\StorefrontPluginConfiguration\FileCollection;
use Shopware\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfiguration;
use Shopware\Storefront\Theme\StorefrontPluginConfiguration\StorefrontPluginConfigurationCollection;

class ThemeFileResolver
{
    public const SCRIPT_FILES = 'script';
    public const STYLE_FILES = 'style';

    public function resolveFiles(
        StorefrontPluginConfiguration $themeConfig,
        StorefrontPluginConfigurationCollection $configurationCollection,
        bool $onlySourceFiles
    ): array {
        return [
            self::SCRIPT_FILES => $this->resolve(
                $themeConfig,
                $configurationCollection,
                $onlySourceFiles,
                function (StorefrontPluginConfiguration $configuration, bool $onlySourceFiles) {
                    $fileCollection = new FileCollection();

                    if ($configuration->getStorefrontEntryFilepath() && $onlySourceFiles) {
                        $fileCollection->add(new File($configuration->getStorefrontEntryFilepath()));
                    }

                    foreach ($configuration->getScriptFiles() as $scriptFile) {
                        if (strpos($scriptFile->getFilepath(), '@') !== 0 && $onlySourceFiles) {
                            continue;
                        }
                        $fileCollection->add($scriptFile);
                    }

                    return $fileCollection;
                }),
            self::STYLE_FILES => $this->resolve(
                $themeConfig,
                $configurationCollection,
                $onlySourceFiles,
                function (StorefrontPluginConfiguration $configuration, bool $onlySourceFiles) {
                    return $configuration->getStyleFiles();
                }),
        ];
    }

    private function resolve(
        StorefrontPluginConfiguration $themeConfig,
        StorefrontPluginConfigurationCollection $configurationCollection,
        bool $onlySourceFiles,
        callable $configFileResolver
    ): FileCollection {
        /** @var FileCollection $files */
        $files = $configFileResolver($themeConfig, $onlySourceFiles);

        if ($files->count() === 0) {
            return $files;
        }

        $resolvedFiles = new FileCollection();

        /** @var File $file */
        foreach ($files as $file) {
            $filepath = $file->getFilepath();
            if (strpos($filepath, '@') !== 0) {
                if (file_exists($filepath)) {
                    $resolvedFiles->add($file);
                    continue;
                }
                throw new ThemeCompileException(
                    $themeConfig->getTechnicalName(),
                    sprintf('Unable to load file "%s". Did you forget to build the theme? Try running ./psh.phar storefront:build ', $filepath)
                );
            }

            if ($filepath === '@Plugins') {
                foreach ($configurationCollection->getNoneThemes() as $plugin) {
                    foreach ($this->resolve($plugin, $configurationCollection, $onlySourceFiles, $configFileResolver) as $item) {
                        $resolvedFiles->add($item);
                    }
                }
                continue;
            }

            // Resolve @ dependencies
            $name = substr($filepath, 1);
            $configuration = $configurationCollection->getByTechnicalName($name);

            if (!$configuration) {
                throw new InvalidThemeException($name);
            }

            foreach ($this->resolve($configuration, $configurationCollection, $onlySourceFiles, $configFileResolver) as $item) {
                $resolvedFiles->add($item);
            }
        }

        return $resolvedFiles;
    }
}
