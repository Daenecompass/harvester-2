<?php
namespace Erpk\Harvester\Module\Login;

use Erpk\Harvester\Module\Module;
use Erpk\Harvester\Exception\ScrapeException;
use XPathSelector\Exception\NotFoundException;

class LoginModule extends Module
{
    public function login()
    {
        $client = $this->getClient();
        $login = $client->post('login');
        $login->addPostFields([
            '_token'            =>  md5(time()),
            'citizen_email'     =>  $client->getEmail(),
            'citizen_password'  =>  $client->getPassword(),
            'remember'          =>  1
        ]);
        
        $login->setHeader('Referer', $client->getBaseUrl());
        $login = $login->send();
        if (!$login->isRedirect()) {
            throw new ScrapeException('Login failed.');
        }

        $hxs = $client->get()->send()->xpath();
        $token = null;
        try {
            $token = $hxs->find('//*[@id="_token"][1]/@value')->extract();
        } catch (NotFoundException $e) {
            $scripts = $hxs->findAll('//script[@type="text/javascript"]');
            $tokenPattern = '@csrfToken\s*:\s*\'([a-z0-9]+)\'@';
            foreach ($scripts as $script) {
                if (preg_match($tokenPattern, $script->extract(), $matches)) {
                    $token = $matches[1];
                    break;
                }
            }
        }

        if ($token === null) {
            throw new ScrapeException('CSRF token not found');
        }

        $link = $hxs->find('//a[@class="user_avatar"][1]');
        $this->getClient()->getSession()
            ->setToken($token)
            ->setCitizenId((int)explode('/', $link->find('@href')->extract())[4])
            ->setCitizenName($link->find('@title')->extract())
            ->save();
    }

    public function logout()
    {
        $this->getClient()->post('logout')->send();
    }
}
