<?php

declare(strict_types=1);

/*
 * This file is part of the "GuidelinesBundle" for Kimai.
 * Copyright (c) 2026 Mauricio Barrera (https://github.com/moui)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\GuidelinesBundle\DependencyInjection;

use App\Plugin\AbstractPluginExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class GuidelinesExtension extends AbstractPluginExtension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        // Defaults for the keys edited on System → Guidelines (guidelines.*).
        // Saved values live in kimai2_configuration and override these. The
        // starting guide shows until an admin saves the page. The description
        // rule starts switched off, so deploying the plugin alone does not
        // block anyone. The future-time rule starts on "kimai", which leaves
        // Kimai's own setting in charge.
        $defaultContent = __DIR__ . '/../Resources/defaults/guidelines.md';
        $container->addResource(new FileResource($defaultContent));

        $this->registerBundleConfiguration($container, [
            'content' => trim((string) file_get_contents($defaultContent)),
            'updated_at' => '',
            'updated_by' => '',
            'description_required' => false,
            'description_min_length' => 5,
            'description_required_since' => '',
            'future_mode' => 'kimai',
            'future_minutes' => 120,
        ]);
    }

    /**
     * Everyone sees the widget; only admins edit it. Other roles can be
     * granted `guidelines_edit` from Kimai's role permission screen.
     */
    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('kimai', [
            'permissions' => [
                'roles' => [
                    'ROLE_ADMIN' => ['guidelines_edit'],
                    'ROLE_SUPER_ADMIN' => ['guidelines_edit'],
                ],
            ],
        ]);
    }

    public function getAlias(): string
    {
        return 'guidelines';
    }
}
