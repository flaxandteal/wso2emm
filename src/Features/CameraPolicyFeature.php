<?php namespace Flaxandteal\Wso2emm\Features;

use Flaxandteal\Wso2emm\Profile;
use Flaxandteal\Wso2emm\Feature;
use Flaxandteal\Wso2emm\FeatureInterface;

class CameraPolicyFeature extends Feature implements FeatureInterface
{
    /* Note this refers to the Feature not the camera */
    public $enabled = false;

    public function getEnabled() {
        return $this->enabled;
    }

    public function disable() {
        $this->enabled = false;
    }

    public function enable() {
        $this->enabled = true;
    }

    public function toJsonable($deviceId) {
        return [
            'featureCode' => "CAMERA",
            'deviceTypeId' => $deviceId,
            'content' => [
                'enabled' => !$this->enabled
            ]
        ];
    }
}
