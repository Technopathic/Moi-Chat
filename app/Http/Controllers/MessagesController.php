<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Validator;
use App\Channel;
use App\Message;
use App\Friend;
use App\Friendchat;
use App\Fmessage;
use App\User;
use \Auth;
use \Response;
use \DB;
use Pusher;

class MessagesController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['only' => ['storeMessage', 'showChannel', 'getFmessages', 'storeFmessage']]);
  }

  public function getMessages(Request $request, $id)
  {
    $channel = Channel::where('id', '=', $id)->where('userID', '=', 0)->select('id', 'channelName', 'channelLocation', 'channelRegion', 'channelLock', 'channelTopic')->first();

    if(!empty($channel))
    {
      $messages = Message::where('messages.channelID', '=', $channel->id)->join('users', 'messages.userID', '=', 'users.id')->select('messages.id', 'messages.messageBody', 'messages.userID', 'users.name', 'users.avatar', 'messages.created_at')->orderBy('messages.created_at', 'ASC')->paginate(30);
      $activeChannel = array('channelName' => $channel->channelName, 'channelID' => $channel->id, 'channelRegion' => $channel->channelRegion, 'channelLocation' => $channel->channelLocation);
      return Response::json(['activeChannel' => $activeChannel, 'messages' => $messages])->setCallback($request->input('callback'));
    }
    else {
      //channel not found
      return Response::json(5)->setCallback($request->input('callback'));
    }
  }

  public function showChannel(Request $request, $id)
  {
    $channel = Channel::where('id', '=', $id)->select('id', 'userID', 'channelName', 'channelLocation', 'channelRegion', 'channelLock', 'channelTopic')->first();

    if(!empty($channel))
    {
      if($channel->userID == 0)
      {
        return $this->getMessages($channel->id);
      }
      else {
        $user = Auth::user();
        if(!empty($user)) {

          if($channel->channelLock == 1)
          {
            $chanAuth = DB::table('chanauths')->where('userID', '=', $user->id)->where('channelID', '=', $channel->id)->first();
            if(empty($chanAuth))
            {
              //You do not have permission to access this channel.
              return Response::json(2)->setCallback($request->input('callback'));
            }
          }

          $ban = DB::table('chanbans')->where('userID', '=', $user->id)->where('channelID', '=', $channel->id)->first();
          if(empty($ban))
          {
            $messages = Message::where('messages.channelID', '=', $channel->id)->join('users', 'messages.userID', '=', 'users.id')->select('messages.id', 'messages.messageBody', 'messages.userID', 'users.name', 'users.avatar', 'messages.created_at')->orderBy('messages.created_at', 'ASC')->paginate(30);

            $activeChannel = array('channelName' => $channel->channelName, 'channelID' => $channel->id);
            return Response::json(['activeChannel' => $activeChannel, 'messages' => $messages])->setCallback($request->input('callback'));
          }
          else {
            //You were banned
            return Response::json(3)->setCallback($request->input('callback'));
          }
        }
        else {
          //You must be logged in to access channels.
          return Response::json(4)->setCallback($request->input('callback'));
        }
      }
    }
    else {
      //channel not found
      return Response::json(5)->setCallback($request->input('callback'));
    }
  }

  public function storeMessage(Request $request)
  {
    $rules = array(
      'messageBody' => 'required',
      'channelID'		=> 	'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0)->setCallback($request->input('callback'));
    } else {

      $messageBody = $request->json('messageBody');
      $channelID = $request->json('channelID');
      $dateTime = $request->json('dateTime');

      $channel = Channel::find($channelID);
      if(empty($channel))
      {
        //Could not find Channel
        return Response::json(2)->setCallback($request->input('callback'));

      } else {
        $user = Auth::user();
        $userID = $user->id;
        $ban = DB::table('chanbans')->where('userID', '=', $userID)->where('channelID', '=', $channel->id)->first();
        if(!empty($ban))
        {
          //You were banned
          return Response::json(4)->setCallback($request->input('callback'));
        }

        if(strlen($messageBody) < 250)
        {
          $message = new Message;
          $message->userID = $userID;
          $message->channelID = $channel->id;
          $message->messageBody = $messageBody;
          $message->created_at = $dateTime;
          $message->save();

          $messageData = Message::where('messages.id', '=', $message->id)->join('users', 'messages.userID', '=', 'users.id')->select('messages.id', 'messages.messageBody', 'messages.userID', 'messages.channelID', 'users.name', 'users.avatar', 'messages.created_at')->first();
          $messageChan = "channel-".$messageData->channelID;
          $pusher = new Pusher( '', '', '', array( 'encrypted' => false ) );

          $pusher->trigger($messageChan, 'messageSend', $messageData);

          return Response::json(1)->setCallback($request->input('callback'));
        }
        else {
          //Message is too long
          return Response::json(5)->setCallback($request->input('callback'));
        }
      }
    }
  }

  public function getFmessages(Request $request, $id)
  {
    $friend = Friend::find($id);

    if(!empty($friend))
    {
      $user = Auth::user();
      if(!empty($user)) {

        $block = DB::table('blocks')->where('userID', '=', $friend->id)->where('blockID', '=', $user->id)->first();
        if(empty($block))
        {
          $mutual = Friend::where('userID', '=', $friend->id)->where('friendID', '=', $user->id)->first();
          if(!empty($mutual))
          {
            $friendChatOne = Friendchat::where('userID', '=', $user->id)->where('friendID', '=', $friend->id)->first();
            $friendChatTwo = Friendchat::where('userID', '=', $friend->id)->where('friendID', '=', $user->id)->first();
            if(empty($friendChatOne) && empty($friendChatTwo))
            {
              $friendChat = new Friendchat;
              $friendChat->userID = $user->id;
              $friendChat->friendID = $friend->id;
              $friendChat->save();

              $messages = Fmessage::where('fmessages.friendID', '=', $friendChat->id)->join('users', 'fmessages.userID', '=', 'users.id')->select('fmessages.id', 'fmessages.messageBody', 'fmessages.userID', 'users.name', 'users.avatar', 'fmessages.created_at')->orderBy('fmessages.created_at', 'ASC')->paginate(30);
              $activeFriend = $friendChat->$id;
            }
            else {
              if(!empty($friendChatOne))
              {
                $messages = Fmessage::where('fmessages.friendID', '=', $friendChatOne->id)->join('users', 'fmessages.userID', '=', 'users.id')->select('fmessages.id', 'fmessages.messageBody', 'fmessages.userID', 'users.name', 'users.avatar', 'fmessages.created_at')->orderBy('fmessages.created_at', 'ASC')->paginate(30);
                $activeFriend = $friendChatOne->id;
              }
              else if(!empty($friendChatTwo)){
                $messages = Fmessage::where('fmessages.friendID', '=', $friendChatTwo->id)->join('users', 'fmessages.userID', '=', 'users.id')->select('fmessages.id', 'fmessages.messageBody', 'fmessages.userID', 'users.name', 'users.avatar', 'fmessages.created_at')->orderBy('fmessages.created_at', 'ASC')->paginate(30);
                $activeFriend = $friendChatTwo->id;
              }
            }

            $userFriend = User::where('id', '=', $friend->friendID)->first();
            $activeChannel = array('channelName' => $userFriend->name, 'channelID' => $activeFriend);
            return Response::json(['activeChannel' => $activeChannel, 'messages' => $messages])->setCallback($request->input('callback'));
          }
          else {
            //Friendship not mutual
            return Response::json(2)->setCallback($request->input('callback'));
          }
        }
        else {
          //You were Blocked
          return Response::json(3)->setCallback($request->input('callback'));
        }
      }
      else {
        //You must be logged in to access Friends.
        return Response::json(4)->setCallback($request->input('callback'));
      }
    }
    else {
      //Chat not found
      return Response::json(5)->setCallback($request->input('callback'));
    }
  }

  public function storeFmessage(Request $request)
  {
    $rules = array(
      'messageBody' => 'required',
      'friendID'		=> 	'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0)->setCallback($request->input('callback'));
    } else {

      $messageBody = $request->json('messageBody');
      $friendID = $request->json('friendID');
      $dateTime = $request->json('dateTime');

      $friendChat = FriendChat::where('id', '=', $friendID)->first();
      if(empty($friendChat))
      {
        //Could not find Friend
        return Response::json(2)->setCallback($request->input('callback'));

      } else {
        $user = Auth::user();
        $userID = $user->id;
        $block = DB::table('blocks')->where('blockID', '=', $userID)->where('userID', '=', $friendChat->friendID)->first();
        if(!empty($block))
        {
          //You were blocked
          return Response::json(4)->setCallback($request->input('callback'));
        }

        if(strlen($messageBody) < 250)
        {
          $message = new Fmessage;
          $message->userID = $userID;
          $message->friendID = $friendChat->id;
          $message->messageBody = $messageBody;
          $message->created_at = $dateTime;
          $message->save();

          $date = date('Y-m-d G:i:s');
          DB::table('friends')->where('userID', '=', $userID)->where('friendID', '=', $friendChat->friendID)->update(array('updated_at' => $date));

          $friendData = Fmessage::where('fmessages.id', '=', $message->id)->join('users', 'fmessages.userID', '=', 'users.id')->select('fmessages.id', 'fmessages.messageBody', 'fmessages.userID', 'fmessages.friendID', 'users.name', 'users.avatar', 'fmessages.created_at')->first();
          $friendChan = "friend-".$friendData->friendID;
          $pusher = new Pusher( '', '', '', array( 'encrypted' => false ) );

          $pusher->trigger($friendChan, 'fmessageSend', $friendData);

          //Success
          return Response::json(1)->setCallback($request->input('callback'));
        }
        else {
          //Message is too long
          return Response::json(5)->setCallback($request->input('callback'));
        }
      }
    }
  }
}
