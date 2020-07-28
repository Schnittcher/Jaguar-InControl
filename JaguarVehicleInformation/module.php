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

        $this->RegisterProfileBooleanEx('JIC.Position', 'Lock', '', '', [
            [false, 'closed',  '', 0xFF0000],
            [true, 'open',  '', 0x00FF00]
        ]);

        $this->RegisterProfileBooleanEx('JIC.Lock', 'Lock', '', '', [
            [false, 'locked',  '', 0xFF0000],
            [true, 'unlocked',  '', 0x00FF00]
        ]);

        $this->RegisterProfileFloat('JIC.Pressure', 'Car', '', ' bar', 0, 0, 0.1, 2);

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

        $this->RegisterVariableBoolean('DOOR_IS_ALL_DOORS_LOCKED', $this->Translate('All Doors locked'), 'JIC.Lock');
        $this->RegisterVariableBoolean('DOOR_FRONT_LEFT_POSITION',$this->Translate('Door front left'), 'JIC.Position');
        $this->RegisterVariableBoolean('DOOR_REAR_RIGHT_POSITION',$this->Translate('Door rear left'), 'JIC.Position');
        $this->RegisterVariableString('TYRE_STATUS_REAR_LEFT',$this->Translate('Tyre status rear left'), '');
        $this->RegisterVariableBoolean('DOOR_REAR_RIGHT_LOCK_STATUS',$this->Translate('Door rear right status'), 'JIC.Lock');
        $this->RegisterVariableBoolean('DOOR_FRONT_RIGHT_POSITION',$this->Translate('Door front right'), 'JIC.Position');
        $this->RegisterVariableBoolean('DOOR_REAR_LEFT_LOCK_STATUS',$this->Translate('Door rear left status'), 'JIC.Lock');
        $this->RegisterVariableBoolean('DOOR_ENGINE_HOOD_POSITION',$this->Translate('Door Engine hood'), 'JIC.Position');
        $this->RegisterVariablestring('TYRE_STATUS_FRONT_LEFT',$this->Translate('Tyre status front left'), '');
        $this->RegisterVariableBoolean('THEFT_ALARM_STATUS',$this->Translate('Alarm status'), '~Alert');
        $this->RegisterVariableFloat('EXT_KILOMETERS_TO_SERVICE',$this->Translate('Kilometers until Service'), 'JIC.Range');
        $this->RegisterVariableString('VEHICLE_STATE_TYPE',$this->Translate('Vehicle state'), '');
        $this->RegisterVariableBoolean('DOOR_ENGINE_HOOD_LOCK_STATUS',$this->Translate('Door engine hood status'), 'JIC.Lock');
        $this->RegisterVariableString('TYRE_STATUS_FRONT_RIGHT',$this->Translate('Tyre status front right'), '');
        $this->RegisterVariableBoolean('DOOR_FRONT_RIGHT_LOCK_STATUS',$this->Translate('Door front right status'), 'JIC.Lock');
        $this->RegisterVariableFloat('TYRE_PRESSURE_REAR_RIGHT',$this->Translate('Tyre Pressure rear right'), 'JIC.Pressure');
        $this->RegisterVariableBoolean('DOOR_FRONT_LEFT_LOCK_STATUS',$this->Translate('Door front left status'), 'JIC.Lock');
        $this->RegisterVariableBoolean('WINDOW_REAR_LEFT_STATUS',$this->Translate('Window rear left'), 'JIC.Position');
        $this->RegisterVariableFloat('TYRE_PRESSURE_FRONT_RIGHT',$this->Translate('Tyre Pressure front right'), 'JIC.Pressure');
        $this->RegisterVariableBoolean('DOOR_BOOT_POSITION',$this->Translate('Trunk'), 'JIC.Position');
        $this->RegisterVariableFloat('TYRE_PRESSURE_FRONT_LEFT',$this->Translate('Tyre Pressure front left'), 'JIC.Pressure');
        $this->RegisterVariableFloat('ODOMETER_METER',$this->Translate('Mileage'), 'JIC.Range');
        $this->RegisterVariableFloat('BATTERY_VOLTAGE',$this->Translate('Battery voltage'), '~Volt');
        $this->RegisterVariableFloat('TYRE_PRESSURE_REAR_LEFT',$this->Translate('Tyre Pressure rear left'), 'JIC.Pressure');
        $this->RegisterVariableBoolean('WINDOW_FRONT_LEFT_STATUS',$this->Translate('Window front left'), 'JIC.Position');
        $this->RegisterVariableString('TYRE_STATUS_REAR_RIGHT',$this->Translate('Tyre status rear right'), '');
        $this->RegisterVariableBoolean('DOOR_BOOT_LOCK_STATUS',$this->Translate('Trunk Status'), 'JIC.Lock');
 
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
        foreach ($Data['vehicleStatus']['coreStatus'] as $Value) {
            if (@$this->GetIDForIdent($Value['key']) != false) {

                switch ($Value['key']) {
                    case 'TYRE_PRESSURE_REAR_RIGHT':
                    case 'TYRE_PRESSURE_FRONT_RIGHT':
                    case 'TYRE_PRESSURE_FRONT_LEFT':
                    case 'TYRE_PRESSURE_REAR_LEFT':
                        $this->SetValue($Value['key'], $Value['value'] / 100);
                        break;
                    case 'ODOMETER_METER':
                        $this->SetValue($Value['key'], $Value['value'] / 1000);
                        break;
                    default:
                        $this->SetValue($Value['key'], $Value['value']);
                        break;
                }
            } else {
                $this->SendDebug('Variable not exist', 'Key: ' . $Value['key'] . ' - Value: ' . $Value['value'], 0);
            }
        }
    }
}
