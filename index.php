<?php
namespace php_require\hoobr_packages;

$req = $require("php-http/request");
$res = $require("php-http/response");
$render = $require("php-render-php");
$pathlib = $require("php-path");
$package = $require("php-package");

/*
    Deletes the given directory and all files in it.

    Copy of same function in php-package.
*/

function deleteDir($dirpath) {

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
                deleteDir($fullpath);
            } else {
                unlink($fullpath);
            }
        }
    }

    return rmdir($dirpath);
}

function getModuleList($dirpath, $pathlib) {

    $dirpath = $pathlib->join($dirpath, "node_modules");

    $modules = inspectDir($dirpath, $pathlib);

    $list = array();

    foreach ($modules as $fullpath => $package) {
        if (isset($package["config"]["hoobr"]["type"]) && $package["config"]["hoobr"]["type"] === "module") {
            array_push($list, $package["name"]);
        }
    }

    return $list;
}

function inspectModule($dirpath, $pathlib) {

    $filepath = $pathlib->join($dirpath, "package.json");

    if (!is_file($filepath)) {
        return null;
    }

    $package = json_decode(file_get_contents($filepath), true);

    return $package;
}

function inspectDir($dirpath, $pathlib) {

    $modules = array();

    $files = scandir($dirpath);

    foreach ($files as $file) {

        if (!in_array($file, array(".", ".."))) {

            $fullpath = $pathlib->join($dirpath, $file);
            $package = inspectModule($fullpath, $pathlib);

            if ($package && isset($package["config"]["hoobr"])) {
                $modules[$fullpath] = $package;
            }
        }
    }

    return $modules;
}

$exports["admin-menu"] = function () use ($req, $render, $pathlib) {

    $list = getModuleList($req->cfg("approot"), $pathlib);

    return $render($pathlib->join(__DIR__, "views", "admin-menu.php.html"), array(
        "list" => $list,
        "current" => $req->param("module")
    ));
};

$exports["admin-sidebar"] = function () use ($req, $render, $pathlib) {

    $list = getModuleList($req->cfg("approot"), $pathlib);

    return $render($pathlib->join(__DIR__, "views", "admin-sidebar.php.html"), array(
        "list" => $list,
        "current" => $req->param("module")
    ));
};

$exports["admin-main"] = function () use ($req, $render, $pathlib) {

    $dirpath = $pathlib->join($req->cfg("approot"), "node_modules");

    $modules = inspectDir($dirpath, $pathlib);

    $list = array();

    foreach ($modules as $fullpath => $package) {
        array_push($list, $package);
    }

    return $render($pathlib->join(__DIR__, "views", "admin-main.php.html"), array(
        "list" => $list
    ));
};

$exports["admin-install"] = function () use ($req, $res, $render, $pathlib, $package) {

    $source = $req->param("zip-url");
    $destination = $pathlib->join($req->cfg("approot"), "node_modules");

    if (!$req->param("hoobr-package-action") || !$source) {
        return $render($pathlib->join(__DIR__, "views", "admin-install.php.html"));
    }

    $result = $package($source, $destination);

    if (isset($result["error"])) {
        return $render($pathlib->join(__DIR__, "views", "admin-install.php.html"), $result);
    }

    $res->redirect("?module=" . $result["package"]);
};

$exports["admin-uninstall"] = function () use ($req, $res, $render, $pathlib, $package) {

    $packageName = $req->param("package-name");
    $confirm = $req->param("confirm");
    $dirpath = $pathlib->join($req->cfg("approot"), "node_modules", $packageName);

    if ($packageName && is_dir($dirpath) && $confirm === "true") {
        if (deleteDir($dirpath)) {
            $res->redirect("?module=hoobr-packages");
        }
    }

    return $render($pathlib->join(__DIR__, "views", "admin-uninstall.php.html"), array(
        "packageName" => $packageName,
        "error" => !is_dir($dirpath)
    ));
};
