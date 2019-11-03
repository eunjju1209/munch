<?php
/**
 * Created by PhpStorm.
 * User: eunju
 * Date: 2018-07-01
 * Time: 오후 2:56
 */

defined('BASEPATH') OR exit('No direct script access allowed');

class Member extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Member_model', 'member');
    }

    public function index()
    {
        $this->login_form();
    }


    /**
     * 회원가입 폼
     */
    public function join_form()
    {
        $this->load->view('common/header.html');
        $this->load->view('member/join_form.html');
        $this->load->view('common/footer.html');
    }

    public function login_form()
    {
        $this->load->view('common/header.html');
        $this->load->view('member/login_form.html');
        $this->load->view('common/footer.html');
    }

    /**
     * 회원가입
     * password => md5 암호화한다.
     */
    public function join()
    {
        $this->load->model('Member_model', 'member');
//        // email && password 체크

        $validation_data = array(
            'email',
            'password',
            'name'
        );

        foreach ($validation_data as $value) {
            $this->form_validation->set_rules($value, $value, 'required');
        }

        if ($this->form_validation->run() == false) {
            alert("필수 데이터를 넣어주세요.");
        } else {
            //중복된 아이디 있는지 체크
            if (!empty($this->member->getMember(array('where' => array('email' => $this->input->get_post('email')))))) {
                // 중복된 아이디 있음
                alert("해당 이메일은 이미 사용되고 있습니다.");
            } else {

                $join_data = array(
                    'email' => $this->input->get_post('email', true),
                    'name' => $this->input->get_post('name', true),
                    'telphone' => $this->input->get_post('telphone', true),
                    'password' => md5(trim($this->input->get_post('password'))),
                    'use_fl' => 'Y'
                );

                $this->member->doRegister($join_data);
            }

            alert("회원가입이 완료되었습니다.", '/member/login_form/');
        }
    }

    /**
     * 로그인
     */
    public function login()
    {
        //아이디 체크

        /***
         * 사용할 email, password
         */

        if (trim($this->input->post('email')) == "") {
            alert("아이디를 넣어주세요.");
        }

        if (trim($this->input->post('password')) == "") {
            alert("비밀번호를 넣어주세요.");
        }

        $member_info = $this->member->getMember(array(
            'where' => array(
                'email' => $this->input->post('email'),
                'use_fl' => 'Y'
            )
        ));

        if (empty($member_info)) {
            alert("존재하지 않는 정보입니다.");
        }

        //아이디랑 비밀번호 같은지 체크해보기

        if ($member_info['password'] !== md5(trim($this->input->post('password')))) {
            alert("비밀번호가 맞지 않습니다.");
        }

        // session 으로 저장 하자
        // 원래 session 쓸때 load->libraries('session') 해줘야 하는데
        // autoload.php 에서 세션은 항상 사용한다고 처리함
        // 세션에서 일단 사용할 값은 member_idx , email 로 저장하고 나중에 추가로
        //설정할지말지 선택한다.
        $this->setSession($member_info);
    }

    public function setSession($member_info)
    {
        $userData = $this->session->get_userdata();
        if (!empty($userData)) {
            $this->session->unset_userdata($userData);
        }

        $session_data = array(
            'member_idx' => $member_info['member_idx'],
            'email' => $member_info ['email'],
            'name' => $member_info['name'],
            'telphone' => $member_info['telphone'],
            'is_admin' => $member_info['is_admin']
        );

        if ($member_info['is_admin'] === 1) {
            $session_data['is_admin'] = true;
            $this->load->vars(array('IS_ADMIN', true));
        }

        $this->session->set_userdata($session_data);
        redirect('/');
    }

    /**
     * 로그아웃
     */
    public function logout()
    {
        $session = $this->session->all_userdata();
        foreach ($session as $key => $val) {
            $this->session->unset_userdata($key);
        }

        alert("로그아웃 되었습니다.", '/member/login_form');
    }


    /**
     *
     * 회원 탈퇴 기능 이지만 user_flg = 'N' 으로 처리한다.
     * use_fl = 'N'
     * 나중에 batch 로 del_dt 3개월 && 1년 삭제 처리되는거 개발 해야함
     */
    public function signout()
    {

        if ($this->session->userdata('email') != "" && $this->session->userdata('member_idx') != "") {
            $this->member->doUpdate(array(
                'where' => array(
                    'member_idx' => $this->session->userdata('member_idx'),
                    'email' => $this->session->userdata('email')
                )
            ), array('use_fl' => 'N', 'del_dt' => date("Y-m-d H:i:s")));

            alert("회원 탈퇴 완료하였습니다.", '/');
            $this->session->unset_userdata('email');
            $this->session->unset_userdata('telphone');
            $this->session->unset_userdata('member_idx');
        } else {

            alert("잘못된 접근입니다.", "/");
        }
    }

    public function findPasswordForm()
    {
        $this->load->view('common/header.html');
        $this->load->view('member/find-password-form.phtml');
        $this->load->view('common/footer.html');
    }


    /**
     * 프로필수정
     */
    public function modifyProfile()
    {
        $pwd = $this->input->post('new-pwd');
        $name = $this->input->post('name');
        $telphone = $this->input->post('telphone');

        $member_info = $this->member->getMember([
            'where' =>
                [
                    'email' => $this->session->userdata('email'),
                    'use_fl' => 'y',
                    'member_idx' => $this->session->userdata('member_idx')
                ]
        ]);

        $modifyData = [];
        $modifySessionData = [];

        if (!empty($pwd) && ($member_info['password'] !== md5(trim($pwd)) || empty($member_info['password']))) {
            $modifyData['password'] = md5(trim($pwd));
        }

        if (!empty($name) && $member_info['name'] !== $name) {
            $modifyData['name'] = $name;
            $modifySessionData['name'] = $name;
        }
        if (!empty($telphone) && ($member_info['telphone'] !== $telphone || empty($member_info['telphone']))) {
            $modifyData['telphone'] = str_replace('-', '', trim($telphone));
            $modifySessionData['telphone'] = $modifyData['telphone'];
        }

        if (!empty($modifyData)) {
            $modifyData['edit_dt'] = date("Y-m-d H:i:s");
            $modifyData['edit_idx'] = $this->session->userdata('mem_idx');

            $changed = $this->member->doUpdate([
                'where' => [
                    'member_idx' => $this->session->userdata('member_idx'),
                    'email' => $this->session->userdata('email')
                ]
            ], $modifyData);

            if (!empty($changed)) {
                if (!empty($modifySessionData)) {
                    $this->session->set_userdata($modifySessionData);
                }

                alert('프로필이 정상적으로 저장되었습니다.');
            } else {
                alert('프로필 변경에 실패하였습니다. 잠시 후 재시도해주세요.');
            }

        } else {
            alert('변경된 내용이 없습니다.');
        }

        redirect('/accounts/profile');
    }

    public function pwdFindAuthForm()
    {
        if (empty($_GET['email']) || empty($_GET['name'])) {
            alert('인증요청이 정상적이지 않습니다. 인증을 새로 요청해주세요.');
            redirect('/member/login_form/');
            return false;
        }
        $params['email'] = urldecode($_GET['email']);
        $params['name'] = urldecode($_GET['name']);

        $member_info = $this->member->getMember([
            'where' => [
                'email' => $params['email'],
                'name' => $params['name'],
                'use_fl' => 'y'
            ]
        ]);

        if (empty($member_info)) {
            alert('존재하지 않는 정보입니다. 인증을 새로 요청해주세요.');
            redirect('/member/login_form/');
            return false;
        }

        $data['name'] = $params['name'];
        $data['email'] = $params['email'];

        $this->load->view('common/header.html');
        $this->load->view('member/find_pwd_auth_form.phtml', $data);
        $this->load->view('common/footer.html');
    }

    public function confirmAuthNumber()
    {
        $params = $_POST;

        $params['name'] = urldecode($params['name']);
        $params['auth'] = urldecode($params['auth']);
        $params['email'] = urldecode($params['email']);

        if (empty($params['name']) || empty($params['auth']) || empty($params['email'])) {
            echo json_encode('fail');
            exit;
        }

        $member_info = $this->member->getMember([
            'where' => [
                'find_pwd_auth_number' => md5(trim($params['auth'])),
                'email' => $params['email'],
                'name' => $params['name'],
                'use_fl' => 'y'
            ]
        ]);

        if (!empty($member_info['find_pwd_auth_expire_dt']) && $member_info['find_pwd_auth_expire_dt'] < date('Y-m-d H:i:s')) {
            echo json_encode('expire');
            exit;
        }

        echo !empty($member_info) ? json_encode('success') : json_encode('fail');
        exit;
    }

    public function changePassword()
    {
        $auth = $this->input->post('find_pwd_auth_number');
        $pwd = $this->input->post('password');
        $email = $this->input->post('email');

        if (empty($pwd) || empty($auth) || empty($email)) {
            alert('요청된 값이 정상적이지 않습니다.');
            return false;
        }

        $member_info = $this->member->getMember([
            'where' => [
                'find_pwd_auth_number' => md5(trim($auth)),
                'email' => $email,
                'use_fl' => 'Y'
            ]
        ]);

        if (!empty($pwd)) {
            $modifyData['password'] = md5(trim($pwd));
        }

        $changed = $this->member->doUpdate([
            'where' => [
                'member_idx' => $member_info['member_idx'],
                'email' => $member_info['email'],
                'use_fl' => 'y'
            ]
        ], [
            'password' => md5(trim($pwd)),
            'edit_dt' => date("Y-m-d H:i:s")
        ]);

        if (!empty($changed)) {
            alert('비밀번호가 정상적으로 저장되었습니다. 로그인을 해주세요.','/member/login_form/');
        } else {
            alert('비밀번호 변경에 실패하였습니다. 잠시 후 재시도해주세요.');
        }
    }
}
