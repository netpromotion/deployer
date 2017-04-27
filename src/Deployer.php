<?php

namespace Netpromotion\Deployer;

class Deployer
{
    private $workingDir;

    private $config;

    public function __construct($workingDir)
    {
        $this->workingDir = new \SplFileInfo($workingDir);

        $this->config = array_replace_recursive(
            (array)@json_decode(@file_get_contents($workingDir . "/deploy.json"), true),
            (array)@json_decode(@file_get_contents($workingDir . "/deploy.local.json"), true)
        );
    }

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

        $this->config["ignore"] = $this->shortenIgnores(
            $this->gatherIgnores($this->workingDir)
        );

        return $this->config;
    }

    private function gatherIgnores(\SplFileInfo $directory = null, array $ignores = null, array $recursiveIgnores = [])
    {
        if (null === $directory) {
            $directory = $this->workingDir;
        }

        if (null === $ignores) {
            $ignores = [];
            foreach ((array)@$this->config["ignore"] as $ignore) {
                $ignores[] = $this->convertLineToPath($directory->getRealPath(), $ignore, $recursiveIgnores);
            }
        }

        if (file_exists($directory->getRealPath() . "/.gitignore")) {
            $file = file_get_contents($directory->getRealPath() . "/.gitignore");
            foreach (explode("\n", $file) as $line) {
                $ignores[] = $this->convertLineToPath($directory->getRealPath(), $line, $recursiveIgnores);
            }
        }

        foreach ($recursiveIgnores as $recursiveIgnore) {
            $ignores[] = ($recursiveIgnore[1] ? "!" : "") . $directory->getRealPath() . $recursiveIgnore[0];
        }

        $ignores = $this->compactIgnores($ignores);

        foreach (new \DirectoryIterator($directory->getRealPath()) as $subDirectory) {
            if ($subDirectory->isDir() && !$subDirectory->isDot()) {
                if (in_array($subDirectory->getRealPath(), $ignores)) {
                    continue; // Skip ignored folders
                }
                $ignores = $this->gatherIgnores($subDirectory, $ignores, $recursiveIgnores);
            }
        }

        return $ignores;
    }

    private function convertLineToPath($basePath, $line, &$recursiveIgnores)
    {
        $line = trim($line);
        if (empty($line)) {
            return null; // Skip empty lines
        }

        if ("#" === $line[0]) {
            return null; // Skip comments
        }

        if ("!" === $line[0]) {
            $negative = true;
            $line = substr($line, 1); // Remove negative mark
        } else {
            $negative = false;
        }

        if (DIRECTORY_SEPARATOR !== $line[0]) {
            $line = DIRECTORY_SEPARATOR . $line;
            $recursiveIgnores[] = [$line, $negative];
        }

        $realPath = (new \SplFileInfo($basePath . $line))->getRealPath();
        if (empty($realPath)) {
            $realPath = $basePath . $line; // Wildcard
        }

        return ($negative ? "!" : "") . $realPath;
    }

    private function compactIgnores($ignores)
    {
        $compactedIgnores = [];
        foreach ($ignores as $ignore) {
            if (!empty($ignore) && !in_array("!{$ignore}", $ignores)) {
                $compactedIgnores[] = $ignore;
            }
        }

        $compactedIgnores = array_unique($compactedIgnores);
        rsort($compactedIgnores);

        return $compactedIgnores;
    }

    private function shortenIgnores($ignores)
    {
        $shortenedIgnores = [];
        foreach ($ignores as $ignore) {
            $shortenedIgnores[] = preg_replace(
                '/^(!)?'.preg_quote($this->workingDir->getRealPath(), '/').'/',
                "\$1",
                $ignore
            );
        }

        return $shortenedIgnores;
    }
}
