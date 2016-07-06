<?php

use Flaxandteal\Wso2emm\Policy;

class PolicyTest extends TestCase
{
    public function testToJsonable() {
        $policy = new Policy;
        $jsonable = $policy->toJsonable();
        $this->assertEquals(
            json_encode($jsonable),
            '{"profile":{"profileFeaturesList":[{"featureCode":"CAMERA","deviceTypeId":1,"enabled":true}],"profileName":"Xero-#?","deviceType":{"id":1}},"policyName":"Xero-#?","compliance":"enforce","ownershipType":"ANY","users":[],"roles":[]}'
        );
    }
}
