<?php

namespace App\Http\Controllers\Api\User;


//Models

use App\Models\Pdf;
use App\Models\User;

//Helpers and Class
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Facades\Utilities;
use App\Http\Traits\ResponseApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Exceptions\ApiValidatorException;

use Validator;


class UserController extends Controller
{
    use ResponseApi;
    public function register (Request $request) 
    {
        try {
            $input = $request->all();
            $rules = [
                'email' => 'required|string',
                'id' => 'required|string',
                'folio' => 'required|string'
            ];
            $input['folio'] = Hash::make($input['folio']);
            //Hash::check()

            $validator = Validator::make($input, $rules);
            if ($validator->fails()) return $this->sendError($validator->errors()->all(),str_replace(':attribute','email',trans('validation.email')), 422);

            $user = DB::connection('mysql')->table('User')->where('id',$input['id'])->where('email',$input['email'])->first();
            //Log::info('woola');
            
            if (!empty($user))  throw new ApiValidatorException('The email is already in db',trans('user.register.duplicate'), 409);

            $user = User::create($input);
           
                /*
            dispatch(function () use ($user,$password) {
                $subject='Registro éxitoso Safe App Master';
                Mail::to($user->email)->send(new NotificationRegister($user ,$password, $subject));
            })->afterResponse();*/

            return $this->sendResponse($this->createAccesTokenResponse($user), trans('usuario creado con éxito'));
        } catch (ApiValidatorException $th) {
            return $this->sendError($th->getError(), $th->getMessage(), $th->getCode());
        }
    }

    public function login(Request $request)
    {

        $rules = array(
            'id' => array('required'),
            'folio' => array('required'),
        );

        $input = $request->all();
        //dd($input);
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return $this->sendError('Validator', $validator->errors()->all(), 422);
        }

        $user = $this->userAuthentication($input['id'], $input['folio']);

        //dd($admin);
        if (!$user) {
            return $this->sendError('Authentication', trans('No se encontró usuario'), 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
            
        
        $respuesta = [
            'ruta' => '/main-index',
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];
        return $this->sendResponse( $respuesta,trans('usuario Autenticado con éxito'));

    }


    private function userAuthentication($id, $folio)
    {
        
        $user = User::where('id',$id)->first();

        return $user && Hash::check($folio, $user->folio) ? $user : false;
    }
    
    /**
     * Get User information.
     *
     * @return \Illuminate\Http\Response
     */
    

    public function createAccesTokenResponse($user)
    {
        $token = $user->createToken('auth_token')->plainTextToken;
            
        return [
            'access_token' => $token,
            'token_type' => 'Bearer'
            //'expires_at' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString()
        ];
    }


    public function logout()
    {
        auth()->user()->tokens()->delete();
            
        return $this->sendResponse('loggout success', trans('usuario deslogueado con éxito'));

    }
}