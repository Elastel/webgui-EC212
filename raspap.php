<?php

function getConfig()
{
    $config = array(
        'admin_user' => 'admin',
        'admin_pass' => '$2y$10$uzRyKtDwHERSfpbAs8G65eBrAvH8q19se0PAZGNRCgrwqx5.9IOve'
    );

    if (file_exists(RASPI_CONFIG . '/raspap.auth')) {
        if ($auth_details = fopen(RASPI_CONFIG . '/raspap.auth', 'r')) {
            $config['admin_user'] = trim(fgets($auth_details));
            $config['admin_pass'] = trim(fgets($auth_details));
            fclose($auth_details);
        }
    }
    return $config;
}

