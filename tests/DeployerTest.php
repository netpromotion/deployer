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
            "log" => __DIR__ . "/DeployerTest/deploy.log",
            "ignore" => [
                "/www/cache",
                "/www/*.log",
                "/vendor/netpromotion/deployer/vendor",
                "/vendor/netpromotion/deployer/src/cache",
                "/vendor/netpromotion/deployer/src/*.log",
                "/vendor/netpromotion/deployer/cache",
                "/vendor/netpromotion/deployer/*.log",
                "/vendor/netpromotion/cache",
                "/vendor/netpromotion/*.log",
                "/vendor/cache",
                "/vendor/*.log",
                "/tests",
                "/src/cache",
                "/src/Web/cache",
                "/src/Web/Resources/public/cache",
                "/src/Web/Resources/public/*.log",
                "/src/Web/Resources/cache",
                "/src/Web/Resources/*.log",
                "/src/Web/*.log",
                "/src/*.log",
                "/deploy.json",
                "!/vendor",
            ],
            "remote" => "ftp://anonymous@production/DeployerTest",
            "preprocess" => false,
        ], $this->getDeployer()->getConfig());
    }

    public function testGatherIgnoresWorks()
    {
        $this->assertEquals(
            [
                __DIR__ . "/DeployerTest/www/cache",
                __DIR__ . "/DeployerTest/www/*.log",
                __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/vendor",
                __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/src/cache",
                __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/src/*.log",
                __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/cache",
                __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/*.log",
                __DIR__ . "/DeployerTest/vendor/netpromotion/cache",
                __DIR__ . "/DeployerTest/vendor/netpromotion/*.log",
                __DIR__ . "/DeployerTest/vendor/cache",
                __DIR__ . "/DeployerTest/vendor/*.log",
                __DIR__ . "/DeployerTest/tests",
                __DIR__ . "/DeployerTest/src/cache",
                __DIR__ . "/DeployerTest/src/Web/cache",
                __DIR__ . "/DeployerTest/src/Web/Resources/public/cache",
                __DIR__ . "/DeployerTest/src/Web/Resources/public/*.log",
                __DIR__ . "/DeployerTest/src/Web/Resources/cache",
                __DIR__ . "/DeployerTest/src/Web/Resources/*.log",
                __DIR__ . "/DeployerTest/src/Web/*.log",
                __DIR__ . "/DeployerTest/src/*.log",
                "!" . __DIR__ . "/DeployerTest/vendor",

            ],
            $this->invoke($this->getDeployer(), "gatherIgnores")
        );
    }

    public function testCompactIgnoresWorks()
    {
        $this->assertEquals(
            [
                "a",
                "!b",
            ],
            $this->invoke(
                $this->getDeployer(),
                "compactIgnores",
                [
                    [
                        "a",
                        "b",
                        "!b",
                        "",
                    ]
                ]
            )
        );
    }
}
