<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Events\MessageSentEvent;
use App\Models\Message;

use Auth;

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
        $message = Message::create([
            'message' => $request->message,
            'from_user_id' => $user->id
        ]);

        $message->load('from_user', 'to_user');

        // send event to user
	    broadcast(new MessageSentEvent($message, $user->id));

        return collect([
        	'status' => 'success',
        	'message' => '',
        	'data' => []
        ]);
    }
}
