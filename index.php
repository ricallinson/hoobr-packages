<?php
namespace php_require\hoobr_packages;

$req = $require("php-http/request");
$res = $require("php-http/response");
$render = $require("php-render-php");
$pathlib = $require("php-path");
$package = $require("php-package");
$utils = $require("hoobr-packages/lib/utils");

$exports["admin-menu"] = function () use ($req, $render, $pathlib, $utils) {

    $list = $utils->getModuleList($req->cfg("approot"));

    return $render($pathlib->join(__DIR__, "views", "admin-menu.php.html"), array(
        "list" => $list,
        "current" => $req->param("module")
    ));
};

$exports["admin-sidebar"] = function () use ($req, $render, $pathlib, $utils) {

    $list = $utils->getModuleList($req->cfg("approot"));

    return $render($pathlib->join(__DIR__, "views", "admin-sidebar.php.html"), array(
        "list" => $list,
        "current" => $req->param("module")
    ));
};

$exports["admin-main"] = function () use ($req, $render, $pathlib, $utils) {

    $dirpath = $pathlib->join($req->cfg("approot"), "node_modules");

    $modules = $utils->inspectDir($dirpath);

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

    $res->redirect("?page=admin&module=" . $result["package"]);
};

$exports["admin-uninstall"] = function () use ($req, $res, $render, $pathlib, $package, $utils) {

    $packageName = $req->param("package-name");
    $confirm = $req->param("confirm");
    $dirpath = $pathlib->join($req->cfg("approot"), "node_modules", $packageName);

    if ($packageName && is_dir($dirpath) && $confirm === "true") {
        if ($utils->deleteDir($dirpath)) {
            $res->redirect("?page=admin&module=hoobr-packages");
        }
    }

    return $render($pathlib->join(__DIR__, "views", "admin-uninstall.php.html"), array(
        "packageName" => $packageName,
        "error" => !is_dir($dirpath)
    ));
};
