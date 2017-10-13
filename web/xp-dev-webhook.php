<?php
use nvbooster\Repo\SatisHelper;

set_time_limit(0);
include '../vendor/autoload.php';

if (null !== $json = json_decode(file_get_contents('php://input'), true)) {

    $repositoryPath = $json['repository_path'];

    $helper = new SatisHelper();

    $matches = array();
    foreach ($helper->getReposIndex() as $repo) {
        if ('svn' === $repo['type'] && (0 === strpos($repo['url'], $repositoryPath))) {
            $matches[] = $repo['url'];
        }
    }

    $matches = array_unique($matches);

    $context = array(
        'channel' => 'xp-dev',
        'repository' => $json['repository'],
        'revision' => $json['revision'],
    );

    if (count($matches)) {
        if (count($matches) == 1) {
            $helper->runSatis($context, $matches[0]);
        } else {
            $helper->runSatis($context);
        }
    }
}
