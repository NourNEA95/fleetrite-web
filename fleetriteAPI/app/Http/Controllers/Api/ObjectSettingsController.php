<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GsObject;
use App\Models\GsProfile;
use App\Models\GsUserObject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObjectSettingsController extends Controller
{
    /**
     * Get all data required for the Object Edit settings dialog.
     */
    private function convUserTimezone($dt)
    {
        if ($dt == '0000-00-00 00:00:00') {
            return '';
        }

        // Simulating the legacy system's timezone conversion
        // In reality, this should use the user's timezone settings
        return $dt;
    }

    private function getParamsArray($params)
    {
        if ($params == '') {
            return [];
        }

        $params = json_decode($params, true);
        if (!$params) {
            return [];
        }

        $formatted_params = [];
        foreach ($params as $key => $value) {
            $formatted_params[] = $key . '=' . $value;
        }

        return implode(', ', $formatted_params);
    }

    public function getObjectSettings(Request $request, $imei)
    {
        // 1. Get the main object data with associated profile and user link
        $object = GsObject::with(['profile', 'userObject' => function($query) {
            $query->where('user_id', auth()->id());
        }])->where('imei', $imei)->first();

        if (!$object) {
            return response()->json(['error' => 'Object not found'], 404);
        }

        // 2. Get Speed Limit data from gs_object_speed_limit table
        $speedLimit = DB::table('gs_object_speed_limit')->where('imei', $imei)->first();

        // 3. Get Ignore Speed data (Accuracy Car Type) from ignore_speed_type_imei table
        $ignoreSpeed = DB::table('ignore_speed_type_imei')->where('imei', $imei)->first();

        // 4. Get Sensors
        $sensors = DB::table('gs_object_sensors')->where('imei', $imei)->orderBy('name', 'asc')->get()->map(function($row) {
            return [
                'id' => (string) $row->sensor_id,
                'name' => $row->name,
                'type' => $row->type,
                'parameter' => $row->param,
                'data_list' => $row->data_list,
                'popup' => $row->popup,
                'result_type' => $row->result_type,
                'text_1' => $row->text_1,
                'text_0' => $row->text_0,
                'units' => $row->units,
                'lv' => $row->lv,
                'hv' => $row->hv,
                'acc_ignore' => $row->acc_ignore,
                'formula' => $row->formula,
                'calibration' => is_string($row->calibration) ? json_decode($row->calibration, true) : null,
                'dictionary' => is_string($row->dictionary) ? json_decode($row->dictionary, true) : null,
            ];
        });

        // 5. Get Services
        $services = DB::table('gs_object_services')->where('imei', $imei)->orderBy('name', 'asc')->get()->map(function($row) {
            return [
                'id' => (string) $row->service_id,
                'name' => $row->name,
                'data_list' => $row->data_list,
                'popup' => $row->popup,
                'odo' => $row->odo,
                'odo_interval' => (float) $row->odo_interval,
                'odo_last' => (float) $row->odo_last,
                'engh' => $row->engh,
                'engh_interval' => (float) $row->engh_interval,
                'engh_last' => (float) $row->engh_last,
                'days' => $row->days,
                'days_interval' => (int) $row->days_interval,
                'days_last' => $row->days_last,
                'odo_left' => $row->odo_left,
                'odo_left_num' => (float) $row->odo_left_num,
                'engh_left' => $row->engh_left,
                'engh_left_num' => (float) $row->engh_left_num,
                'days_left' => $row->days_left,
                'days_left_num' => (int) $row->days_left_num,
                'update_last' => $row->update_last
            ];
        });

        // 6. Get Custom Fields
        $customFields = DB::table('gs_object_custom_fields')->where('imei', $imei)->orderBy('name', 'asc')->get()->map(function($row) {
            return [
                'id' => (string) $row->field_id,
                'name' => $row->name,
                'value' => $row->value,
                'data_list' => $row->data_list,
                'popup' => $row->popup,
            ];
        });

        // 7. Get User Lists (Groups, Drivers, Trailers)
        $userId = $object->userObject->user_id ?? 0;
        $userGroups = DB::table('gs_user_object_groups')->where('user_id', $userId)->orderBy('group_name', 'asc')->get();
        $userDrivers = DB::table('gs_user_object_drivers')->where('user_id', $userId)->orderBy('driver_name', 'asc')->get();
        $userTrailers = DB::table('gs_user_object_trailers')->where('user_id', $userId)->orderBy('trailer_name', 'asc')->get();

        // 8. Format the response to be clean, organized, and descriptive
        return response()->json([
            'success' => true,
            'data' => [
                'imei' => $object->imei,
                'name' => $object->name,
                'device' => $object->device,
                'sim_number' => $object->sim_number,
                'model' => $object->model, // Device/Hardware model
                'vin' => $object->vin,
                'plate_number' => $object->plate_number,
                
                // User-specific relation fields (Driver, Group)
                'user_link' => [
                    'group_id' => (int) ($object->userObject->group_id ?? 0),
                    'driver_id' => (int) ($object->userObject->driver_id ?? 0),
                    'trailer_id' => (int) ($object->userObject->trailer_id ?? 0),
                ],

                // Profiles lists for selection
                'lists' => [
                    'groups' => $userGroups,
                    'drivers' => $userDrivers,
                    'trailers' => $userTrailers,
                ],

                // Profile data (Physical Vehicle Details)
                'profile' => [
                    'vehicle_type' => $object->profile->type ?? '',
                    'brand' => $object->profile->brand ?? '',
                    'model' => $object->profile->model ?? '', // Vehicle brand model
                    'vin' => $object->profile->vin ?? '',
                    'year' => (int) ($object->profile->year ?? 0),
                    'color' => $object->profile->color ?? '',
                    'insurance_ex_day' => $object->profile->ex_day ?? '',
                    'ignore_updates_stop' => $object->profile->stop ?? 'no',
                ],

                // Technical Configuration
                'config' => [
                    'odometer_type' => $object->odometer_type ?: 'gps',
                    'engine_hours_type' => $object->engine_hours_type ?: 'acc',
                    'odometer' => (int) round($object->odometer),
                    'engine_hours' => (float) round($object->engine_hours / 3600, 2), // Convert seconds to hours
                    'time_adj' => $object->time_adj,
                    'fcr' => is_string($object->fcr) ? json_decode($object->fcr, true) : $object->fcr,
                    'map_arrows' => is_string($object->map_arrows) ? json_decode($object->map_arrows, true) : $object->map_arrows,
                    'map_icon' => $object->map_icon,
                    'tail_color' => $object->tail_color,
                    'tail_points' => (int) $object->tail_points,
                    'accuracy' => is_string($object->accuracy) ? json_decode($object->accuracy, true) : $object->accuracy,
                    'protocol' => $object->protocol,
                ],

                // Speed & Accuracy Settings
                'performance' => [
                    'speed_limit' => $speedLimit ? (int) $speedLimit->speed_limit : null,
                    'speed_limit_duration' => $speedLimit ? (int) $speedLimit->speed_limit_duration : null,
                    'ignore_after_speed' => $ignoreSpeed ? (int) $ignoreSpeed->speed : (int) $object->ignore_after_speed,
                    'accuracy_car_type' => $ignoreSpeed ? $ignoreSpeed->device_type : '',
                ],

                // Lists
                'sensors' => $sensors,
                'services' => $services,
                'custom_fields' => $customFields,
                
                // Live metadata for Info tab
                'info' => [
                    'altitude' => ($object->altitude ?: '0') . ' m',
                    'angle' => ($object->angle ?: '0') . ' °',
                    'lat' => sprintf('%0.6f', $object->lat) . ' °',
                    'lng' => sprintf('%0.6f', $object->lng) . ' °',
                    'params' => $this->getParamsArray($object->params),
                    'protocol' => $object->protocol,
                    'speed' => round($object->speed) . ' km/h',
                    'dt_tracker' => $this->convUserTimezone($object->dt_tracker),
                    'dt_server' => $this->convUserTimezone($object->dt_server),
                ]
            ]
        ]);
    }

    /**
     * Update object settings (implements the save functionality).
     */
    public function updateObjectSettings(Request $request, $imei)
    {
        $user_id = auth()->id();
        $data = $request->all();

        DB::beginTransaction();
        try {
            // 1. Update User-Object relationship (Group, Driver)
            GsUserObject::where('user_id', $user_id)
                ->where('imei', $imei)
                ->update([
                    'group_id' => $data['user_link']['group_id'] ?? 0,
                    'driver_id' => $data['user_link']['driver_id'] ?? 0,
                    'trailer_id' => $data['user_link']['trailer_id'] ?? 0,
                ]);

            // 2. Update Basic Object Data
            GsObject::where('imei', $imei)->update([
                'name' => $data['name'],
                'device' => $data['device'] ?? '',
                'sim_number' => $data['sim_number'] ?? '',
                'model' => $data['model'] ?? '',
                'vin' => $data['vin'] ?? '',
                'plate_number' => $data['plate_number'] ?? '',
                'map_arrows' => isset($data['config']['map_arrows']) ? json_encode($data['config']['map_arrows']) : null,
                'map_icon' => $data['config']['map_icon'] ?? '',
                'tail_color' => $data['config']['tail_color'] ?? '',
                'tail_points' => $data['config']['tail_points'] ?? 0,
                'fcr' => isset($data['config']['fcr']) ? json_encode($data['config']['fcr']) : null,
                'accuracy' => isset($data['config']['accuracy']) ? json_encode($data['config']['accuracy']) : null,
                'odometer_type' => $data['config']['odometer_type'] ?? 'gps',
                'engine_hours_type' => $data['config']['engine_hours_type'] ?? 'acc',
                'odometer' => $data['config']['odometer'] ?? 0,
                'engine_hours' => $data['config']['engine_hours'] ?? 0,
                'time_adj' => $data['config']['time_adj'] ?? '',
            ]);

            // 3. Update Profile (Vehicle Details)
            GsProfile::updateOrCreate(
                ['imei' => $imei],
                [
                    'type' => $data['profile']['vehicle_type'] ?? '',
                    'brand' => $data['profile']['brand'] ?? '',
                    'model' => $data['profile']['model'] ?? '',
                    'vin' => $data['profile']['vin'] ?? '',
                    'year' => $data['profile']['year'] ?? 0,
                    'color' => $data['profile']['color'] ?? '',
                    'ex_day' => $data['profile']['insurance_ex_day'] ?? '',
                    'stop' => $data['profile']['ignore_updates_stop'] ?? 'no',
                ]
            );

            // 4. Update Speed Limits
            DB::table('gs_object_speed_limit')->updateOrInsert(
                ['imei' => $imei],
                [
                    'speed_limit' => $data['performance']['speed_limit'] ?? 0,
                    'speed_limit_duration' => $data['performance']['speed_limit_duration'] ?? 0,
                ]
            );

            // 5. Update Ignore Speed Settings
            DB::table('ignore_speed_type_imei')->updateOrInsert(
                ['imei' => $imei],
                [
                    'device_type' => $data['performance']['accuracy_car_type'] ?? '',
                    'speed' => $data['performance']['ignore_after_speed'] ?? 0,
                ]
            );

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Settings updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
