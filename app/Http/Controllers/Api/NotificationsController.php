<?php

namespace App\Http\Controllers\Api;
use Kreait\Firebase\Exception\Messaging\InvalidMessage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\AndroidConfig;
use App\Models\Api\AppUser;
use Auth;
use DB;
class NotificationsController extends Controller
{
    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }
    public function index(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);
        extract($request->all());
        $user =  Auth::guard('app-api')->user();
        // $tokens = 'cIEqqDJ1RKW3jTMo6KnixH:APA91bGgVuLdV9uo1Iah3fTQuhO-MV9ZHq_bW39FQeQ1Q5qRu087Uiq1Y0pycF8hLWw0jVPjyVFkE4IYCrrwRU7vgRTU-6eGv42TXVCKB02GaKOL51456PJhttpVvG5K0-9_-ars6oAs';
        $tokens = $user->device_token;
        $result = $this->messaging->validateRegistrationTokens($tokens);

        if(isset($result['valid']) && !empty($result['valid']) )
        {
            try {
                $data['title'] = $title;
                $data['body'] = $body;
                $message = CloudMessage::withTarget('token',$tokens)->withNotification($data);
                $resp = $this->messaging->send($message); 
                $message_id = $this->getmessageid($resp['name']);
                $data1['status'] = 'success';
                $data1['message'] = 'Notification Sent Successfully, Message ID '.$message_id; 
                return response()->json($data1,200);            
            } catch (InvalidMessage $e) {
                $data['status'] = 'error';
                $data['message'] = $e->errors();
                return response()->json($data,401);
            }
            
        }
        else
        {
            $data['status'] = 'error';
            $data['message'] = 'Invalid FCM Registration Device Token. Please use a valid Token.';
            return response()->json($data,401);
        }
    }
    function getmessageid($str)
    {
        return  substr($str, strpos($str, "messages/") + 9);
        
    }
    public function multiUserNotification(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'body' => 'required',
            'category' => 'required'
        ]);
        $a_tokens = AppUser::select('device_token')->where('device_type',1)->get()->toArray();
        $a_tokens = array_column($a_tokens,'device_token');
        $data = $this->androidDevice($a_tokens,$request->all());
        return $data;
        // try {
        //     $data['title'] = $title;
        //     $data['body'] = $body;
        //     $message = CloudMessage::new()->withNotification($data); // Any instance of Kreait\Messaging\Message
        //     $report = $this->messaging->sendMulticast($message,$a_tokens);
        //     $data1['success'] = $report->successes()->count();
        //     $data1['Failed'] = $report->failures()->count();
        //     $data1['status'] = 'success';
        //     $data1['message'] = 'Number of Success Notification :'.$report->successes()->count()
        //     .' Number of Faild Notification is :'.$report->failures()->count();
        //     $return_msg['android'] = $data1;
        // }
        //     catch (InvalidMessage $e) {
        //     $data['status'] = 'error';
        //     $data['message'] = $e->errors();
        //     return response()->json($data,401);
        // }
        // $ios_tokens = AppUser::select('device_token')->where('device_type',2)->get()->toArray();
        // $a_tokens = array_column($ios_tokens,'device_token');
        // try {
        //     $data['title'] = $title;
        //     $data['body'] = $body;
        //     $message = CloudMessage::new()->withNotification($data); // Any instance of Kreait\Messaging\Message
        //     $report = $this->messaging->sendMulticast($message,$a_tokens);
        //     $data1['success'] = $report->successes()->count();
        //     $data1['Failed'] = $report->failures()->count();
        //     $data1['status'] = 'success';
        //     $data1['message'] = 'Number of Success Notification :'.$report->successes()->count()
        //     .' Number of Faild Notification is :'.$report->failures()->count();
        //     $return_msg['android'] = $data1;
        // }
        //     catch (InvalidMessage $e) {
        //     $data['status'] = 'error';
        //     $data['message'] = $e->errors();
        //     return response()->json($data,401);
        // }

                 
    }
    public function androidDevice($var,$data)
    {
        // dd($data);
        try {
            $config = AndroidConfig::fromArray([
                'ttl' => '3600s',
                'priority' => 'normal',
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'icon' => 'stock_ticker_update',
                    'color' => '#f45342',
                    'sound' => 'default',
                ],
            ]);
            $arr = array(
                'category' => $data['category']
            );
            if(is_array($var)){
                $message = CloudMessage::new()->withNotification(['title' => $data['title'], 'body' => $data['body']])->withAndroidConfig($config)->withData($arr); // Any instance of Kreait\Messaging\Message
                $report = $this->messaging->sendMulticast($message,$var);
                // dd($report);
                $data1['success'] = $report->successes()->count();
                $data1['Failed'] = $report->failures()->count();
                $data1['status'] = 'success';
                $data1['message'] = 'Number of Success Notification :'.$report->successes()->count()
                .' Number of Faild Notification is :'.$report->failures()->count();
                return response()->json($data1,200); 
            }else{
                $message = CloudMessage::withTarget('token',$var)->withNotification(['title' => $data['title'], 'body' => $data['body']])->withAndroidConfig($config)->withData($arr);
                $resp = $this->messaging->send($message); 
                $message_id = $this->getmessageid($resp['name']);
                $data1['status'] = 'success';
                $data1['message'] = 'Notification Sent Successfully, Message ID '.$message_id; 
                return response()->json($data1,200);    
            }
        }
        catch (InvalidMessage $e) {
            $data['status'] = 'error';
            $data['message'] = $e->errors();
            return response()->json($data,401);
        }
    }

    public function appleDevice($var,$data)
    {
        try {
            $config = AndroidConfig::fromArray([
                'ttl' => '3600s',
                'priority' => 'normal',
                'category' => $data['category'],
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'icon' => 'stock_ticker_update',
                    'color' => '#f45342',
                    'sound' => 'default',
                ],
            ]);
            if(is_array($var)){
                $data['title'] = $title;
                $data['body'] = $body;
                $message = CloudMessage::new()->withNotification($data); // Any instance of Kreait\Messaging\Message
                $report = $this->messaging->sendMulticast($message,$a_tokens)->withAndroidConfig($config);
                $data1['success'] = $report->successes()->count();
                $data1['Failed'] = $report->failures()->count();
                $data1['status'] = 'success';
                $data1['message'] = 'Number of Success Notification :'.$report->successes()->count()
                .' Number of Faild Notification is :'.$report->failures()->count();
                return response()->json($data1,200); 
            }else{
                $data['title'] = $title;
                $data['body'] = $body;
                $message = CloudMessage::withTarget('token',$tokens)->withNotification($data);
                $resp = $this->messaging->send($message)->withAndroidConfig($config); 
                $message_id = $this->getmessageid($resp['name']);
                $data1['status'] = 'success';
                $data1['message'] = 'Notification Sent Successfully, Message ID '.$message_id; 
                return response()->json($data1,200);    
            }
        }
        catch (InvalidMessage $e) {
            $data['status'] = 'error';
            $data['message'] = $e->errors();
            return response()->json($data,401);
        }
    }
}
