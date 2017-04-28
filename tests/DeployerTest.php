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
                "/*.log",
                "!/vendor/",
                "/vendor/*.log",
                "/vendor/netpromotion/*.log",
                "/vendor/netpromotion/deployer/vendor/",
                "/vendor/netpromotion/deployer/*.log",
                "/vendor/netpromotion/deployer/src/*.log",
                "/tests/*.log",
                "/temp/*",
                '!/temp/.gitignore',
                "/src/*.log",
                "/src/Web/*.log",
                "/src/Web/Resources/docs/",
                "/src/Web/Resources/*.log",
                "/src/Web/Resources/public/*.log",
                "/www/*.log",
                "/www/assets/*.log",
                "/tests/",
                "/deploy.*",
            ],
            "remote" => "ftp://anonymous@production/DeployerTest",
            "preprocess" => false,
        ], $this->getDeployer()->getConfig());
    }

    public function testGatherIgnoresWorks()
    {
        $expected = [
            __DIR__ . "/DeployerTest/www/assets/*.log",
            __DIR__ . "/DeployerTest/www/*.log",
            __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/vendor",
            __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/src/*.log",
            __DIR__ . "/DeployerTest/vendor/netpromotion/deployer/*.log",
            __DIR__ . "/DeployerTest/vendor/netpromotion/*.log",
            __DIR__ . "/DeployerTest/vendor/*.log",
            __DIR__ . "/DeployerTest/tests",
            __DIR__ . "/DeployerTest/src/Web/Resources/docs",
            __DIR__ . "/DeployerTest/src/Web/Resources/public/*.log",
            __DIR__ . "/DeployerTest/src/Web/Resources/*.log",
            __DIR__ . "/DeployerTest/src/Web/*.log",
            __DIR__ . "/DeployerTest/src/*.log",
            "!" . __DIR__ . "/DeployerTest/vendor",
            __DIR__ . "/DeployerTest/vendor",
            __DIR__ . "/DeployerTest/temp/*",
            "!" . __DIR__ . "/DeployerTest/temp/.gitignore",
            __DIR__ . "/DeployerTest/docs",
            __DIR__ . "/DeployerTest/*.log",
            __DIR__ . "/DeployerTest/tests/*.log",
        ];

        $expected = array_combine($expected, array_fill(0, count($expected), Deployer::PLACEHOLDER));

        $this->assertEquals($expected, $this->invoke($this->getDeployer(), "gatherIgnores"));
    }

    public function testCompactIgnoresWorks()
    {
        $this->assertEquals(
            [
                "/src/Web/Resources/docs/",
                "!/vendor/",
            ],
            $this->invoke(
                $this->getDeployer(),
                "compactIgnores",
                [
                    [
                        __DIR__ . "/DeployerTest/vendor" => Deployer::PLACEHOLDER,
                        __DIR__ . "/DeployerTest/src/Web/Resources/docs" => Deployer::PLACEHOLDER,
                        "!" . __DIR__ . "/DeployerTest/vendor" => Deployer::PLACEHOLDER,
                        "!" . __DIR__ . "/DeployerTest/docs" => Deployer::PLACEHOLDER,
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
