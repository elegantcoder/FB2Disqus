<?php
require 'facebook-sdk/src/facebook.php';
require 'disqus-php/disqusapi/disqusapi.php';

class FB2Disqus {
  private $_fb = null;
  private $_fbAppId = null;
  private $_fbSecret = null;

  private $_dsq = null;
  private $_dsqSecret = null;
  private $_dsqForumId = null;

  private $_comments = array();
  private $_users = array();

  function __construct ($options) {
    $this->_fbAppId = $options['fbAppId'];
    $this->_fbSecret = $options['fbSecret'];

    $this->_dsqSecret = $options['dsqSecret'];
    $this->_dsqorumId = $options['dsqForumId'];

    $this->_emailForAnonymous = $options['emailForAnonymous'];
    $this->_commentURLs = $options['commentURLs'];

    $this->_fb = new Facebook(array('appId' => $this->_fbAppId, 'secret' => $this->_fbSecret));
    $this->_dsq = new DisqusAPI($this->_dsqSecret);
  }

  public static function run($options) {
    $fb2disqus = new FB2Disqus($options);
    // $fb2disqus->disqustest();
    foreach ($options['commentURLs'] as $thread => $url) {
      $fb2disqus->fetchComments($url);
      $fb2disqus->collectUsers($fb2disqus->_comments[$fb2disqus->_currentComment]);
      $fb2disqus->fetchUsers();
      $fb2disqus->convert($fb2disqus->_comments[$fb2disqus->_currentComment], $thread);
      $fb2disqus->post();
     }
  }

  private function fetchComments ($url) {
    $comments = $this->_fb->api('/comments', 'GET', array('ids'=>$url, 'limits'=>100));
    $this->_comments = array_merge($this->_comments, $comments);
    list($this->_currentComment) = each($comments);
  }

  private function collectUsers ($res) {
    if ($res['comments']) {
      foreach($res['comments']['data'] as $comment) {
        // recursive
        if ($comment['comments']) $this->collectUsers($comment);
        //queue
        $userId = $comment['from']['id'];
        if (!$this->_users[$userId]) {
          $this->_users[$userId] = null;
        }
      }
    }
  }

  private function fetchUsers () {
    foreach ($this->_users as $userID => $userInfo) {
      if (!$userInfo) {
        $this->_users[$userID] = $this->_fb->api('/'.$userID, 'GET');
      }
    }
  }

  private function convert ($res, $thread, $parent = null) {    if ($res['comments']) {
      foreach($res['comments']['data'] as $comment) {        
        $user = $this->_users[$comment['from']['id']];
        if ($user['link']) {
          $author_url = $user['link'];
        } else if ($user['username']) {
          $author_url = 'http://facebook.com/'.$user['username'];
        }
        
        $createRes = $this->_dsq->posts->create(array(
          'message' => $comment['message'],
          'thread' => $thread,
          'parent' => $parent,
          'author_name' => $user['name'],
          'author_email' => !$user['username']? $this->_emailForAnonymous : $user['username'].'@facebook.com',
          'author_url' => $author_url,
          'state' => 'approved',
          'date' => strtotime($comment['created_time']),
        ));
        try {
          $this->convert($comment, $thread, $createRes->id);
        } catch (Exception $e) {
          var_export($e);
          var_export($comment);
        }
        
      }
    }
  }
}
