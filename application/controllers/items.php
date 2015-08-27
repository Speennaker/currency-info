<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once($_SERVER['DOCUMENT_ROOT'].'/application/libraries/REST_Controller.php');
class Items extends REST_Controller {

	/** @var  Items_model */
    public $model;
    function __construct()
    {
        parent::__construct();
        $this->load->model('Items_model', 'model');
    }

    public function data_get()
    {
        try
        {
            if(!$this->get('city') && !$this->get('region'))
            {
                $this->response('Required Params are absent!', 400);
            }
            $data = $this->model->get_current_data_by_place($this->get('city'), $this->get('region'));
            $this->response($data, 200);
        }
        catch(Exception $e)
        {
            $this->response($e->getMessage(), $e->getCode());
        }
    }

    public function archive_get()
    {
        try
        {
            if(!$this->get('from') || !$this->get('to') ||(!$this->get('city') && !$this->get('region')))
            {
                $this->response('Required Params are absent!', 400);
            }
            $data = $this->model->get_saved_data_by_place($this->get('city'), $this->get('region'), $this->get('from'), $this->get('to'));
            $this->response($data, 200);
        }
        catch(Exception $e)
        {
            $this->response($e->getMessage(), $e->getCode());
        }
    }

    public function selectors_get()
	{
        try
        {
            $selectors = $this->model->get_selectors();
            $this->response($selectors, 200);
        }
        catch(Exception $e)
        {
            $this->response($e->getMessage(), $e->getCode());
        }


	}
}
