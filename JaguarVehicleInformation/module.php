<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/VariableProfileHelper.php';
class JaguarVehicleInformation extends IPSModule
{
    use VariableProfileHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{687CF50C-9FD3-8194-E85D-25993298A1B6}');
        $this->RegisterPropertyInteger('Interval', 60);

        $this->RegisterTimer('JIC_UpdateVehicleInformation', 0, 'JIC_FetchData($_IPS[\'TARGET\']);');

        $this->RegisterProfileInteger('JIC.Minutes', 'Clock', '', ' min', 0, 0, 1);
        $this->RegisterProfileFloat('JIC.Range', 'Distance', '', ' km', 0, 0, 0.1, 2);
        $this->RegisterProfileFloat('JIC.ChargingRateKM', 'Distance', '', ' km/h', 0, 0, 0.1, 2);
        $this->RegisterProfileFloat('JIC.ChargingRateSOC', 'Distance', '', ' %', 0, 0, 0.1, 2);

        $this->RegisterVariableFloat('EV_RANGE_COMFORTx10', $this->Translate('Range in Comfort Mode'), 'JIC.Range');
        $this->RegisterVariableString('EV_CHARGING_STATUS', $this->Translate('Charging Status'), '');
        $this->RegisterVariableString('EV_IS_PLUGGED_IN', $this->Translate('Cable plugged in'), '');
        $this->RegisterVariableString('EV_CHARGING_METHOD', $this->Translate('Charging Method'), '');
        $this->RegisterVariableFloat('EV_CHARGING_RATE_KM_PER_HOUR', $this->Translate('Charging Rate KM'), 'JIC.ChargingRateKM');
        $this->RegisterVariableInteger('EV_STATE_OF_CHARGE', $this->Translate('SOC'), '~Battery.100');
        $this->RegisterVariableString('EV_IS_CHARGING', $this->Translate('Charging'), '');
        $this->RegisterVariableFloat('EV_RANGE_ON_BATTERY_KM', $this->Translate('Range'), 'JIC.Range');
        $this->RegisterVariableInteger('EV_MINUTES_TO_FULLY_CHARGED', $this->Translate('Time to 100% SOC'), 'JIC.Minutes');
        $this->RegisterVariableString('EV_CHARGE_TYPE', $this->Translate('Charge Type'), '');
        $this->RegisterVariableFloat('EV_CHARGING_RATE_SOC_PER_HOUR', $this->Translate('Charging Rate SOC'), 'JIC.ChargingRateSOC');
        $this->RegisterVariableFloat('EV_RANGE_ECOx10', $this->Translate('Range in Eco Mode'), 'JIC.Range');
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
        $this->SetTimerInterval('JIC_UpdateVehicleInformation', $this->ReadPropertyInteger('Interval') * 1000);
    }

    public function FetchData()
    {
        $Data['DataID'] = '{DC086781-C8F0-70A0-4E04-6E9BC6A07F11}';

        $Buffer['Command'] = 'getVersionV3';
        $Buffer['Params'] = '';

        $Data['Buffer'] = $Buffer;

        $Data = json_encode($Data);

        $Data = json_decode($this->SendDataToParent($Data), true);
        if (!$Data) {
            return false;
        }
        foreach ($Data['vehicleStatus']['evStatus'] as $Value) {
            if (@$this->GetIDForIdent($Value['key']) != false) {
                $this->SetValue($Value['key'], $Value['value']);
            } else {
                $this->SendDebug('Variable not exist', 'Key: ' . $Value['key'] . ' - Value: ' . $Value['value'], 0);
            }
        }
    }
}