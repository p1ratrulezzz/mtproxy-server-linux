<?php

if (!file_exists('madeline.phar') || !file_exists('madeline.phar.version') || (file_get_contents('madeline.phar.version') !== file_get_contents('https://phar.madelineproto.xyz/release?v=new') && file_get_contents('https://phar.madelineproto.xyz/release?v=new'))) {
    $release = file_get_contents('https://phar.madelineproto.xyz/release?v=new');
    $phar = file_get_contents('https://phar.madelineproto.xyz/madeline.phar?v=new');
    if ($release && $phar) {
        file_put_contents('madeline.phar', $phar);
        file_put_contents('madeline.phar.version', $release);
    }
    unset($release);
    unset($phar);
}

require 'madeline.phar';

