<?php
namespace php_require\hoobr_packages;

class Utils {

    /*
        Deletes the given directory and all files in it.

        Copy of same function in php-package.
    */

    public function deleteDir($dirpath) {

        if (!is_dir($dirpath)) {
            throw new InvalidArgumentException("$dirpath must be a directory");
        }

        if (substr($dirpath, strlen($dirpath) - 1, 1) != DIRECTORY_SEPARATOR) {
            $dirpath .= DIRECTORY_SEPARATOR;
        }

        $cdir = scandir($dirpath);

        foreach ($cdir as $key => $value) {

            if (!in_array($value, array(".",".."))) {

                $fullpath = $dirpath . DIRECTORY_SEPARATOR . $value;

                if (is_dir($fullpath)) {
                    $this->deleteDir($fullpath);
                } else {
                    unlink($fullpath);
                }
            }
        }

        return rmdir($dirpath);
    }

    public function getModuleList($dirpath) {

        global $require;

        $pathlib = $require("php-path");

        $dirpath = $pathlib->join($dirpath, "node_modules");

        $modules = $this->inspectDir($dirpath, $pathlib);

        $list = array();

        foreach ($modules as $fullpath => $package) {
            if (isset($package["config"]["hoobr"]["type"]) && $package["config"]["hoobr"]["type"] === "module") {
                array_push($list, $package["name"]);
            }
        }

        return $list;
    }

    public function inspectModule($dirpath) {

        global $require;

        $pathlib = $require("php-path");

        $filepath = $pathlib->join($dirpath, "package.json");

        if (!is_file($filepath)) {
            return null;
        }

        $package = json_decode(file_get_contents($filepath), true);

        return $package;
    }

    public function inspectDir($dirpath) {

        global $require;

        $pathlib = $require("php-path");

        $modules = array();

        $files = scandir($dirpath);

        foreach ($files as $file) {

            if (!in_array($file, array(".", ".."))) {

                $fullpath = $pathlib->join($dirpath, $file);
                $package = $this->inspectModule($fullpath, $pathlib);

                if ($package && isset($package["config"]["hoobr"])) {
                    $modules[$fullpath] = $package;
                }
            }
        }

        return $modules;
    }
}

$module->exports = new Utils();
