<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use \Auth;
use JWTAuth;
use JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use App\Channel;
use App\User;
use \Response;
use \DB;
use \Image;
use \File;
use Pusher;

class ChannelsController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['only' => ['storeChannel', 'chanAuth', 'getAuths', 'storeAvatar', 'storeFriend', 'storeBan', 'storeBlock', 'getBlocks', 'refreshChannels', 'getFriends']]);
  }

  public function index()
  {
      return File::get('index.html');
  }

  public function getChannels(Request $request, $lat, $lon)
  {
    $location = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng='.$lat.','.$lon);
    $locData = json_decode($location, true);
    $location = $locData['results'][0]['address_components'][3]['long_name'];
    $region = $locData['results'][0]['address_components'][5]['long_name'];

    $auth = array();


    $channels = Channel::where('channelRegion', '=', $region)->where('channelLocation', '=', $location)->select('id', 'userID', 'channelName', 'channelLock')->get();
    if($channels->isEmpty())
    {
      $channel = new Channel;
      $channel->channelName = "Public";
      $channel->userID = 0;
      $channel->channelLock = 0;
      $channel->channelLocation = $location;
      $channel->channelRegion = $region;
      $channel->channelTopic = "Welcome to ".$location." #public.";
      $channel->save();

      $channels = Channel::where('channelRegion', '=', $region)->where('channelLocation', '=', $location)->select('id', 'userID', 'channelName', 'channelLock')->get();
    }

    $public = Channel::where('channelRegion', '=', $region)->where('channelLocation', '=', $location)->where('userID', '=', 0)->select('id')->first();
    return Response::json(['public' => $public, 'region' => $region, 'location' => $location, 'channels' => $channels])->setCallback($request->input('callback'));

  }

  public function refreshChannels(Request $request)
  {
    $user = Auth::user();
    $channels = Channel::where('channelRegion', '=', $user->region)->where('channelLocation', '=', $user->location)->select('id', 'userID', 'channelName', 'channelLock')->get();

    return Response::json($channels)->setCallback($request->input('callback'));
  }

  public function getAuths(Request $request)
  {
    $user = Auth::user();
    if(!empty($user))
    {
      $auth = DB::table('chanauths')->where('userID', '=', $user->id)->select('channelID')->get();
      $authData = array();
      foreach($auth as $key => $value)
      {
        $authData[] = intval($value->channelID);
      }
      return Response::json($authData)->setCallback($request->input('callback'));
    }
  }

  public function getBlocks(Request $request)
  {
    $user = Auth::user();
    if(!empty($user))
    {
      $block = DB::table('blocks')->where('userID', '=', $user->id)->select('blockID')->get();
      $blockData = array();
      foreach($block as $key => $value)
      {
        $blockData[] = intval($value->blockID);
      }
      return Response::json($blockData)->setCallback($request->input('callback'));
    }
  }

  public function createChannel(Request $request)
  {

  }

  public function storeChannel(Request $request)
  {
    $rules = array(
      'channelName'		=> 	'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0)->setCallback($request->input('callback'));
    } else {

      $user = Auth::user();
      $channelName = $request->json('channelName');
      $channelPassword = $request->json('channelPassword');
      $channelLock = $request->json('channelLock');

      if(empty($user)) {
        //Anonymous User cannot create Channel
        return Response::json(2)->setCallback($request->input('callback'));

      } else {

        $pastChans = Channel::where('userID', '=', $user->id)->select('id', 'created_at')->orderBy('id', 'DESC')->get();
        if(count($pastChans) >= 5)
        {
          //Too Many Chans
          return Response::json(3)->setCallback($request->input('callback'));
        }
        else {
          if(strlen($channelName) < 16)
          {
            if(strlen($channelPassword) < 255)
            {
              $channel = new Channel;
              $channel->channelName = $channelName;
              $channel->userID = $user->id;
              $channel->channelPassword = $channelPassword;
              if($channelLock == NULL)
              {
                $channelLock = 0;
              }
              $channel->channelLock = $channelLock;
              $channel->channelLocation = $user->location;
              $channel->channelRegion = $user->region;
              $channel->channelTopic = "Welcome to ".$channelName.".";
              $channel->save();


              if($channelLock == 1)
              {
                DB::table('chanauths')->insert(array('userID' => $user->id, 'channelID' => $channel->id));
              }

              //Channel Added.
              $channelData = Channel::where('id', '=', $channel->id)->select('id', 'channelName', 'userID', 'channelLock')->first();
              return Response::json($channelData)->setCallback($request->input('callback'));
            }
            else {
              //Password too long
              return Response::json(4)->setCallback($request->input('callback'));
            }
          }
          else {
            //Channel Name too long
            return Response::json(5)->setCallback($request->input('callback'));
          }
        }
      }
    }
  }

  public function deleteChannel(Request $request, $id)
  {
    $user = Auth::user();
    $channel = Channel::find($id);

    if($channel->userID == $user->id)
    {
      $channel->delete();
      //Channel Deleted.
      return Response::json(1)->setCallback($request->input('callback'));
    }
    else {
      //You cannot delete this channel.
      return Response::json(0)->setCallback($request->input('callback'));
    }
  }

  public function editChannel(Request $request, $id)
  {
    $channel = Channel::where('id', '=', $id)->select('id', 'channelName', 'channelLock')->first();

    return Response::json($channel)->setCallback($request->input('callback'));
  }

  public function updateChannel(Request $request, $id)
  {
    $rules = array(
      'channelName'		=> 	'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0)->setCallback($request->input('callback'));
    } else {

      $user = Auth::user();
      $channel = Channel::find($id);
      $channelName = $request->json('channelName');
      $channelPassword = $request->json('channelPassword');
      $channelLock = $request->json('channelLock');
      $channelTopic = $request->json('channelTopic');

      if(empty($user)) {
        //Anonymous users cannot edit channels.
        return Response::json(2)->setCallback($request->input('callback'));

      } else {
        if($user->id == $channel->id)
        {
          $channel->channelName = $channelName;
          $channel->channelPassword = $channelPassword;
          $channel->channelLock = $channelLock;
          $channel->channelTopic = $channelTopic;
          $channel->save();

          //Channel Updated.
          return Response::json(1)->setCallback($request->input('callback'));
        }
        else {
          //You do not own this channel.
          return Response::json(3)->setCallback($request->input('callback'));
        }
      }
    }
  }

  public function chanAuth(Request $request)
  {
    $rules = array(
      'channelPassword'		=> 	'required',
      'channelID'         => 'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0)->setCallback($request->input('callback'));
    } else {

      $user = Auth::user();
      if(!empty($user))
      {
        $channel = Channel::find($request->json('channelID'));
        $channelPassword = $request->json('channelPassword');
        if(!empty($channel))
        {
          $chanAuth = DB::table('chanauths')->where('userID', '=', $user->id)->where('channelID', '=', $channel->id)->first();
          if(!empty($chanAuth))
          {
            return Response::json(1)->setCallback($request->input('callback'));
          }
          else {
            if($channel->channelPassword == $channelPassword)
            {
              DB::table('chanauths')->insert(array('userID' => $user->id, 'channelID' => $channel->id));
              return Response::json(1)->setCallback($request->input('callback'));
            }
            else {
              //Incorrect Password
              return Response::json(2)->setCallback($request->input('callback'));
            }
          }
        }
        else {
          //Channel not found.
          return Response::json(3)->setCallback($request->input('callback'));
        }
      }
      else {
        //You must be logged in to access channels.
        return Response::json(4)->setCallback($request->input('callback'));
      }
    }
  }

  public function banUser(Request $request)
  {
    $rules = array(
      'userID'		=> 	'required',
      'channelID'	=>	'required'
    );
    $validator = Validator::make($request->json()->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0)->setCallback($request->input('callback'));
    } else {

      $user = Auth::user();
      $userID = $request->input('userID');
      $channelID = $request->input('channelID');

      $userCheck = User::find($userID);
      if(!empty($userCheck))
      {
        $channelCheck = Channel::find($channelID);
        if(!empty($channelCheck))
        {
          if($user->id == $channelCheck->userID)
          {
            $banCheck = DB::table('chanbans')->where('userID', '=', $userCheck->id)->where('channelID', '=', $channelCheck->id)->first();
            if(empty($banCheck))
            {
              DB::table('chanbans')->insert(array('userID' => $userCheck->id, 'channelID' => $channelCheck->id));

              //User banneds
              return Response::json(1)->setCallback($request->input('callback'));
            }
            else {
              DB::table('chanbans')->where('userID', '=', $userCheck->id)->where('channelID', '=', $channelCheck->id)->delete();

              //User unbanned
              return Response::json(2)->setCallback($request->input('callback'));
            }
          }
          else {
            //You do not own this channel
            return Response::json(3)->setCallback($request->input('callback'));
          }
        }
        else {
          //Channel not found
          return Response::json(4)->setCallback($request->input('callback'));
        }
      }
      else {
        //User not found
        return Response::json(5)->setCallback($request->input('callback'));
      }
    }
  }

  public function storeAvatar(Request $request)
  {
    $rules = array(
      'avatar'		=> 	'required'
    );
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return Response::json(0)->setCallback($request->input('callback'));
    } else {

      $user = Auth::user();
      $avatar = $request->file('avatar');

      $profile = User::find($user->id);

      if(File::size($avatar) > 5000000)
      {
        //Avatar too big
        return Response::json(2)->setCallback($request->input('callback'));
      }
      else {

        $imageFile = 'storage/media/avatars';

        if (!is_dir($imageFile)) {
          mkdir($imageFile,0777,true);
        }

        $fileName = $profile->name."-".str_random(8);
        $avatar->move($imageFile, $fileName.'.png');
        $avatar = $imageFile.'/'.$fileName.'.png';

        $img = Image::make($avatar);

        list($width, $height) = getimagesize($avatar);
        if($width > 150)
        {
          $img->resize(150, null, function ($constraint) {
              $constraint->aspectRatio();
          });
          if($height > 150)
          {
            $img->crop(150, 150);
          }
        }
        $img->save($avatar);

        $profile->avatar = 'http://api.technopathic.me/'.$avatar;
        $profile->save();

        //Avatar Saved
        return Response::json(1)->setCallback($request->input('callback'));
      }
    }
  }

  public function getFriends(Request $request)
  {
    $user = Auth::user();
    $friends = DB::table('friends')->where('friends.userID', '=', $user->id)->join('users', 'friends.friendID', '=', 'users.id')->select('friends.id', 'friends.friendID', 'users.name', 'users.avatar')->orderBy('friends.updated_at', 'ASC')->take(50)->get();

    return Response::json($friends)->setCallback($request->input('callback'));
  }

  public function storeFriend(Request $request, $id)
  {
    $friend = User::find($id);
    $user = Auth::user();
    if(!empty($friend))
    {
      if($friend->id == $user->id)
      {
        //You cannot friend yourself.
        return Response::json(4)->setCallback($request->input('callback'));
      }
      else {
        $friendCheck = DB::table('friends')->where('userID', '=', $user->id)->where('friendID', '=', $friend->id)->first();
        if(empty($friendCheck))
        {
          $checkBlock = DB::table('blocks')->where('userID', '=', $friend->id)->where('blockID', '=', $user->id)->first();
          if(empty($checkBlock))
          {
            DB::table('friends')->insert(array('userID' => $user->id, 'friendID' => $friend->id));

            //Friended
            return Response::json(1)->setCallback($request->input('callback'));
          }
          else {
            //This user has blocked you.
            return Response::json(2)->setCallback($request->input('callback'));
          }
        }
        else {
          DB::table('friends')->where('userID', '=', $user->id)->where('friendID', '=', $friend->id)->delete();

          //Unfriended
          return Response::json(3)->setCallback($request->input('callback'));
        }
      }
    }
    else {
      //Friend Not Found
      return Response::json(0)->setCallback($request->input('callback'));
    }
  }

  public function storeBlock(Request $request, $id)
  {
    $block = User::find($id);
    $user = Auth::user();
    if(!empty($block))
    {
      if($block->id == $user->id)
      {
        //You cannot block yourself
        return Response::json(3)->setCallback($request->input('callback'));
      }
      else {
        $blockCheck = DB::table('blocks')->where('userID', '=', $user->id)->where('blockID', '=', $block->id)->first();
        if(empty($blockCheck))
        {
          DB::table('blocks')->insert(array('userID' => $user->id, 'blockID' => $block->id));
          return Response::json(1)->setCallback($request->input('callback'));
          //Blocked
        }
        else {
          //Unblock.
          DB::table('blocks')->where('userID', '=', $user->id)->where('blockID', '=', $block->id)->delete();
          return Response::json(2)->setCallback($request->input('callback'));
        }
      }
    }
    else {
      //Block Not Found
      return Response::json(0)->setCallback($request->input('callback'));
    }
  }

  public function storeBan(Request $request, $id, $chanID)
  {
    $person = User::find($id);
    $user = Auth::user();
    $channel = Channel::find($chanID);
    if(!empty($person))
    {
      if(!empty($channel))
      {
        $ownerCheck = Channel::where('userID', '=', $user->id)->where('id', '=', $channel->id)->first();
        if(!empty($ownerCheck))
        {
          if($person->id == $user->id)
          {
            //You cannot ban yourself
            return Response::json(5)->setCallback($request->input('callback'));
          }
          else {
            $banCheck = DB::table('chanbans')->where('channelID', '=', $channel->id)->where('userID', '=', $person->id)->first();
            if(empty($banCheck))
            {
              DB::table('chanbans')->insert(array('channelID' => $channel->id, 'userID' => $person->id));

              $banData = array('userID' => $person->id, 'channelID' => $channel->id);
              $banChan = "banchan-".$channel->id;
              $pusher = new Pusher( '', '', '', array( 'encrypted' => true ) );

              $pusher->trigger($banChan, 'userBan', $banData);
              //User banned
              return Response::json(1)->setCallback($request->input('callback'));

            }
            else {
              $banCheck = DB::table('chanbans')->where('channelID', '=', $channel->id)->where('userID', '=', $person->id)->delete();
              //User unbanned
              return Response::json(0)->setCallback($request->input('callback'));

            }
          }
        }
        else {
          //You do not have the power to ban.
          return Response::json(2)->setCallback($request->input('callback'));
        }
      }
      else {
        //Channel not found
        return Response::json(3)->setCallback($request->input('callback'));
      }
    }
    else {
      //User not found
      return Response::json(4)->setCallback($request->input('callback'));
    }
  }
}
