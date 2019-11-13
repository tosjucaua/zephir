<?php

/*
 * This file is part of the Zephir.
 *
 * (c) Zephir Team <team@zephir-lang.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zephir\Test;

use PHPUnit\Framework\TestCase;
use Zephir\Config;

class ConfigTest extends TestCase
{
    /**
     * Common directory.
     *
     * @var string
     */
    private $pwd;

    /** @var Config $config */
    private $config;

    public function setUp()
    {
        /* Store the current directory before to be change */
        $this->pwd = getcwd();
        $this->config = new Config();
    }

    /**
     * Restore current directory, and clean config.json.
     */
    public function tearDown()
    {
        if (getcwd() != $this->pwd) {
            chdir($this->pwd);
        }

        $this->cleanTmpConfigFile();
    }

    /**
     * Clean config.json file into tmp dir.
     */
    private function cleanTmpConfigFile()
    {
        /* clean config.json into tmp dir */
        $tmpConfigFile = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'config.json';

        if (file_exists($tmpConfigFile)) {
            unlink($tmpConfigFile);
        }
    }

    /**
     * Test when we have a bad config.json file.
     *
     * @test
     * @expectedException \Zephir\Exception
     * @expectedExceptionMessage The config.json file is not valid or there is
     * no Zephir extension initialized in this directory.
     */
    public function constructWithBadConfigFile()
    {
        chdir(\constant('ZEPHIRPATH').'/unit-tests/fixtures/badconfig');
        new Config();
    }

    /**
     * Test data provider.
     *
     * [
     *     'test name' => [
     *          [$key, $value, $namespace], $expected,
     *      ],
     *      ...
     * ]
     *
     * @return array
     */
    public function setConfigDataProvider(): array
    {
        return [
            'set with namespace' => [
                ['unused-variable', false, 'warnings'], false,
            ],
            'set without namespace' => [
                ['config', true, null], true,
            ],
            'get with namespace' => [
                ['unused-variable', true, 'warnings'], true,
            ],
            'get without namespace' => [
                ['verbose', false, null], false,
            ],
            'directive don`t have duplicates with namespace' => [
                ['ext.some_key', 'some_value', 'test'], 'some_value',
            ],
            'directive don`t have duplicates without namespace' => [
                ['test.my_setting_1', 'test', null], 'test',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider setConfigDataProvider
     */
    public function shouldSetConfigParams(array $test, $expected)
    {
        list($key, $value, $namespace) = $test;
        $this->config->set($key, $value, $namespace);

        $actual = $this->config->get($key, $namespace);

        $this->assertSame($expected, $actual);
    }

    /** @test */
    public function shouldGetDefaultConfigParams()
    {
        $this->assertSame($this->config->get('test.my_setting_1', 'test'), null);
    }

    /** @test */
    public function shouldSaveConfigOnExit()
    {
        chdir(sys_get_temp_dir());
        $config = new Config();
        $config->set('name', 'foo');
        $config->dumpToFile();
        $configJson = json_decode(file_get_contents('config.json'), true);
        $this->assertInternalType('array', $configJson);
        $this->assertSame($configJson['name'], 'foo');
        $this->cleanTmpConfigFile();
    }
}
