<?php

namespace App\Http\Controllers;

use App\Location;
use App\Service\IS_Encryption;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends BaseController
{
    public $paginate = 10;

    public function __construct()
    {
        parent::__construct('user');
    }

    public function getReg(Request $request)
    {
        return view('register')
            ->with('hide_login', true);
    }

    public function postReg(Request $request)
    {
        $captchaRes = $this->checkCaptcha();

        if (!$captchaRes['success']) {
            return $this->result($captchaRes['message'], false);
        }

        $reqData = $this->getEncryptedData($request);
        if ($reqData === null)
            return $this->result('參數錯誤', false);

        $reqData = collect($reqData);

        //讀表單
        $account = $reqData->get('account');
        $password = bcrypt($reqData->get('password'));
        $name = $reqData->get('name');
        $role = $reqData->get('role');
        if (!preg_match("/^(B|C)$/", $role)) {
            return $this->result('參數錯誤', false);
        }
        $sn = $reqData->get('sn');
        $gender = $reqData->get('gender');
        if (!preg_match("/^(男|女)$/", $gender)) {
            return $this->result('參數錯誤', false);
        }
        $email = $reqData->get('email');
        $birthday = $reqData->get('birthday');
        //資料封裝
        $new_user = new User();
        $new_user->account = $account;
        $new_user->password = $password;
        $new_user->role = $role;
        $new_user->name = $name;
        $new_user->sn = $sn;
        $new_user->gender = $gender;
        $new_user->email = $email;
        $new_user->birthday = $birthday;
        $keyPair = IS_Encryption::createKeyPair($new_user);
        $new_user->public_key = $keyPair->public;
        $new_user->private_key = $keyPair->private;
        //
        $check_user = User::where('account', $account)->count();
        if ($check_user !== 0) {
            return $this->result('已有相同帳戶', false);
        } else {
            $new_user->save();
            return $this->result('註冊成功', true);
        }
    }

    public function postLogin(Request $request)
    {

        $reqData = $this->getEncryptedData($request);
        if ($reqData === null)
            return $this->result('參數錯誤', false);

        $reqData = collect($reqData);
        //讀表單
        $account = $reqData->get('account');
        $password = $reqData->get('password');
        $captchaRes = $this->checkCaptcha();

        if (!$captchaRes['success']) {
            return $this->result($captchaRes['message'], false);
        }

        $check_user = User::where('account', '=', $account)->first();

        if ($check_user) {
            $dbpassword = $check_user->password;
            if (Hash::check($password, $dbpassword)) {
                $this->updateUser($check_user);
                session()->flash('refreshKey', true);
                return $this->result('登入成功', true);
            }
        }
        return $this->result('登入失敗', false);
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        $request->session()->flash('log', '登出成功');
        $request->session()->flash('refreshKey', true);
        return redirect('/');
    }

    public function getUserInfo(Request $request)
    {
        $id = session('user.id');
        $data = User::find($id);
        $locationData = Location::
        where('user_id', '=', $id)->first();
        if (!$data) {
            return $this->logout($request);
        }

        return view('management.user-info', ['data' => $data], ['locationData' => $locationData])
            ->with('title', '會員資料');
    }

    public function changePassword(Request $request)
    {
        $id = $request->session()->get('user')->id;
        $oldpassword = request('oldpassword');
        $check_user = User::where('id', '=', $id)->first();
        $dbpassword = $check_user->password;
        if (Hash::check($oldpassword, $dbpassword)) {
            $check_user->password = bcrypt(request('newpassword'));
            $check_user->save();
            $this->logout($request);
            return $this->result('修改成功，請重新登入。', true);
        } else {
            return $this->result('密碼不符合', false);
        }
    }

    public function postChangeAvatar(Request $request)
    {
        $id = session('user.id');
        $user = User::find($id);
        $avatar = $request->get('avatar');
        if (!is_string($avatar)) $avatar = '';
        $user->avatar = trim($avatar);
        $this->updateUser($user);

        return $this->result([
            'url' => $user->getAvatarUrl()
        ], true);
    }

    public function changeEmail(Request $request)
    {
        $id = $request->session()->get('user')->id;
        $email = request('email');
        $check_user = User::where('id', '=', $id)->first();
        $check_user->email = $email;
        $this->updateUser($check_user);
        return $this->result('修改成功', true);
    }

    private function updateUser(User $user): User
    {
        if (empty($user->private_key) || empty($user->public_key)) {
            $keyPair = IS_Encryption::createKeyPair($user);
            $user->public_key = $keyPair->public;
            $user->private_key = $keyPair->private;
        }

        $user->save();
        session()->put('user', $user);

        return $user;
    }
}
