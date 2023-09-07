<?php return array(
    'root' => array(
        'name' => 'pluginmaniacs/openaiplugin',
        'pretty_version' => '1.0.0+no-version-set',
        'version' => '1.0.0.0',
        'reference' => NULL,
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'openai/openai' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '3943a54d2a974d4b3246842846cb0cf4414aa308',
            'type' => 'library',
            'install_path' => __DIR__ . '/../openai/openai',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
        'pluginmaniacs/openaiplugin' => array(
            'pretty_version' => '1.0.0+no-version-set',
            'version' => '1.0.0.0',
            'reference' => NULL,
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
