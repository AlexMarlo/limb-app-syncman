<?php

$conf = array(
  'host' => 'myhost.com',
  'user' => 'syncman',
  'key' => '/home/syncman/.ssh/id_dsa',
  'repository' => 
    array (
      'type' => 'git',
      'path' => 'git://myrepos/project.git',
    ),
  'remote_dir' => '/var/www/projectA',
  'presync_cmd' => 'php %local_dir%/cli/pre_sync.php',
  'postsync_cmd' => 'ssh -i %key% %user%@%host% \'php %remote_dir%/cli/post_sync.php\'',
  'history' => false,
  //'rsync_opts' => '-kL --copy-links', //to copy simlinks as files
  'category' => 'demo',
);
