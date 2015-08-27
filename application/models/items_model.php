<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Nick
 * Date: 27.08.2015
 * Time: 15:28
 */
class Items_model extends CI_Model
{
    private $table_name = 'saved_data';
    /** @var null|object */
    private $current_info = null;


    /**
     * Uploading JSON with data from Source Server
     */
    private function init_current_info()
    {
        if($this->current_info) return false;
        if( $curl = curl_init() ) {
            curl_setopt($curl, CURLOPT_URL, JSON_PATH_RU);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
            $out = curl_exec($curl);
            curl_close($curl);
            $info = json_decode($out);
            if(!$info)
            {
                throw new Exception('Data was corrupted!', 400);
            }
            $dt_object = new DateTime($info->date);
            $date = $dt_object ? $dt_object->format('Y-m-d') : date('Y-m-d');
            $this->cache_info($date, $out);
            $this->current_info = $info;
        }
        else
        {
            throw new Exception('CURL init error', 400);
        }
    }

    /**
     * Checking if we already save today's data in database. If not - saving it;
     * @param $date /Y-m-d
     * @param $raw_info /JSON string
     */
    function cache_info($date, $raw_info)
    {
        if(!$this->get_by_date($date))
        {
            $this->db->insert($this->table_name, [
                'date' => $date,
                'info' => $raw_info
            ]);
        }
    }


    /**
     * Get Saved Data from DB by Date
     * @param $date
     * @return array
     */
    public function get_by_date($date)
    {
        $this->db->where('date', $date);
        return $this->db->get($this->table_name)->row_array();
    }

    public function get_by_range($from, $to)
    {
        $this->db->where("date BETWEEN '{$from}' AND '{$to}'");
        return $this->db->get($this->table_name)->result_array();
    }

    public function get_current_data()
    {
        if(!$this->current_info)
        {
            $this->init_current_info();
        }
        return $this->current_info;
    }


    public function get_selectors()
    {
        $data = $this->get_current_data();
        $result = [
            'cities' =>  $data->cities,
            'regions' => $data->regions,
            'currencies' => $data->currencies,
            'org_types' => $data->orgTypes
        ];
        return $result;
    }


    public function get_current_data_by_place($city, $region)
    {
        $data = $this->get_current_data();
        $info = $data->organizations;
        $result = [];
        foreach($info as $bank)
        {
            if($city)
            {
                if($bank->cityId == $city)
                {
                    $result[] = $bank;
                }

            }
            elseif($region)
            {
                if($bank->regionId == $region)
                {
                    $result[] = $bank;
                }

            }
        }
        return $result;
    }

    public function get_saved_data_by_place($city, $region, $from, $to)
    {
        if(!$from || !$to)
        {
            throw new Exception('Data range param(s) corrupted!', 400);
        }
        $from_object = DateTime::createFromFormat('Y-m-d', $from);
        $to_object = DateTime::createFromFormat('Y-m-d', $to);
        if(!$from_object || !$to_object)
        {
            throw new Exception('Data range param(s) corrupted!', 400);
        }
        $saved = $this->get_by_range($from, $to);
        if(!$saved)
        {
            throw new Exception('No data in given date range!', 404);
        }
        $result = [];
        foreach($saved as $row)
        {
            $data = json_decode($row['info']);
            if(!$data)
            {
                $result[$row['date']] = 'JSON parsing error!';
                continue;
            }
            $info = $data->organizations;
            foreach($info as $bank)
            {
                if($city)
                {
                    if($bank->cityId == $city)
                    {
                        $result[$row['date']][] = $bank;
                    }

                }
                elseif($region)
                {
                    if($bank->regionId == $region)
                    {
                        $result[$row['date']][] = $bank;
                    }

                }
            }
        }
        return $result;
    }
}