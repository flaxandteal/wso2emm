<?php namespace Flaxandteal\Wso2emm;

class Policy
{
    public function __construct() {
        $this->profile = new Profile;
    }

    public function setReference($reference) {
        $this->profile->reference = $reference;
    }

    public static function create() {
        return new self;
    }

    public function toJsonable($deviceTypeId=1) {
        return [
            'profile' => $this->profile->toJsonable($deviceTypeId),
            'policyName' => $this->getName() . '::' . $deviceTypeId,
            'compliance' => 'enforce',
            'ownershipType' => 'ANY',
            'description' => '',
            'roles' => ['ANY'],
            'users' => ['ANY']
        ];
    }

    public function getName() {
        return $this->profile->getName();
    }
}
