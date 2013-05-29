<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Github\Client;

class Annotator
{
    private $user;
    private $repo;
    private $sha;
    private $token;
    private $client;
    private $prefix = "**Build Status**: ";

    public function __construct($user, $repo, $sha)
    {
        $this->user = $user;
        $this->repo = $repo;
        $this->sha = $sha;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    private function getClient()
    {
        if ($this->client) {
            $client = $this->client;
        } else {
            if (! $this->token) {
                throw new \RuntimeException('There must be a token set in order to authenticate with GitHub');
            }

            $client = new Client;
            $client->authenticate($this->token, null, Client::AUTH_HTTP_TOKEN);
        }

        return $client;
    }

    private function pull()
    {
        $pulls = $this->getClient()->api('pull_request')->all($this->user, $this->repo, 'open');

        foreach ($pulls as $pull) {
            if ($pull['head']['sha'] == $this->sha) {
                return $pull;
            }
        }

        return null;
    }

    private function uncomment($pull)
    {
        $comments = $this->getClient()->api('issue')->comments()->all($this->user, $this->repo, $pull['number']);

        foreach ($comments as $comment) {
            if (strpos($comment['body'], $this->prefix) !== false) {
                $this->getClient()->api('issue')->comments()->remove($this->user, $this->repo, $comment['id']);
            }
        }
    }

    private function title($pull, $status)
    {
        $prefix = (strtolower($status) == 'success') ? '[Tests pass] ' : '[Tests fail] ';
        $title = $prefix . preg_replace('/\[Tests (fail|pass)\] /', '', $pull['title']);

        $this->getClient()->api('issue')->update($this->user, $this->repo, $pull['number'], array(
            'title' => $title,
        ));
    }

    private function comment($pull, $status, $url, $out)
    {
        $body = $this->prefix;
        $body .= (strtolower($status) == 'success') ? '[Success]' : '[Failure]';
        $body .= "({$url})";
        $body .= "\n```\n" . (string) $out . "\n```";

        $this->getClient()->api('issue')->comments()->create($this->user, $this->repo, $pull['number'], array(
            'body' => $body,
        ));
    }

    public function run($data)
    {
        $pull = $this->pull();
        if (! $pull) {
            return null;
        }

        echo "Annotator running with data set: " . PHP_EOL;
        print_r($data);
        echo PHP_EOL;

        $this->uncomment($pull);
        $this->title($pull, $data['status']);
        $this->comment($pull, $data['status'], $data['url'], $data['out']);
    }
}

$app = new Silex\Application(); 

$app->get('/', function () {
    return new Response('Index');
});

$app->post('/{user}/{repo}/{sha}', function(Request $request) use($app) { 
    $data = array(
        'status'  => $request->get('status'),
        'url'     => $request->get('url'),
        'out'     => $request->get('out'),
    );

    $annotator = new Annotator($request->get('user'), $request->get('repo'), $request->get('sha'));
    $annotator->setToken($request->get('token'));
    $annotator->run($data);

    return new Response('Done');
});

$app['debug'] = true;
$app->run(); 
