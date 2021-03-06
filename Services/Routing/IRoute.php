<?php

namespace Services\Routing;

interface IRoute
{
    public function matches($uri);
    public function supports($method);
    public function getControllerName();
    public function getActionName($method);
    public function getActionArgs();
}
