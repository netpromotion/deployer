<?php

namespace Netpromotion\Deployer;

class Deployer
{
    const PLACEHOLDER = true;
    const ABSOLUTE_IGNORE = '/^[^\*^\?^\[]*$/';

    /**
     * @var \SplFileInfo
     */
    private $workingDir;

    /**
     * @var array
     */
    private $config;

    /**
     * @param string $workingDir
     */
    public function __construct($workingDir)
    {
        $this->workingDir = new \SplFileInfo($workingDir);

        $this->config = array_replace_recursive(
            (array)@json_decode(@file_get_contents($workingDir . "/deploy.json"), true),
            (array)@json_decode(@file_get_contents($workingDir . "/deploy.local.json"), true)
        );
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        $logFile = $this->workingDir->getPathname() . "/deploy.log";
        $this->config = array_merge_recursive(
            [
                "local" => $this->workingDir->getPathname(),
                "log" => $logFile,
                "ignore" => [
                    ".git",
                    "/deploy.*"
                ],
                "preprocess" => false,
            ],
            $this->config
        );

        $this->config["ignore"] = $this->compactIgnores(
            $this->gatherIgnores($this->workingDir)
        );

        return $this->config;
    }

    /**
     * @param \SplFileInfo $directory
     * @param array $ignores
     * @param array $recursiveIgnores
     * @return array
     */
    private function gatherIgnores(\SplFileInfo $directory = null, array $ignores = null, array $recursiveIgnores = [])
    {
        if (null === $directory) {
            $directory = $this->workingDir;
        }

        if (null === $ignores) {
            $ignores = [];
            $masterIgnores = [];
            foreach ((array)@$this->config["ignore"] as $ignore) {
                $ignore = $this->convertLineToPath($directory->getPathname(), $ignore, $recursiveIgnores);
                if ("!" === $ignore[0]) {
                    $ignores[$ignore] = self::PLACEHOLDER;
                } else {
                    $masterIgnores[$ignore] = self::PLACEHOLDER;
                }
            }
        }

        foreach ($recursiveIgnores as $recursiveIgnore) {
            $recursiveIgnore = $this->convertLineToPath(
                $directory->getPathname(),
                $recursiveIgnore
            );
            if (preg_match(self::ABSOLUTE_IGNORE, $recursiveIgnore) && !file_exists($recursiveIgnore)) {
                continue;
            }
            $ignores[$recursiveIgnore] = self::PLACEHOLDER;
        }

        if (file_exists($directory->getPathname() . "/.gitignore")) {
            $file = file_get_contents($directory->getPathname() . "/.gitignore");
            foreach (explode("\n", $file) as $line) {
                if (!empty($line)) {
                    $line = $this->convertLineToPath($directory->getPathname(), $line, $recursiveIgnores);
                    $ignores[$line] = self::PLACEHOLDER;
                }
            }
        }

        foreach (new \DirectoryIterator($directory->getPathname()) as $subDirectory) {
            if ($subDirectory->isDir() && !$subDirectory->isDot()) {
                if (isset($ignores[$subDirectory->getPathname()]) && !isset($ignores["!" . $subDirectory->getPathname()])) {
                    continue; // Skip ignored folders
                }
                $ignores = array_merge($ignores, $this->gatherIgnores($subDirectory, [], $recursiveIgnores));
            }
        }

        if (isset($masterIgnores)) {
            krsort($masterIgnores);
            $ignores = array_merge($ignores, $masterIgnores);
        }

        return $ignores;
    }

    /**
     * @param string $basePath
     * @param string $item
     * @param array $recursiveIgnores
     * @return null|string
     */
    private function convertLineToPath($basePath, $item, array &$recursiveIgnores = [])
    {
        $item = trim($item);
        if (empty($item)) {
            return null; // Skip empty lines
        }

        if ("#" === $item[0]) {
            return null; // Skip comments
        }

        if ("!" === $item[0]) {
            $negative = true;
            $item = substr($item, 1); // Remove negative mark
        } else {
            $negative = false;
        }

        if ("*" === $item) {
            $item = "/*"; // Simplify recursive star wildcard by non-recursive rule
        }

        if (DIRECTORY_SEPARATOR !== $item[0]) {
            $item = DIRECTORY_SEPARATOR . $item;
            $recursiveIgnores[$item] = $item;
        }

        $realPath = (new \SplFileInfo($basePath . $item))->getPathname();
        if (empty($realPath)) {
            $realPath = $basePath . $item; // Wildcard
        }

        return ($negative ? "!" : "") . $realPath;
    }

    /**
     * @param array $ignores
     * @return array
     */
    private function compactIgnores(array $ignores)
    {
        $compactedIgnores = [];
        foreach ($ignores as $ignore => $placeholder) {
            $compactedIgnore = null;
            if (preg_match(self::ABSOLUTE_IGNORE, $ignore)) {
                if ("!" === $ignore[0]) {
                    $path = substr($ignore, 1);
                } else {
                    $path = $ignore;
                }

                if (file_exists($path)) {
                    if (is_dir($path)) {
                        $ignore .= DIRECTORY_SEPARATOR;
                    }
                    $compactedIgnore = $this->shortenIgnore($ignore);
                }
            } else {
                $compactedIgnore = $this->shortenIgnore($ignore);
            }

            if (null !== $compactedIgnore && !isset($compactedIgnores["!{$compactedIgnore}"])) {
                $compactedIgnores[$compactedIgnore] = self::PLACEHOLDER;
            }
        }

        return array_keys($compactedIgnores);
    }

    /**
     * @param $ignore
     * @return string
     */
    private function shortenIgnore($ignore)
    {
        $ignore = preg_replace(
            '/^(!)?'.preg_quote($this->workingDir->getPathname(), '/').'/',
            "\$1",
            $ignore
        );

        return $ignore;
    }
}
