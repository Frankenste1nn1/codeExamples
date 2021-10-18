<?php

namespace App\Repositories;

//This dependencies are using in current project, where this example were took from, and will not present in current repository
use App\Models\HotelSetting;
use App\Models\HotelSettingField;
use App;

class HotelSettingRepository
{
    public function index($options)
    {
        $hotelSetting = new HotelSetting;
        $search = $hotelSetting->searchable;

        if (!empty($options['key'])) {
            $hotelSetting = $hotelSetting->where('key', '=', $options['key']);
        }

        if (!empty($options['withHotel'])) {
            $hotelSetting = $hotelSetting->with('hotel');
        }

        if (!empty($options['keyword']) && !empty($search)) {
            $hotelSetting = $hotelSetting->where(function ($query) use ($search, $options) {
                $query->where(array_shift($search), 'ilike', '%' . $options['keyword'] . '%');

                while (!empty($search)) {
                    $query = $query->orWhere(array_shift($search), 'ilike', '%' . $options['keyword'] . '%');
                }
            });
        }

        if (!empty($options['hotelsOnly'])) {
            $hotelSetting = $hotelSetting->whereIn('hotel_id', $options['hotelsOnly']);
        }

        if (!empty($options['group'])) {
            $hotelSetting = $hotelSetting->where('group', $options['group']);
        }

        $hotelSetting = $hotelSetting->orderBy($options['sort'], $options['direction']);
        $hotelSetting = $hotelSetting
            ->paginate($options['perPage'], ['*'], 'page', $options['page'])
            ->setPath('/api/hotel_settings');

        return $hotelSetting;
    }

    public function all($options) {
        $hotelSetting = new HotelSetting;
        $search = $hotelSetting->searchable;

        if (!empty($options['key'])) {
            $hotelSetting = $hotelSetting->where('key', '=', $options['key']);
        }

        if (!empty($options['withHotel'])) {
            $hotelSetting = $hotelSetting->with('hotel');
        }

        if (!empty($options['keyword']) && !empty($search)) {
            $hotelSetting = $hotelSetting->where(function ($query) use ($search, $options) {
                $query->where(array_shift($search), 'ilike', '%' . $options['keyword'] . '%');

                while (!empty($search)) {
                    $query = $query->orWhere(array_shift($search), 'ilike', '%' . $options['keyword'] . '%');
                }
            });
        }

        if (!empty($options['hotelsOnly'])) {
            $hotelSetting = $hotelSetting->whereIn('hotel_id', $options['hotelsOnly']);
        }

        if (!empty($options['group'])) {
            $hotelSetting = $hotelSetting->where('group', $options['group']);
        }

        $hotelSetting = $hotelSetting->orderBy($options['sort'], $options['direction']);
        $hotelSetting = $hotelSetting->get();

        return $hotelSetting;
    }

    public function show($options, $id)
    {
        $hotelSetting = new HotelSetting;

        if (!empty($options['withHotel'])) {
            $hotelSetting = $hotelSetting->with('hotel');
        }

        if (!empty($options['hotelsOnly'])) {
            $hotelSetting = $hotelSetting->whereIn('hotel_id', $options['hotelsOnly']);
        }

        $hotelSetting = $hotelSetting->find($id);

        return $hotelSetting;
    }

    public function store($request)
    {
        $hotel_id = $request['hotel_id'];
        unset($request['hotel_id']);

        $settings = array();

        $hotelSettingField = new HotelSettingField;
        $fields = $hotelSettingField->fields();

        foreach ($request as $key => $value) {
            $setting = HotelSetting::updateOrCreate(['hotel_id' => $hotel_id, 'key' => $key], [
                'hotel_id' => $hotel_id,
                'key' => $key,
                'value' => $value,
                'group' => $fields[$key]['group'],
            ]);

            $setting = HotelSetting::find($setting->id);
            $settings[] = $setting;
        }

        return $settings;
    }

    public function destroy($id, $options)
    {
        $hotelSetting = new HotelSetting;

        if (!empty($options['hotelIds'])) {
            $hotelSetting = $hotelSetting->whereIn('hotel_id', $options['hotelIds']);
        }

        $hotelSetting = $hotelSetting->find($id);

        if (empty($hotelSetting)) {
            return null;
        }

        $hotelSetting->delete();
        return 204;
    }

    public function getSettingByKeyHotel($options)
    {
        $hotelSetting = new HotelSetting;

        if (!empty($options['key'])) {
            $hotelSetting = $hotelSetting->where('key', $options['key']);
        }

        if (!empty($options['hotelId'])) {
            $hotelSetting = $hotelSetting->where('hotel_id', $options['hotelId']);
        }

        return $hotelSetting->first();
    }

    public function rules($fields)
    {
        $hotelSetting = new HotelSetting;

        if (empty($fields)) {
            return $hotelSetting->rules;
        }

        return array_only($hotelSetting->rules, $fields);
    }

    public function groups()
    {
        $hotelSetting = new HotelSetting;

        return $hotelSetting->groups;
    }
}
