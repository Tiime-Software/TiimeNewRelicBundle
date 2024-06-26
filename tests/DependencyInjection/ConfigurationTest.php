<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\PrototypedArrayNode;
use Tiime\NewRelicBundle\DependencyInjection\Configuration;

class ConfigurationTest extends TestCase
{
    public function testIgnoredRoutes(): void
    {
        $configuration = new Configuration();
        $rootNode = $configuration->getConfigTreeBuilder()
            ->buildTree();
        $children = $rootNode->getChildren();

        /** @var PrototypedArrayNode $ignoredRoutesNode */
        $ignoredRoutesNode = $children['http']->getChildren()['ignored_routes'];

        $this->assertInstanceOf('\Symfony\Component\Config\Definition\PrototypedArrayNode', $ignoredRoutesNode);
        $this->assertFalse($ignoredRoutesNode->isRequired());
        $this->assertEmpty($ignoredRoutesNode->getDefaultValue());

        $this->assertSame(['ignored_route1', 'ignored_route2'], $ignoredRoutesNode->normalize(['ignored_route1', 'ignored_route2']));
        $this->assertSame(['ignored_route'], $ignoredRoutesNode->normalize('ignored_route'));
        $this->assertSame(['ignored_route1', 'ignored_route2'], $ignoredRoutesNode->merge(['ignored_route1'], ['ignored_route2']));
    }

    public function testIgnoredPaths(): void
    {
        $configuration = new Configuration();
        $rootNode = $configuration->getConfigTreeBuilder()
            ->buildTree();
        $children = $rootNode->getChildren();

        /** @var PrototypedArrayNode $ignoredPathsNode */
        $ignoredPathsNode = $children['http']->getChildren()['ignored_paths'];

        $this->assertInstanceOf('\Symfony\Component\Config\Definition\PrototypedArrayNode', $ignoredPathsNode);
        $this->assertFalse($ignoredPathsNode->isRequired());
        $this->assertEmpty($ignoredPathsNode->getDefaultValue());

        $this->assertSame(['/ignored/path1', '/ignored/path2'], $ignoredPathsNode->normalize(['/ignored/path1', '/ignored/path2']));
        $this->assertSame(['/ignored/path'], $ignoredPathsNode->normalize('/ignored/path'));
        $this->assertSame(['/ignored/path1', '/ignored/path2'], $ignoredPathsNode->merge(['/ignored/path1'], ['/ignored/path2']));
    }

    public function testIgnoredCommands(): void
    {
        $configuration = new Configuration();
        $rootNode = $configuration->getConfigTreeBuilder()
            ->buildTree();
        $children = $rootNode->getChildren();

        /** @var PrototypedArrayNode $ignoredCommandsNode */
        $ignoredCommandsNode = $children['commands']->getChildren()['ignored_commands'];

        $this->assertInstanceOf('\Symfony\Component\Config\Definition\PrototypedArrayNode', $ignoredCommandsNode);
        $this->assertFalse($ignoredCommandsNode->isRequired());
        $this->assertEmpty($ignoredCommandsNode->getDefaultValue());

        $this->assertSame(['test:ignored-command1', 'test:ignored-command2'], $ignoredCommandsNode->normalize(['test:ignored-command1', 'test:ignored-command2']));
        $this->assertSame(['test:ignored-command'], $ignoredCommandsNode->normalize('test:ignored-command'));
        $this->assertSame(['test:ignored-command1', 'test:ignored-command2'], $ignoredCommandsNode->merge(['test:ignored-command1'], ['test:ignored-command2']));
    }

    public function testDefaults(): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), []);

        $this->assertEmpty($config['http']['ignored_routes']);
        $this->assertIsArray($config['http']['ignored_routes']);
        $this->assertEmpty($config['http']['ignored_paths']);
        $this->assertIsArray($config['http']['ignored_paths']);
        $this->assertEmpty($config['commands']['ignored_commands']);
        $this->assertIsArray($config['commands']['ignored_commands']);
        $this->assertEmpty($config['deployment_names']);
        $this->assertIsArray($config['deployment_names']);
    }

    /**
     * @return array<array{string|string[], string[]}>
     */
    public static function ignoredRoutesProvider(): array
    {
        return [
            ['single_ignored_route', ['single_ignored_route']],
            [['single_ignored_route'], ['single_ignored_route']],
            [['ignored_route1', 'ignored_route2'], ['ignored_route1', 'ignored_route2']],
        ];
    }

    /**
     * @return array<array{string|string[], string[]}>
     */
    public static function ignoredPathsProvider(): array
    {
        return [
            ['/single/ignored/path', ['/single/ignored/path']],
            [['/single/ignored/path'], ['/single/ignored/path']],
            [['/ignored/path1', '/ignored/path2'], ['/ignored/path1', '/ignored/path2']],
        ];
    }

    /**
     * @return array<array{string|string[], string[]}>
     */
    public static function ignoredCommandsProvider(): array
    {
        return [
            ['single:ignored:command', ['single:ignored:command']],
            [['single:ignored:command'], ['single:ignored:command']],
            [['ignored:command1', 'ignored:command2'], ['ignored:command1', 'ignored:command2']],
        ];
    }

    /**
     * @return array<array{string|string[], string[]}>
     */
    public static function deploymentNamesProvider(): array
    {
        return [
            ['App1', ['App1']],
            [['App1'], ['App1']],
            [['App1', 'App2'], ['App1', 'App2']],
        ];
    }

    /**
     * @param string|string[] $deploymentNameConfig
     * @param string[]        $expected
     *
     * @dataProvider deploymentNamesProvider
     */
    public function testDeploymentNames(string|array $deploymentNameConfig, array $expected): void
    {
        $processor = new Processor();

        $config1 = $processor->processConfiguration(new Configuration(), ['tiime_new_relic' => ['deployment_name' => $deploymentNameConfig]]);
        $config2 = $processor->processConfiguration(new Configuration(), ['tiime_new_relic' => ['deployment_names' => $deploymentNameConfig]]);

        $this->assertSame($expected, $config1['deployment_names']);
        $this->assertSame($expected, $config2['deployment_names']);
    }

    /**
     * @param string|string[] $ignoredRoutesConfig
     * @param string[]        $expected
     *
     * @dataProvider ignoredRoutesProvider
     */
    public function testIgnoreRoutes(string|array $ignoredRoutesConfig, array $expected): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), ['tiime_new_relic' => ['http' => ['ignored_routes' => $ignoredRoutesConfig]]]);

        $this->assertSame($expected, $config['http']['ignored_routes']);
    }

    /**
     * @param string|string[] $ignoredPathsConfig
     * @param string[]        $expected
     *
     * @dataProvider ignoredPathsProvider
     */
    public function testIgnorePaths(string|array $ignoredPathsConfig, array $expected): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), ['tiime_new_relic' => ['http' => ['ignored_paths' => $ignoredPathsConfig]]]);

        $this->assertSame($expected, $config['http']['ignored_paths']);
    }

    /**
     * @param string|string[] $ignoredCommandsConfig
     * @param string[]        $expected
     *
     * @dataProvider ignoredCommandsProvider
     */
    public function testIgnoreCommands(string|array $ignoredCommandsConfig, array $expected): void
    {
        $processor = new Processor();

        $config = $processor->processConfiguration(new Configuration(), ['tiime_new_relic' => ['commands' => ['ignored_commands' => $ignoredCommandsConfig]]]);

        $this->assertSame($expected, $config['commands']['ignored_commands']);
    }
}
