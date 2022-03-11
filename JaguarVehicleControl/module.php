<?php

declare(strict_types=1);
class JaguarVehicleControl extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{687CF50C-9FD3-8194-E85D-25993298A1B6}');
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

    public function Send()
    {
        $this->SendDataToParent(json_encode(['DataID' => '{DC086781-C8F0-70A0-4E04-6E9BC6A07F11}']));
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        IPS_LogMessage('Device RECV', utf8_decode($data->Buffer));
    }
}