<?php

declare(strict_types=1);
class JaguarConnect extends IPSModule
{
    private $token_url = 'https://ifas.prod-row.jlrmotor.com/ifas/jlr/tokens';
    private $base_url = 'https://ifop.prod-row.jlrmotor.com/ifop/jlr/';
    private $IF9_base_url = 'https://if9.prod-row.jlrmotor.com/if9/jlr';

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyString('Vehicles', '');
        $this->RegisterAttributeString('DeviceID', $this->get_guid());

        $this->RegisterAttributeString('access_token', '');
        $this->RegisterAttributeString('authorization_token', '');
        $this->RegisterAttributeString('refresh_token', '');
        $this->RegisterAttributeString('TokenExpires', '');
        $this->RegisterAttributeInteger('TokenExpiresTime', '');
        $this->RegisterAttributeString('UserID', '');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        //$Form = [];

        $Username = $this->ReadPropertyString('Username');
        $Password = $this->ReadPropertyString('Password');
        $accessToken = $this->ReadAttributeString('access_token');
        $tokenExpiresTime = $this->ReadAttributeInteger('TokenExpiresTime');

        $FormElementCount = 2;

        $this->authRequest();
        $this->deviceRegistration();
        $this->loginUser();

        $Vehicles = $this->getVehicles()['vehicles'];
        if (count($Vehicles) > 0) {
            $Form['elements'][$FormElementCount]['type'] = 'Select';
            $Form['elements'][$FormElementCount]['name'] = 'Vehicles';
            $Form['elements'][$FormElementCount]['caption'] = 'Vehicles';
            $selectOptions[0]['caption'] = $this->Translate('Please select a car!');
            $selectOptions[0]['value'] = '0';
            $optionsElementCount = 1;
            foreach ($Vehicles as $Vehicle) {
                $selectOptions[$optionsElementCount]['caption'] = $Vehicle['vin'];
                $selectOptions[$optionsElementCount]['value'] = $Vehicle['vin'];
                $optionsElementCount++;
            }
            $Form['elements'][$FormElementCount]['options'] = $selectOptions;
        }

