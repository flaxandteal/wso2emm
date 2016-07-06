<?php namespace Flaxandteal\Wso2emm;

class Profile
{
    const DEVICE_TYPES = 3;

    const DEVICE_ANDROID = 1;
    const DEVICE_WINDOWS = 2;
    const DEVICE_IOS = 3;

    public $profileFeaturesList = [];

    public function __construct($reference=0)
    {
        $this->profileFeaturesList['camera-restrict'] = new Features\CameraPolicyFeature;
        $this->reference = $reference;
    }

    public function getFeature($identifier)
    {
        return $this->profileFeaturesList[$identifier];
    }

    public function getName()
    {
        return 'Xero - #' . $this->reference;
    }

    public function toJsonable($deviceId=self::DEVICE_ANDROID)
    {
	$featuresList = array_filter($this->profileFeaturesList, function ($f) use ($deviceId) {
	    return $f->activeFor($deviceId);
        });

        return [
            'profileFeaturesList' => array_map(function ($f) use ($deviceId) {
                return $f->toJsonable($deviceId);
            }, array_values($featuresList)),
            'profileName' => $this->getName() . '::' . $deviceId,
            'deviceType' => ['id' => $deviceId]
        ];
    }
}
