<?php

namespace Netpromotion\Deployer\Test;

use Netpromotion\Deployer\Deployer;

class DeployerTest extends \PHPUnit_Framework_TestCase
{
    private function getDeployer()
    {
        return new Deployer(__DIR__ . "/DeployerTest");
    }

    private function invoke($object, $method, array $args = [])
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    public function testGetConfigWorks()
    {
        $this->assertEquals([
            "local" => __DIR__ . "/DeployerTest",
            "log" => [
                "output" => __DIR__ . "/DeployerTest/deploy.log",
                "config" => __DIR__ . "/DeployerTest/deploy.config.log",
            ],
            "ignore" => [
                "!/vendor/",
                "/*.log",
                "/vendor/*.log",
                "/vendor/netpromotion/*.log",
                "/vendor/netpromotion/deployer/*.log",
                "/vendor/netpromotion/deployer/vendor/",
                "/vendor/netpromotion/deployer/src/*.log",
                "/tests/*.log",
                "/temp/*.log",
                "/temp/*",
                '!/temp/.gitignore',
                "/src/*.log",
                "/src/Web/*.log",
                "/src/Web/Resources/docs/",
                "/src/Web/Resources/*.log",
                "!/src/Web/Resources/docs/",
                "/src/Web/Resources/public/*.log",
                "/src/Web/Resources/docs/*.log",
                "/www/*.log",
                "/www/assets/*.log",
                "/deploy.*",
                "/tests/",
            ],
            "remote" => "ftp://anonymous@production/DeployerTest",
            "preprocess" => false,
        ], $this->getDeployer()->getConfig());
    }

    public function testGatherIgnoresWorks()
    {
        $expected = [
            __DIR__ . "/DeployerTest/www/assets/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/www/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/vendor" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/src/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/vendor/netpromotion/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/vendor/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/tests" => Deployer::USER_IGNORE,
            __DIR__ . "/DeployerTest/src/Web/Resources/docs" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/src/Web/Resources/public/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/src/Web/Resources/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/src/Web/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/src/*.log" => Deployer::DYNAMIC_IGNORE,
            "!" . __DIR__ . "/DeployerTest/vendor" => Deployer::USER_IGNORE,
            __DIR__ . "/DeployerTest/vendor" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/temp/*" => Deployer::DYNAMIC_IGNORE,
            "!" . __DIR__ . "/DeployerTest/temp/.gitignore" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/docs" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/tests/*.log" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/temp/*.log" => Deployer::DYNAMIC_IGNORE,
            "!" . __DIR__ . "/DeployerTest/src/Web/Resources/docs" => Deployer::DYNAMIC_IGNORE,
            __DIR__ . "/DeployerTest/src/Web/Resources/docs/*.log" => Deployer::DYNAMIC_IGNORE,
        ];

        $this->assertEquals($expected, $this->invoke($this->getDeployer(), "gatherIgnores"));
    }

    public function testCompactIgnoresWorks()
    {
        $this->assertEquals(
            [
                "/vendor/",
                "/src/Web/Resources/docs/",
                "!/vendor/",
            ],
            $this->invoke(
                $this->getDeployer(),
                "compactIgnores",
                [
                    [
                        __DIR__ . "/DeployerTest/vendor" => Deployer::DYNAMIC_IGNORE,
                        __DIR__ . "/DeployerTest/src/Web/Resources/docs" => Deployer::DYNAMIC_IGNORE,
                        "!" . __DIR__ . "/DeployerTest/vendor" => Deployer::DYNAMIC_IGNORE,
                        "!" . __DIR__ . "/DeployerTest/docs" => Deployer::DYNAMIC_IGNORE,
                    ]
                ]
            )
        );
    }

    /**
     * @dataProvider dataShortenIgnoreWorks
     * @param string $input
     * @param string $output
     */
    public function testShortenIgnoreWorks($input, $output)
    {
        $this->assertEquals($output, $this->invoke($this->getDeployer(), "shortenIgnore", [$input]));
    }

    public function dataShortenIgnoreWorks()
    {
        return [
            [__DIR__ . "/DeployerTest/docs", "/docs"],
            ["!" . __DIR__ . "/DeployerTest/docs", "!/docs"],
            [__DIR__ . "/DeployerTest/src/Web/Resources/docs", "/src/Web/Resources/docs"],
            ["!" . __DIR__ . "/DeployerTest/src/Web/Resources/docs", "!/src/Web/Resources/docs"],
        ];
    }
}
