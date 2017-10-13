<?php
use nvbooster\Repo\SatisHelper;

set_time_limit(0);
include '../vendor/autoload.php';

if (!empty($_SERVER['HTTP_X_GITHUB_EVENT'])
    && ('push' == $_SERVER['HTTP_X_GITHUB_EVENT'])
    && (null !== $json = json_decode(file_get_contents('php://input'), true))
) {
    $repositoryPath = $json['repository']['clone_url'];

    $helper = new SatisHelper();

    $matches = false;
    foreach ($helper->getReposIndex() as $repo) {
        if ($repo['url'] === $repositoryPath) {
            $matches = true;
            break;
        }
    }

    $context = array(
        'channel' => 'github',
        'repository' => $json['repository']['full_name'],
        'revision' => substr($json['after'], 0, 7),
    );

    if ($matches) {
        $helper->runSatis($context, $repositoryPath);
    }
}
