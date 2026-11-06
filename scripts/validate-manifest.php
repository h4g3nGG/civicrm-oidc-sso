<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$manifest = simplexml_load_file($root . '/info.xml');
if ($manifest === false) {
    throw new RuntimeException('info.xml is not well-formed XML.');
}
$required = ['file', 'name', 'description', 'license', 'releaseDate', 'version'];
foreach ($required as $element) {
    if (!isset($manifest->{$element}) || trim((string) $manifest->{$element}) === '') {
        throw new RuntimeException("info.xml is missing required element {$element}.");
    }
}
if (trim((string) $manifest->maintainer->author) === ''
    || filter_var((string) $manifest->maintainer->email, FILTER_VALIDATE_EMAIL) === false
) {
    throw new RuntimeException('info.xml must contain a maintainer author and valid email.');
}
if (!isset($manifest->urls->url[0]) || !isset($manifest->compatibility->ver[0])) {
    throw new RuntimeException('info.xml must contain project URLs and CiviCRM compatibility.');
}
if ((string) $manifest['type'] !== 'module') {
    throw new RuntimeException('Extension type must be module.');
}
if ((string) $manifest['key'] !== 'oidc_sso' || (string) $manifest->file !== 'oidc_sso') {
    throw new RuntimeException('Extension key, directory and entrypoint must be oidc_sso.');
}
if (!is_file($root . '/' . (string) $manifest->file . '.php')) {
    throw new RuntimeException('The manifest entrypoint does not exist.');
}
if (preg_match('/^\d+\.\d+\.\d+(?:-(?:alpha|beta|rc)\.\d+|-dev)?$/D', (string) $manifest->version) !== 1) {
    throw new RuntimeException('Extension version is not supported Semantic Versioning.');
}
if (DateTimeImmutable::createFromFormat('!Y-m-d', (string) $manifest->releaseDate) === false) {
    throw new RuntimeException('Release date must use YYYY-MM-DD.');
}
foreach ($manifest->urls->url as $url) {
    if (filter_var((string) $url, FILTER_VALIDATE_URL) === false || !str_starts_with((string) $url, 'https://')) {
        throw new RuntimeException('All extension metadata URLs must be valid HTTPS URLs.');
    }
}
echo "MANIFEST OK\n";
