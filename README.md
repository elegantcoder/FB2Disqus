FB2Disqus
=========

Facebook Social Comment to Disqus Exporter

## Usage
export.php
```php
#!/bin/env php
<?php
require 'FB2Disqus.php';
FB2Disqus::run(array(
  // may find facebook keys at https://developers.facebook.com/apps
  'fbAppId' => '',
  'fbSecret' => '',
  // may find disqus keys at http://disqus.com/api/applications/
  'dsqSecret' => '',
  
  // target forum's short name 
  'dsqForumId' => 'elegantcodertest', //example

  'emailForAnonymous' => 'anon@anonymous.com',

  'commentURLs' => array(
    // disqus thread id => comment url(facebook)
    '322626171' => 'http://elegantcoder.com/pro-ft-engineer'
  )
));
```

