<?php namespace Flaxandteal\Wso2emm;

interface FeatureInterface
{
    public function activeFor($deviceType);
    public function toJsonable($deviceType);
    public function disable();
    public function enable();
    public function getEnabled();
}
