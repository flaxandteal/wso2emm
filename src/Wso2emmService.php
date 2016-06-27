<?php namespace Flaxandteal\Wso2emm;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use CommerceGuys\Guzzle\Oauth2\GrantType\RefreshToken;
use CommerceGuys\Guzzle\Oauth2\GrantType\PasswordCredentials;
use CommerceGuys\Guzzle\Oauth2\Middleware\OAuthMiddleware;

class Wso2emmService
{
    public $debug = false;

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
        $this->client = self::configureClient();
    }

    public function addAppBlacklist() {
        $testPolicy =  [  
           "policyName" => "black list",
           "description" => "",
           "compliance" => "enforce",
           "ownershipType" => "ANY",
           "profile" => [  
              "profileName" => "black list",
              "deviceType" => [  
                 "id" => 1
              ],
              "profileFeaturesList" => [  
                 [  
                    "featureCode" => "APP-RESTRICTION",
                    "deviceTypeId" => 1,
                    "content" => [  
                       "restriction-type" => "black-list",
                       "restricted-applications" => [  
                          [  
                             "appName" => "app name1",
                             "packageName" => "package1"
                          ],
                          [  
                             "appName" => "app name2",
                             "packageName" => "package2"
                          ],
                          [  
                             "appName" => "app name3",
                             "packageName" => "package3"
                          ]
                       ]
                    ]
                 ]
              ]
           ],
           "roles" => [  
              "ANY"
           ]
        ];

        $response = $this->client->post('/mdm-admin/policies/active-policy', ['json' => $testPolicy]);
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);
    }

    public function getPolicyDetails($policyId) {
        $response = $this->client->get('/mdm-admin/policies/' . $policyId);
        $responseBody = json_decode($response->getBody());
        if ($this->debug) print_r($responseBody);
        return $responseBody->responseContent;
    }

    public function updatePolicyDetails($policy) {
        $response = $this->client->put('/mdm-admin/policies/' . $policy->id, ['json' => $policy]);
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

