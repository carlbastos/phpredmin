<?php

class Keys_Controller extends Controller
{
    public function searchAction()
    {
        if ($this->router->method == Router::POST) {
            $key = $this->inputs->post('key', Null);

            if (isset($key) && trim($key) != '') {
                $keys = $this->db->keys("{$key}*");
                asort($keys);
                Template::factory()->render('keys/search', Array('keys' => $keys, 'search' => $key));
            } else
                Template::factory()->render('invalid_input');
        }
    }

    public function moveAction($key)
    {
        $moved = Null;

        if ($this->router->method == Router::POST) {
            $db  = $this->inputs->post('db', Null);
            $key = $this->inputs->post('key', Null);

            if (!isset($db) || trim($db) == '' || !isset($key) || trim($key) == '')
                $moved = False;
            else
                $moved = $this->db->move($key, $db);
        }

        Template::factory()->render('keys/move', array('moved' => $moved, 'key' => urldecode($key)));
    }

    public function renameAction($key)
    {
        $renamed = Null;

        if ($this->router->method == Router::POST) {
            $newkey = $this->inputs->post('newkey', Null);
            $key    = $this->inputs->post('key', Null);

            if (!isset($newkey) || trim($newkey) == '' || !isset($key) || trim($key) == '')
                $renamed = False;
            else
                $renamed = $this->db->rename($key, $newkey);
        }

        Template::factory()->render('keys/rename', array('renamed' => $renamed, 'key' => urldecode($key)));
    }

    public function expireAction($key)
    {
        $updated = Null;
        $oldttl  = $this->db->ttl(urldecode($key));

        if ($this->router->method == Router::POST) {
            $ttl = $this->inputs->post('ttl', Null);
            $key = $this->inputs->post('key', Null);

            if (!isset($ttl) || trim($ttl) == '' || !isset($key) || trim($key) == '')
                $updated = False;
            else
                if ((int)$ttl > 0)
                    $updated = $this->db->expire($key, $ttl);
                else
                    if ($oldttl > 0)
                        $updated = $this->db->persist($key);
                    else
                        $updated = True;
        }



        Template::factory()->render('keys/ttl', array('updated' => $updated, 'key' => urldecode($key), 'ttl' => $oldttl));
    }

    public function moveallAction()
    {
        if ($this->router->method == Router::POST) {
            $results     = Array();
            $values      = $this->inputs->post('values', array());
            $destination = $this->inputs->post('destination');

            foreach ($values as $key => $value)
                $results[$value] = $this->db->move($value, $destination);

            Template::factory('json')->render($results);
        }
    }

    public function delallAction()
    {
        if ($this->router->method == Router::POST) {
            $results = Array();
            $values  = $this->inputs->post('values', array());

            foreach ($values as $key => $value)
                $results[$value] = $this->db->del($value);

            Template::factory('json')->render($results);
        }
    }

    public function bulkdeleteAction()
    {
        if ($this->router->method == Router::POST) {
            $key = $this->inputs->post('key', Null);

            if (isset($key) && trim($key) != '') {
                $config = App::instance()->config;
                $pheanstalk = new Pheanstalk_Pheanstalk($config['beanstalkd']['host'], $config['beanstalkd']['port']);
                $pheanstalk->useTube('phpredmin')
                    ->put('deleteKeys '.urlencode($key));
            }
        }
    }

    public function deleteinfoAction($key)
    {
        $this->db->incrBy("phpredmin:requests:{$key}", 1);

        $key      = urldecode($key);
        $total    = $this->db->get("phpredmin:deletecount:{$key}");
        $count    = $this->db->get("phpredmin:deleted:{$key}");
        $requests = $this->db->get("phpredmin:requests:{$key}");

        if ($total === false && $count !== false && $requests == 1)
            $total = $count;

        $result = array($total, $count);

        Template::factory('json')->render($result);
    }

    public function deleteAction($key)
    {
        Template::factory('json')->render($this->db->del(urldecode($key)));
    }

    public function viewAction($key)
    {
        switch ($this->db->type(urldecode($key))) {
            case Redis::REDIS_STRING:
                $this->router->redirect("strings/view/{$key}");
                break;
            case Redis::REDIS_SET:
                $this->router->redirect("sets/view/{$key}");
                break;
            case Redis::REDIS_LIST:
                $this->router->redirect("lists/view/{$key}");
                break;
            case Redis::REDIS_ZSET:
                $this->router->redirect("zsets/view/{$key}");
                break;
            case Redis::REDIS_HASH:
                $this->router->redirect("hashes/view/{$key}");
                break;
        }
    }
}
