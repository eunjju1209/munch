<?php
/**
 * Created by PhpStorm.
 * User: eunju
 * Date: 2018-07-11
 * Time: 오후 11:35
 */

class Member_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
    }

    private function setWhere($where = array())
    {

        if (isset($where['where']) && !empty($where['where'])) {
            foreach ($where['where'] as $key => $value) {
                $this->db->where($key, $value);
            }
        }

    }

    /**
     * 회원 total_count 가져오기
     */
    public function getCount($where = [])
    {
        if (!empty($where)) {
            $this->setWhere($where);
        }

        $return = $this->db->count_all_results('member');

        return $return;
    }


    /**
     * 회원 가입
     */
    public function doRegister($data = array())
    {
        $data['reg_dt'] = date("Y-m-d H:i:s");

        $this->db->insert('member', $data);
        return $this->db->insert_id();
    }

    public function doUpdate($where = array(), $params = array())
    {
        $this->db->trans_begin();
        if (!empty($where)) {
            $this->setWhere($where);
        }

        $this->db->update('member', $params);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_complete();
        }

        return true;
    }

}