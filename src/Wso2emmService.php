<?php namespace Flaxandteal\Wso2emm;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use CommerceGuys\Guzzle\Oauth2\Middleware\OAuthMiddleware;

class Wso2emmService
{
    public $debug = false;
    public $client = null;
    public $allPolicyDetails = null;

    public static function configureClient() {
        $verify_certificate = false;
        $wso2emm_host = getenv('WSO2EMM_PORT_9443_TCP_ADDR') ?: 'localhost';
        $wso2emm_port = getenv('WSO2EMM_PORT_9443_TCP_PORT') ?: '9443';
        $wso2emm_uri = 'https://' . $wso2emm_host . ':' . $wso2emm_port;

        $handlerStack = HandlerStack::create();

        $client = new Client([
            'base_uri' => $wso2emm_uri,
            'auth' => 'oauth2',
            'handler' => $handlerStack,
            'timeout'  => 2.0,
            'verify' => false
        ]);

        $grantType = ['refresh_token', 'password', 'client_credentials'];
        $body = [
            'owner' => 'admin',
            'clientName' => 'admin_emm',
            'grantType' => implode($grantType, ' '),
            'tokenScope' => 'prod'
        ];
        $response = $client->post('/dynamic-client-web/register', ['json' => $body]);

        $responseBody = json_decode($response->getBody(), true);

        /*
        $authToken = $responseBody['client_id'] . ':' . $responseBody['client_secret'];
        */

        $authParams = [
            PasswordCredentials::CONFIG_USERNAME => 'admin',
            PasswordCredentials::CONFIG_PASSWORD => 'admin',
            PasswordCredentials::CONFIG_CLIENT_ID => $responseBody['client_id'],
            PasswordCredentials::CONFIG_CLIENT_SECRET => $responseBody['client_secret'],
            'scope' => 'default'
        ];

        $token = new PasswordCredentials($client, $authParams);
        $refreshToken = new RefreshToken($client, $authParams);
        $middleware = new OAuthMiddleware($client, $token, $refreshToken);

        $handlerStack->push($middleware->onBefore());
        $handlerStack->push($middleware->onFailure(5));

        return $client;
    }

    public function init() {
        if (!$this->client)
            $this->client = self::configureClient();
    }

    public function addAppBlacklist() {
        $testPolicy =  [  
           "profile" => [  
              "profileFeaturesList" => [  
                 [  
                    "featureCode" => "CAMERA",
                    "deviceTypeId" => 1,
                    "content" => [  
                        "enabled" => true
                    ]
                 ]
              ],
              "profileName" => "Xero",
              "deviceType" => [  
                 "id" => 1
              ],
           ],
           "policyName" => "Xero",
           "compliance" => "enforce",
           "ownershipType" => "ANY",
           "description" => "",
           "roles" => [  
              "ANY"
           ]
        ];

        $response = $this->client->post('/mdm-admin/policies/active-policy', ['json' => $testPolicy]);
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);
    }

    public function getPolicyObjectByName($policyName, $deviceTypeId) {
        $allPolicies = $this->getAllPolicyDetails();
        $policyName .= '::' . $deviceTypeId;
        $policies = array_values(array_filter(
          $allPolicies,
          function ($p) use ($policyName) { return $p->policyName == $policyName; }
        ));

        if (empty($policies))
         return null;

        $policy = $policies[0]; //FIXME: more efficient and check matches

        if ($this->debug) print_r($policy);
        return $policy;
    }

    public function getAllPolicyDetails() {
        if ($this->allPolicyDetails)
            return $this->allPolicyDetails;

        $response = $this->client->get('/mdm-admin/policies');
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);
        $content = $responseBody->responseContent;
        $this->allPolicyDetails = $content;
        $this->allPolicyIds = array_map(function ($policy) { return $policy->id; }, $content);
        return $content;
    }

    public function getPolicyDetails($policyId) {
        $response = $this->client->get('/mdm-admin/policies/' . $policyId);
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);
        return $responseBody->responseContent;
    }

    public function removePolicy($policyId) {
        $this->allPolicyDetails = null;

        try {
            $response = $this->client->post('/mdm-admin/policies/bulk-remove', ['json' => [$policyId]]);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            \Log::warning('Failed remove policy');
            return false;
        }
        if ($this->debug) print_r($response->getBody());
        return true;
    }

    public function addPolicy($policy, $deviceTypeId=1) {
        $this->allPolicyDetails = null;
        // FIXME: horrendously inefficient way to check for clashes, but need more REST API info
        $policyName = $policy->getName();
        if (!$policyName || $this->getPolicyObjectByName($policyName, $deviceTypeId))
            throw new \Exception("Must have a unique WSO2 policy name - " . $policyName . "::" . $deviceTypeId . " is not");

        $policyObject = $policy->toJsonable($deviceTypeId);
        $response = $this->client->post('/mdm-admin/policies/active-policy', ['json' => $policyObject]);
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);

        $this->allPolicyDetails = null;
        $newPolicyObject = $this->getPolicyObjectByName($policyName, $deviceTypeId);

        return $newPolicyObject->id;
    }

    public function updatePolicy($policyId, $policy, $deviceTypeId) {
        $this->allPolicyDetails = null;
        $policyObject = $policy->toJsonable($deviceTypeId);
        $response = $this->client->put('/mdm-admin/policies/' . $policyId, ['json' => $policyObject]);
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);
    }

    public function setCameraAllowed($policy, $allowed) {
        $policy->profile->profileFeaturesList[0]->content = ['enabled' => $allowed];
    }

    public function activatePolicies($policyIds) {
        $response = $this->client->put('/mdm-admin/policies/activate', ['json' => $policyIds]);
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);
    }

    public function inactivatePolicies($policyIds) {
        $response = $this->client->put('/mdm-admin/policies/inactivate', ['json' => $policyIds]);
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);
    }

    public function applyPolicyChanges() {
        $response = $this->client->put('/mdm-admin/policies/apply-changes');
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);
    }

}

