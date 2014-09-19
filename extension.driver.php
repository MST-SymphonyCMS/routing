<?php

require_once CORE . '/class.cacheable.php';

class Extension_Routing extends Extension
{
    private static $resolved;

    // delegates

    public function getSubscribedDelegates()
    {
        return array(

            array('page'     => '/frontend/',
                  'delegate' => 'FrontendPrePageResolve',
                  'callback' => 'frontendPrePageResolve')
        );
    }

    // install

    public function install()
    {
        // add configuration

        Symphony::Configuration()->set('path', 'routes.xml', 'routing');
        Symphony::Configuration()->write();

        // create example

        if (!file_exists(WORKSPACE . '/routes.xml')) {

            copy(EXTENSIONS . '/routing/routes.xml', WORKSPACE . '/routes.xml');
        }

        // create cache

        $this->enable();
    }

    // uninstall

    public function uninstall()
    {
        // remove configuration

        Symphony::Configuration()->remove('routing');
        Symphony::Configuration()->write();

        // clear cache

        $this->disable();
    }

    // enable

    public function enable()
    {
        // refresh cache

        $this->cache(true);
    }

    // disable

    public function disable()
    {
        $cache    = new Cacheable(Symphony::Database());
        $cache_id = md5('routes');

        // clear cache

        $cache->forceExpiry($cache_id);
    }

    // routing

    public function frontendPrePageResolve($context)
    {
        if (!self::$resolved) {

            if ($path_from = $context['page']) {

                if (is_array($routes = $this->cache())) {

                    foreach ($routes as $route_from => $route_to) {

                        // actual routing

                        if (preg_match($route_from, $path_from)) {

                            $path_to = preg_replace($route_from, $route_to, $path_from);

                            break;
                        }
                    }
                }

                if ($path_to) {

                    $context['page'] = $path_to;

                } else {

                    self::$resolved = true;

                    throw new FrontendPageNotFoundException();
                }
            }

            self::$resolved = true;
        }
    }

    // cache

    private function cache($refresh = null)
    {
        $cache    = new Cacheable(Symphony::Database());
        $cache_id = md5('routes');

        if ($refresh || !($cache_data = $cache->check($cache_id))) {

            // clear cache

            $cache->forceExpiry($cache_id);

            // get xml path

            $path = Symphony::Configuration()->get('path', 'routing');
            $path = WORKSPACE . '/' . trim($path, '/');

            // get routes from xml

            $routes = array();

            if (!$refresh) {

                libxml_use_internal_errors(true);
            }

            if ($xml = simplexml_load_file($path)) {

                // prepare routes

                foreach ($xml->route as $route) {

                    if (isset($route['from']) && isset($route['to'])) {

                        // prepare route

                        $route_from = (string) $route['from'];
                        $route_to   = (string) $route['to'];

                        $route_from = '/' . trim($route_from, '/') . '/';
                        $route_to   = '/' . trim($route_to,   '/') . '/';

                        // prepare filters

                        $route_filters = array();

                        foreach ($route->filter as $filter) {

                            if (isset($filter['parameter']) && isset($filter['match'])) {

                                $filter_parameter = (string) $filter['parameter'];
                                $filter_match     = (string) $filter['match'];

                                $route_filters[$filter_parameter] = trim($filter_match, '()');
                            }
                        }

                        // prepare parameters

                        preg_match_all('/(:[\w-]+)/u', $route_from, $matches);

                        foreach ($matches[0] as $index => $parameter) {

                            // check filter

                            if (!isset($route_filters[$parameter])) {

                                // set default filter

                                $route_filters[$parameter] = '[\w\.-]+';
                            }

                            // subsitute parameters

                            $route_from = str_replace($parameter, '(' . $route_filters[$parameter] . ')', $route_from);
                            $route_to   = str_replace($parameter, '$' . ($index + 1), $route_to);
                        }

                        $route_from = '/^' . str_replace('/', '\/', $route_from) . '$/u';

                        $routes[$route_from] = $route_to;
                    }
                }
            }

            // serialize routes

            $routes = serialize($routes);

            // write cache

            $cache->write($cache_id, $routes);

            // get cache data

            $cache_data = $cache->check($cache_id);
        }

        return unserialize($cache_data['data']);
    }
}
