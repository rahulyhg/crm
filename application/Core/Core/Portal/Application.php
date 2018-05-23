<?php


namespace Core\Core\Portal;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\NotFound;
use \Core\Core\Exceptions\Forbidden;

class Application extends \Core\Core\Application
{
    public function __construct($portalId)
    {
        date_default_timezone_set('UTC');

        $this->initContainer();

        if (empty($portalId)) {
            throw new Error("Portal id was not passed to ApplicationPortal.");
        }

        $GLOBALS['log'] = $this->getContainer()->get('log');

        $portal = $this->getContainer()->get('entityManager')->getEntity('Portal', $portalId);

        if (!$portal) {
            $portal = $this->getContainer()->get('entityManager')->getRepository('Portal')->where(array(
                'customId' => $portalId
            ))->findOne();
        }

        if (!$portal) {
            throw new NotFound();
        }
        if (!$portal->get('isActive')) {
            throw new Forbidden("Portal is not active.");
        }

        $this->portal = $portal;

        $this->getContainer()->setPortal($portal);

        $this->initAutoloads();
    }

    protected function getPortal()
    {
        return $this->portal;
    }

    protected function initContainer()
    {
        $this->container = new Container();
    }

    protected function getRouteList()
    {
        $routeList = parent::getRouteList();
        foreach ($routeList as $i => $route) {
            if (isset($route['route'])) {
                if ($route['route']{0} !== '/') {
                    $route['route'] = '/' . $route['route'];
                }
                $route['route'] = '/:portalId' . $route['route'];
            }
            $routeList[$i] = $route;
        }
        return $routeList;
    }

    public function runClient()
    {
        $this->getContainer()->get('clientManager')->display(null, 'html/portal.html', array(
            'portalId' => $this->getPortal()->id
        ));
    }
}

