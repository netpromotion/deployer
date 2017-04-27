<?php

namespace Netpromotion\Deployer;

class Deployer
{
    const PLACEHOLDER = true;

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
        $logFile = $this->workingDir->getRealPath() . "/deploy.log";
        $this->config = array_merge_recursive(
            [
                "local" => $this->workingDir->getRealPath(),
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
            foreach ((array)@$this->config["ignore"] as $ignore) {
                $ignore = $this->convertLineToPath($directory->getRealPath(), $ignore, $recursiveIgnores);
                $ignores[$ignore] = self::PLACEHOLDER;
            }
        }

        if (file_exists($directory->getRealPath() . "/.gitignore")) {
            $file = file_get_contents($directory->getRealPath() . "/.gitignore");
            foreach (explode("\n", $file) as $line) {
                if (!empty($line)) {
                    $line = $this->convertLineToPath($directory->getRealPath(), $line, $recursiveIgnores);
                    $ignores[$line] = self::PLACEHOLDER;
                }
            }
        }

        foreach ($recursiveIgnores as $recursiveIgnore) {
            $recursiveIgnore = $this->convertLineToPath(
                $directory->getRealPath(),
                $recursiveIgnore
            );
            $ignores[$recursiveIgnore] = self::PLACEHOLDER;
        }

        foreach (new \DirectoryIterator($directory->getRealPath()) as $subDirectory) {
            if ($subDirectory->isDir() && !$subDirectory->isDot()) {
                if (isset($ignores[$subDirectory->getRealPath()]) && !isset($ignores["!" . $subDirectory->getRealPath()])) {
                    continue; // Skip ignored folders
                }
                $ignores = $this->gatherIgnores($subDirectory, $ignores, $recursiveIgnores);
            }
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

        if (DIRECTORY_SEPARATOR !== $item[0]) {
            $item = DIRECTORY_SEPARATOR . $item;
            $recursiveIgnores[$item] = $item;
        }

        $realPath = (new \SplFileInfo($basePath . $item))->getRealPath();
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
            if (!empty($ignore) && !isset($ignores["!{$ignore}"])) {
                $compactedIgnores[] = $this->shortenIgnore($ignore);
            }
        }

        rsort($compactedIgnores);

        return $compactedIgnores;
    }

    /**
     * @param $ignore
     * @return string
     */
    private function shortenIgnore($ignore)
    {
        return preg_replace(
            '/^(!)?'.preg_quote($this->workingDir->getRealPath(), '/').'/',
            "\$1",
            $ignore
        );
    }
}
