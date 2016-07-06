<?php namespace Flaxandteal\Wso2emm;

class Feature
{
    public $activeDevices = [Profile::DEVICE_ANDROID, Profile::DEVICE_WINDOWS, Profile::DEVICE_IOS];

    public function activeFor($deviceId) {
        return in_array($deviceId, $this->activeDevices);
    }

}
