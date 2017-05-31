<?php

namespace Netpromotion\Deployer;

class Deployer
{
    const USER_IGNORE = 1;
    const DYNAMIC_IGNORE = 2;
    const ABSOLUTE_IGNORE_PATTERN = '/^[^\*^\?^\[]*$/';

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
        $log = (array)@$this->config["log"];
        unset($this->config["log"]);
        $this->config = array_merge_recursive(
            [
                "local" => $this->workingDir->getPathname(),
                "log" => [
                    "output" => "/deploy.log",
                    "config" => null,
                ],
                "ignore" => [],
                "preprocess" => false,
            ],
            $this->config
        );

        $knownFiles = [
            ".git/",
            "/deploy.json",
            "/deploy.local.json",
            $this->config["log"]["output"],
            $this->config["log"]["config"]
        ];

        foreach ($knownFiles as $ignore) {
            if (!in_array("!" . $ignore, $this->config["ignore"])) {
                $this->config["ignore"][] = $ignore;
            }
        }

        $this->config["log"] = array_merge($this->config["log"], $log);
        if (null !== $this->config["log"]["output"]) {
            $this->config["log"]["output"] = $this->convertIgnoreToPatterns($this->workingDir->getPathname(), $this->config["log"]["output"])[0];
        }
        if (null !== $this->config["log"]["config"]) {
            $this->config["log"]["config"] = $this->convertIgnoreToPatterns($this->workingDir->getPathname(), $this->config["log"]["config"])[0];
        }

        $this->config["ignore"] = $this->compactIgnoredPatterns(
            $this->gatherIgnoredPatterns($this->workingDir)
        );

        return $this->config;
    }

    /**
     * @param \SplFileInfo $directory
     * @param array $ignores
     * @return array
     */
    private function gatherIgnoredPatterns(\SplFileInfo $directory = null, array $ignores = null)
    {
        if (null === $directory) {
            $directory = $this->workingDir;
        }

        if (null === $ignores) {
            $negativeUserIgnores = [];
            $positiveUserIgnores = [];
            foreach ((array)@$this->config["ignore"] as $ignore) {
                foreach ($this->convertIgnoreToPatterns($directory->getPathname(), $ignore) as $pattern) {
                    if ("!" === $pattern[0]) {
                        $negativeUserIgnores[$pattern] = self::USER_IGNORE;
                    } else {
                        $positiveUserIgnores[$pattern] = self::USER_IGNORE;
                    }
                }
            }
            $ignores = $negativeUserIgnores;
        }

        if (file_exists($directory->getPathname() . "/.gitignore")) {
            $file = file_get_contents($directory->getPathname() . "/.gitignore");
            foreach (explode("\n", $file) as $ignore) {
                if (!empty($ignore)) {
                    foreach ($this->convertIgnoreToPatterns($directory->getPathname(), $ignore) as $pattern) {
                        $ignores[$pattern] = self::DYNAMIC_IGNORE;
                    }
                }
            }
        }

        foreach (scandir($directory->getPathname()) as $subDirectory) {
            if (in_array($subDirectory, [".", ".."])) {
                continue; // Skip dots
            }

            $subDirectory = new \SplFileInfo($directory->getPathname() . DIRECTORY_SEPARATOR . $subDirectory);

            if (!is_dir($subDirectory->getRealPath())) {
                continue; // Skip files
            }

            if (isset($ignores[$subDirectory->getPathname()]) && !isset($ignores["!" . $subDirectory->getPathname()])) {
                continue; // Skip ignored folders
            }

            $ignores = array_merge($ignores, $this->gatherIgnoredPatterns($subDirectory, []));
        }

        if (isset($positiveUserIgnores)) {
            $ignores = array_merge($ignores, $positiveUserIgnores);
        }

        return $ignores;
    }

    /**
     * @param string $basePath
     * @param string $ignore
     * @return array
     */
    private function convertIgnoreToPatterns($basePath, $ignore)
    {
        $ignore = trim($ignore);
        if (empty($ignore)) {
            return []; // Skip empty lines
        }

        if ("#" === $ignore[0]) {
            return []; // Skip comments
        }

        if ("!" === $ignore[0]) {
            $negative = true;
            $ignore = substr($ignore, 1); // Remove negative mark
        } else {
            $negative = false;
        }

        $patterns = [];
        if ("*" === $ignore) {
            $ignore = "/*"; // Simplify recursive star wildcard by non-recursive rule
        } elseif (DIRECTORY_SEPARATOR !== $ignore[0]) {
            $ignore = DIRECTORY_SEPARATOR . $ignore;
            $patterns = $this->convertIgnoreToPatterns($basePath, DIRECTORY_SEPARATOR . "**" . $ignore);
        }

        $fileInfo = new \SplFileInfo($basePath . $ignore);
        $realPath = $fileInfo->getPathname();
        if (empty($realPath) || !file_exists($realPath)) { // Unknown file or directory
            $realPath = $basePath . $ignore;
            $patterns[] = ($negative ? "!" : "") . $realPath;
            if (DIRECTORY_SEPARATOR . "*" !== $ignore && DIRECTORY_SEPARATOR !== substr($realPath, -1)) {
                $patterns[] = ($negative ? "!" : "") . $realPath . DIRECTORY_SEPARATOR;
            }
        } elseif ($fileInfo->isDir()) {
            $patterns[] = ($negative ? "!" : "") . $realPath . DIRECTORY_SEPARATOR;
        } else {
            $patterns[] = ($negative ? "!" : "") . $realPath;
        }

        return $patterns;
    }

    /**
     * @param array $ignores
     * @return array
     */
    private function compactIgnoredPatterns(array $ignores)
    {
        $compactedIgnores = [];
        foreach ($ignores as $ignore => $type) {
            if (empty($ignore)) {
                continue;
            }

            $compactedIgnore = null;
            if ("!" === $ignore[0]) {
                $path = substr($ignore, 1);
            } else {
                $path = $ignore;
            }
            if (preg_match(self::ABSOLUTE_IGNORE_PATTERN, $ignore)) {
                if (file_exists($path)) {
                    $compactedIgnore = $this->shortenIgnore($ignore);
                }
            } else {
                $compactedIgnore = $this->shortenIgnore($ignore);
            }

            if (empty($compactedIgnore)) {
                continue;
            }

            if (self::DYNAMIC_IGNORE === $type) {
                if (isset($ignores[$path]) && self::USER_IGNORE === $ignores[$path]) {
                    continue;
                } elseif (isset($ignores["!" . $path]) && self::USER_IGNORE === $ignores["!" . $path]) {
                    continue;
                }
            }

            $compactedIgnores[$compactedIgnore] = $type;
        }

        $uniqueCompactedIgnores = [];
        foreach (array_reverse($compactedIgnores) as $compactedIgnore => $type) {
            if ("!" === $compactedIgnore[0]) {
                $path = substr($compactedIgnore, 1);
            } else {
                $path = $compactedIgnore;
            }
            if (!isset($uniqueCompactedIgnores[$path]) && !isset($uniqueCompactedIgnores["!" . $path])) {
                $uniqueCompactedIgnores[$compactedIgnore] = $type;
            }
        }

        return array_keys(array_reverse($uniqueCompactedIgnores));
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