        return json_encode($Form);
    }

    public function getVehicles()
    {
        $accessToken = $this->ReadAttributeString('access_token');
        $userID = $this->ReadAttributeString('UserID');
        $header = [
            'Content-Type: application/json',
            'X-Device-Id: ' . $this->ReadAttributeString('DeviceID'),
            'Authorization: Bearer ' . $accessToken,
        ];
        $url = $this->IF9_base_url . '/users/' . $userID . '/vehicles?primaryOnly=true';

        return $this->getRequest($url, $header);
    }

    public function ForwardData($JSONString)
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);

        switch ($data->Buffer->Command) {
            case 'getVersionV3':
                $result = $this->getVehicleStatus(3);
                break;
            case 'getVersion':
                $result = $this->getVehicleStatus(0);
                break;
            default:
            $this->SendDebug(__FUNCTION__, $data->Buffer->Command, 0);
            break;
        }
        $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        return json_encode($result);
    }

    public function getVehicleStatus($version)
    {
        $accessToken = $this->ReadAttributeString('access_token');
        $vin = $this->ReadPropertyString('Vehicles');
        $header = [
            'Content-Type: application/json',
            'X-Device-Id: ' . $this->ReadAttributeString('DeviceID'),
            'Authorization: Bearer ' . $accessToken,
        ];
        if ($version == 3) {
            $url = $this->IF9_base_url . '/vehicles/' . $vin . '/status?includeInactive=true';
            array_push($header, 'Accept: application/vnd.ngtp.org.if9.healthstatus-v3+json');
        } else {
            $url = $this->IF9_base_url . '/vehicles/' . $vin . '/status';
            array_push($header, 'Accept: application/vnd.ngtp.org.if9.healthstatus-v2+json');
        }
        return $this->getRequest($url, $header);
    }

    public function refreshToken()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic YXM6YXNwYXNz',
            'X-Device-Id: ' . $this->ReadAttributeString('DeviceID'),
            'Connection: close',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'grant_type'       => 'refresh_token',
            'refresh_token'    => $this->ReadAttributeString('refresh_token'),
            'username'         => $this->ReadPropertyString('Username'),
        ]));

        $apiResult = curl_exec($ch);

        $this->SendDebug(__FUNCTION__ . ' API Result', $apiResult, 0);

        if ($apiResult === false) {
            die('Curl-Fehler: ' . curl_error($ch));
        }
        $apiResultJson = json_decode($apiResult, true);
        curl_close($ch);

        if (!array_key_exists('access_token', $apiResultJson) || !array_key_exists('expires_in', $apiResultJson) || $apiResultJson === null) {
            $this->SendDebug(__FUNCTION__, 'Invalid response while fetching access token!', 0);
            return false;
        }

        $access_token = $apiResultJson['access_token'];
        $TokenExpires = $apiResultJson['expires_in'];
        $TokenExpiresTime = time() + $apiResultJson['expires_in'];

        $this->WriteAttributeInteger('TokenExpiresTime', $TokenExpiresTime);
        $this->WriteAttributeString('TokenExpires', $TokenExpires);
        $this->WriteAttributeString('access_token', $access_token);

        return true;
    }

    public function leereAccessToken()
    {
        $this->WriteAttributeString('access_token', '');
    }

    private function authRequest()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->token_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic YXM6YXNwYXNz',
            'X-Device-Id: ' . $this->ReadAttributeString('DeviceID'),
            'Connection: close',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'grant_type'       => 'password',
            'password'         => $this->ReadPropertyString('Password'),
            'username'         => $this->ReadPropertyString('Username'),
        ]));

        $apiResult = curl_exec($ch);

        $this->SendDebug(__FUNCTION__ . ' API Result', $apiResult, 0);

        if ($apiResult === false) {
            die('Curl-Fehler: ' . curl_error($ch));
        }
        $apiResultJson = json_decode($apiResult, true);
        curl_close($ch);

        if (!array_key_exists('access_token', $apiResultJson) || !array_key_exists('expires_in', $apiResultJson) || $apiResultJson === null) {
            $this->SendDebug(__FUNCTION__, 'Invalid response while fetching access token!', 0);
            return false;
        }

        $access_token = $apiResultJson['access_token'];
        $authorization_token = $apiResultJson['authorization_token'];
        $refresh_token = $apiResultJson['refresh_token'];
        $TokenExpires = $apiResultJson['expires_in'];
        $TokenExpiresTime = time() + $apiResultJson['expires_in'];

        $this->WriteAttributeString('access_token', $access_token);
        $this->WriteAttributeString('authorization_token', $authorization_token);
        $this->WriteAttributeString('refresh_token', $refresh_token);
        $this->WriteAttributeString('TokenExpires', $TokenExpires);
        $this->WriteAttributeInteger('TokenExpiresTime', $TokenExpiresTime);

        return true;
    }

    private function deviceRegistration()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->base_url . '/users/' . $this->ReadPropertyString('Username') . '/clients');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-Device-Id: ' . $this->ReadAttributeString('DeviceID'),
            'Content-Type: application/json',
            'Connection: close',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'access_token'           => $this->ReadAttributeString('access_token'),
            'authorization_token'    => $this->ReadAttributeString('authorization_token'),
            'expires_in'             => $this->ReadAttributeString('TokenExpires'),
            'deviceID'               => $this->ReadAttributeString('DeviceID'),
        ]));

        $apiResult = curl_exec($ch);
        $this->LogMessage($apiResult, KL_NOTIFY);
        $this->SendDebug(__FUNCTION__ . ' API Result', $apiResult, 0);
        curl_close($ch);

        return;
    }

    private function getRequest($url, $header)
    {
        $accessToken = $this->ReadAttributeString('access_token');
        $tokenExpiresTime = $this->ReadAttributeInteger('TokenExpiresTime');

        $this->LogMessage($url, KL_NOTIFY);
        $this->LogMessage(print_r($header, true), KL_NOTIFY);

        if (($accessToken == '') || (time() >= intval($tokenExpiresTime - 3600))) { // Eine Stunde bevor der Token abläuft soll dieser erneuert werden.
            $this->refreshToken();
            $this->LogMessage('Token expired, refresh Token', KL_NOTIFY);
            $accessToken = $this->ReadAttributeString('access_token');
        } elseif (($accessToken == '') || (time() >= intval($tokenExpiresTime))) {
            $this->LogMessage($this->Translate('Token Refresh with authRequest'), KL_NOTIFY);
            $this->authRequest();
            $this->deviceRegistration();
            $this->loginUser();
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $apiResult = curl_exec($ch);
        $this->LogMessage($apiResult, KL_NOTIFY);
        $this->SendDebug(__FUNCTION__ . ' API Result', $apiResult, 0);

        $apiResultJson = json_decode($apiResult, true);
        return $apiResultJson;
    }

    private function loginUser()
    {
        $accessToken = $this->ReadAttributeString('access_token');
        $url = $this->IF9_base_url . '/users?loginName=' . $this->ReadPropertyString('Username');

        $header = [
            'Accept: application/vnd.wirelesscar.ngtp.if9.User-v3+json',
            'Content-Type: application/json',
            'X-Device-Id: ' . $this->ReadAttributeString('DeviceID'),
            'Authorization: Bearer ' . $accessToken,
        ];

        $result = $this->getRequest($url, $header);

        $this->WriteAttributeString('UserID', $result['userId']);
        return;
    }

    private function get_guid()
    {
        $data = PHP_MAJOR_VERSION < 7 ? openssl_random_pseudo_bytes(16) : random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // Set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // Set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}