<?php

namespace Shakewellagency\ContentPortalPdfParser\Features\Packages\Helpers;


class ParserVersionHelper
{
    /**
     * Get the installed version of a given package.
     *
     * @param string $packageName
     * @return string|null
     */
    public static function getPackageVersion(string $packageName): ?string
    {
        $composerLockPath = base_path('composer.lock');

        if (file_exists($composerLockPath)) {
            $lockData = json_decode(file_get_contents($composerLockPath), true);

            // Search for the package in the "packages" and "packages-dev" sections
            foreach (['packages', 'packages-dev'] as $section) {
                if (isset($lockData[$section])) {
                    foreach ($lockData[$section] as $package) {
                        if ($package['name'] === $packageName) {
                            return $package['version']; // Return exact installed version
                        }
                    }
                }
            }
        }

        return null; 
    }
}