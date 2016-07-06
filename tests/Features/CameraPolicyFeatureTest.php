<?php

use Flaxandteal\Wso2emm\Features\CameraPolicyFeature;

class CameraPolicyFeatureTest extends TestCase
{
    public function testEnable() {
        $policy = new CameraPolicyFeature;
        $policy->enable();
        $this->assertTrue(
            $policy->getEnabled()
        );
    }

    public function testToJsonable() {
        $policy = new CameraPolicyFeature;
        $jsonable = $policy->toJsonable();
        $this->assertEquals(
            json_encode($jsonable),
            '{"featureCode":"CAMERA","deviceTypeId":1,"enabled":true}'
        );
    }
}
