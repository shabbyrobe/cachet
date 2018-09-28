<?php
namespace Cachet\Util;

class File
{
    public $user;
    public $group;
    public $filePerms;
    public $dirPerms;

    /** @var string */
    public $basePath;

    /**
     * Dangerous - can break setting when happening concurrently.
     */
    public $purgeEmptyDirs = false;

    public function __construct($basePath, $options=array())
    {
        if (!is_writable($basePath)) {
            throw new \InvalidArgumentException("Base path must be writable");
        }

        $this->basePath = $basePath;
        $this->user = isset($options['user']) ? $options['user'] : null;
        $this->group = isset($options['group']) ? $options['group'] : null;
        $this->filePerms = isset($options['filePerms']) ? $options['filePerms'] : null;
        $this->dirPerms = isset($options['dirPerms']) ? $options['dirPerms'] : null;

        if ($unknown = array_diff(array_keys($options), array('user', 'group', 'filePerms', 'dirPerms'))) {
            throw new \InvalidArgumentException("Unknown options: ".implode(', ', $unknown));
        }
    }

    function read($path, &$found=null)
    {
        $found = false;
        $fullPath = $this->resolvePath($path);
        if (file_exists($fullPath)) {
            if (!is_file($fullPath)) {
                throw new \UnexpectedValueException();
            }
            $found = true;
            return file_get_contents($fullPath);
        }
    }

    private function ensurePathExists($path, $fullPath)
    {
        $fullDir = dirname($fullPath);
        if (!file_exists($fullDir)) {
            $dir = dirname($path);
            $parts = preg_split('~[/\\\\]~', $dir, null, PREG_SPLIT_NO_EMPTY);
            $current = $this->basePath;

            // can't use mkdir recursive mode because it is affected by umask
            foreach ($parts as $part) {
                $current .= "/$part";
                if (!file_exists($current)) {
                    mkdir($current);
                    $this->applySettings($current, false);
                }
            }
        }
    }

    function open($path, $mode)
    {
        $fullPath = $this->resolvePath($path);
        $this->ensurePathExists($path, $fullPath);
        $handle = fopen($fullPath, $mode);
        $this->applySettings($fullPath, true);
        return $handle;
    }

    function write($path, $data)
    {
        $fullPath = $this->resolvePath($path);
        $this->ensurePathExists($path, $fullPath);
        file_put_contents($fullPath, $data);
        $this->applySettings($fullPath, true);
    }

    function delete($path)
    {
        $fullPath = $this->resolvePath($path);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }

    function flush($path)
    {
        $iter = $this->getIterator($path);
        if (!$iter) {
            return;
        }

        $lastDir = null;

        foreach ($iter as $item) {
            if ($item->isFile()) {
                $currentDir = dirname($item);
                unlink($item);
            }
            else {
                $currentDir = $item.'';
            }

            if ($this->purgeEmptyDirs) {
                if ($lastDir != null && $currentDir != $lastDir) {
                    if (count(glob("$lastDir/*")) === 0) {
                        // this cast is to quieten phan, which can't verify the guard clauses yet.
                        rmdir((string)$lastDir);
                    }
                }
                $lastDir = $currentDir;
            }
        }
        if ($this->purgeEmptyDirs && $lastDir && count(glob("$lastDir/*")) === 0) {
            rmdir($lastDir);
        }
    }

    function getIterator($path, $iteratorMode=\RecursiveIteratorIterator::LEAVES_ONLY)
    {
        $fullPath = $this->resolvePath($path);
        if (!file_exists($fullPath))
            return;

        $dir = new \RecursiveDirectoryIterator(
            $fullPath,
            \FilesystemIterator::KEY_AS_PATHNAME |
            \FilesystemIterator::CURRENT_AS_FILEINFO |
            \FilesystemIterator::SKIP_DOTS
        );
        $iter = new \RecursiveIteratorIterator($dir, $iteratorMode);
        return $iter;
    }

    private function applySettings($name, $file=true)
    {
        if ($this->user) {
            chown($name, $this->user);
        }
        if ($this->group) {
            chown($name, $this->group);
        }

        if ($file) {
            if ($this->filePerms) {
                chmod($name, $this->filePerms);
            }
        }
        else {
            if ($this->dirPerms) {
                chmod($name, $this->dirPerms);
            }
        }
    }

    private function resolvePath($path=null)
    {
        // hacky base path escape prevention
        $path = str_replace('..', '', $path);

        return "{$this->basePath}/$path";
    }
}
