<?php

$conf = array(
  'type' => 'lftp',
  'host' => 'myhost.com',
  'user' => 'syncman',
  'password' => 'qwerty',
  'port' => 21,
  'repository' => 'svn://myrepos/projectB/trunk',
  'remote_dir' => '/public_html/test',
  'presync_cmd' => 'php %local_dir%/cli/pre_sync.php',
  'postsync_cmd' => 'lftp -e \'rm -rf %remote_dir%/var/compiled;rm -rf %remote_dir%/var/locators; bye;\' -u %user%,%password% %host%',
  'history' => false,
  'opts' => '--verbose=3',
  'category' => 'my_own',
);
