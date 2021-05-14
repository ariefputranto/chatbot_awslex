<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Events\MessageSentEvent;
use App\Models\Message;

use Aws\LexRuntimeService\LexRuntimeServiceClient;

use Auth;
use DB;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index()
    {
        $user = Auth::user();
        $message = Message::with('from_user', 'to_user')
	        ->where('from_user_id', $user->id)
            ->orWhere('to_user_id', $user->id)
        	->get();

        return collect([
        	'status' => 'success',
        	'message' => '',
        	'data' => $message
        ]);
    }

    public function store(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'message' => 'required'
        ]);

        if ($validator->fails()) {
            return collect([
            	'status' => 'failed',
            	'message' => $validator->errors()->first(),
            	'data' => []
            ]);
        }

        $user = Auth::user();

        DB::beginTransaction();
        try {
            $message = Message::create([
                'message' => $request->message,
                'from_user_id' => $user->id
            ]);

            $message->load('from_user', 'to_user');

            // send message to bot
            $botMessage = $this->sendMessageToBot($message, $user);

            // broadcast user message
            broadcast(new MessageSentEvent($message, $user->id));

            // broadcast bot message
            broadcast(new MessageSentEvent($botMessage, null, $user->id));

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            return collect([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }

        return collect([
        	'status' => 'success',
        	'message' => '',
        	'data' => []
        ]);
    }

    private function sendMessageToBot($message, $user)
    {
        // config aws lex
        $config = [
            'region' => 'ap-southeast-1',
            'version' => "latest",
            'credentials' => [
                'key' => env('AWS_ACCESS_KEY_ID', ''),
                'secret' => env('AWS_SECRET_ACCESS_KEY', '')
            ],
        ];
	    $client = new LexRuntimeServiceClient($config);

        // send message to bot
	    $config = [
	    	'botAlias' => 'test', // REQUIRED
		    'botName' => 'BookTripExample', // REQUIRED
		    'inputText' => $message->message, // REQUIRED
		    // 'requestAttributes' => ['<string>', ...],
		    'sessionAttributes' => [],
		    'userId' => $user->id . '-' . $user->name, // REQUIRED
	    ];
	    $response = $client->postText($config);

        // save bot message
        $message = Message::create([
            'message' => $response->get('message'),
            'to_user_id' => $user->id
        ]);
        $message->load('from_user', 'to_user');

        return $message;
    }
}
