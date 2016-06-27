<?php

use Flaxandteal\Wso2emm\Wso2emmService;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;

class Wso2emmServiceTest extends TestCase
{
    private $client;

    public static function clientFromResponses($responses) {
        $mock = new MockHandler($responses);
        
        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        return $client;
    }

    public function testAddAppBlacklist() {
        $responses = [
            new Response(200)
        ];
        $wso2emm = new Wso2emmService();
        $wso2emm->client = self::clientFromResponses($responses);
        $wso2emm->addAppBlackList();
    }

    public static function makeResponse($content) {
        $responseBody = new stdClass;
        $responseBody->responseContent = $content;
        return new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode($responseBody)
        );
    }

    public function testGetPolicyDetails() {
        $responses = [
            self::makeResponse([])
        ];
        $wso2emm = new Wso2emmService();
        $wso2emm->client = self::clientFromResponses($responses);
        $wso2emm->getPolicyDetails(1);
    }

    public function testUpdatePolicyDetails() {
        $responses = [
            self::makeResponse([])
        ];
        $wso2emm = new Wso2emmService();
        $wso2emm->client = self::clientFromResponses($responses);
        $policy = self::makePolicy();
        $policy->id = 1;
        $wso2emm->updatePolicyDetails($policy);
    }

    public function testSetCameraAllowed() {
        $responses = [
            new Response(200) /*,
            new Response(202, ['Content-Length' => 0]),
            new RequestException("Error communicating", new Request('GET', 'test')) */
        ];
        $wso2emm = new Wso2emmService();
        $wso2emm->client = self::clientFromResponses($responses);
        $policy = self::makePolicy();
        $wso2emm->setCameraAllowed($policy, true);
    }

    public static function makePolicy() {
        $policy = new stdClass;
        $policy->profile = new stdClass;
        $policy->profile->profileFeaturesList = [];
        $policy->profile->profileFeaturesList[] = new stdClass;
        $policy->profile->profileFeaturesList[0]->content = "{enabled: true}";

        return $policy;
    }

    public function testActivatePolicies() {
        $responses = [
            new Response(200)
        ];
        $wso2emm = new Wso2emmService();
        $wso2emm->client = self::clientFromResponses($responses);
        $wso2emm->activatePolicies([1]);
    }

    public function testInactivatePolicies() {
        $responses = [
            new Response(200)
        ];
        $wso2emm = new Wso2emmService();
        $wso2emm->client = self::clientFromResponses($responses);
        $wso2emm->inactivatePolicies([1]);
    }

    public function testApplyPolicyChanges() {
        $responses = [
            new Response(200)
        ];
        $wso2emm = new Wso2emmService();
        $wso2emm->client = self::clientFromResponses($responses);
        $wso2emm->applyPolicyChanges();
    }
}
