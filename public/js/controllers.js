angular.module('moi.controllers', [])

.directive('ngEnter', function() {
    return function(scope, element, attrs) {
        element.bind("keydown", function(e) {
            if(e.which === 13) {
                scope.$apply(function(){
                    scope.$eval(attrs.ngEnter, {'e': e});
                });
                e.preventDefault();
            }
        });
    };
})

.controller('MainCtrl', ['$scope', '$state', '$http', '$rootScope', '$mdDialog', '$mdToast', '$mdSidenav', function($scope, $state, $http, $rootScope, $mdDialog, $mdToast, $mdSidenav) {

  $scope.loaded = false;
  $rootScope.auths = null;
  $rootScope.blocks = [];
  $scope.mHidden = [];

  var originatorEv = null;

  $scope.notifyToast = function(message) {
    $mdToast.show(
      $mdToast.simple()
        .textContent(message)
        .position('bottom left')
        .hideDelay(3000)
    );
  };

  $scope.openMenu = function($mdOpenMenu, ev) {
    originatorEv = ev;
    $mdOpenMenu(ev);
  };

  $scope.authDialog = function(ev) {
    $mdDialog.show({
      templateUrl: 'views/templates/auth-Dialog.html',
      parent: angular.element(document.body),
      targetEvent: ev,
      scope:$scope.$new(),
      clickOutsideToClose:true,
      controller: 'AuthCtrl'
    });
  };

  $scope.avatarDialog = function(ev) {
    $mdDialog.show({
      templateUrl: 'views/templates/avatar-Dialog.html',
      parent: angular.element(document.body),
      targetEvent: ev,
      scope:$scope.$new(),
      clickOutsideToClose:true,
      controller: 'AvatarCtrl'
    });
  };

  $scope.dialogClose = function() {
    $mdDialog.hide();
  };

  $scope.signOut = function() {
   localStorage.removeItem('user');
   $rootScope.authenticated = false;
   $rootScope.currentUser = null;
   $rootScope.currentToken = null;
   $rootScope.auths = null;
   $scope.notifyToast('Bye for now! Hope to see you again soon!');
 };

 $scope.toggleChannels = function() {
   $mdSidenav('left').toggle()
 };

 $scope.getBlocks = function()
 {
   $http.jsonp('https://api.technopathic.me/api/getBlocks?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
   .success(function(data) {
     $rootScope.blocks = data;
   });
 };

}])

.controller('AvatarCtrl', ['$scope', '$state', '$http', '$rootScope', '$mdDialog', '$mdToast', 'Upload', function($scope, $state, $http, $rootScope, $mdDialog, $mdToast, Upload) {
  $scope.avatarData = {};

  $scope.storeAvatar = function()
  {
    Upload.upload({
      url: 'https://api.technopathic.me/api/storeAvatar?token='+$rootScope.currentToken,
      data: {
        avatar: $scope.avatarData.avatar,
      },
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function(data){
      if(data == 0)
      {
        $scope.notifyToast("Nothing to upload.");
      }
      else if(data == 2)
      {
        $scope.notifyToast("Avatar was too big.");
      }
      else
      {
        $scope.notifyToast("Avatar Uploaded.");
        $rootScope.currentUser.avatar = data;
        $mdDialog.hide();
      }
    }).error(function(data) {
      $scope.notifyToast("Could not upload your file.");
    });
  };
}])

.controller('AuthCtrl', ['$scope', '$state', '$http', '$rootScope', '$mdDialog', '$mdToast', '$stateParams', function($scope, $state, $http, $rootScope, $mdDialog, $mdToast, $stateParams) {

  $scope.signIn = {};
  $scope.signUp = {};

  $scope.state = $state.current.name;

  $scope.notifyToast = function(message) {
    $mdToast.show(
      $mdToast.simple()
        .textContent(message)
        .position('bottom left')
        .hideDelay(3000)
    );
  };

  $scope.getAuths = function()
  {
    $http.jsonp('https://api.technopathic.me/api/getAuths?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
    .success(function(data) {
      $rootScope.auths = data;
      $scope.getBlocks();
      $mdDialog.hide();
    });
  };

  $scope.dialogClose = function() {
    $mdDialog.hide();
  };


  $scope.doSignUp = function() {
    $http({
        method: 'POST',
        url: 'https://api.technopathic.me/api/signUp',
        data: {email: $scope.signUp.email, username: $scope.signUp.username, password: $scope.signUp.password, location:$rootScope.location, region:$rootScope.region},
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function(data){
      if(data == 0)
      {
        $scope.notifyToast('Please fill out the fields.');
      }
      if(data == 2)
      {
        $scope.notifyToast('That Email is already Registered.');
      }
      else if(data == 3)
      {
        $scope.notifyToast('That Username is already Registered.');
      }
      else if(data == 5)
      {
        $scope.notifyToast('Registration is not allowed.');
      }
      else if(data == 6)
      {
        $scope.notifyToast('Success! Please check your email.');
        $mdDialog.hide();
      }
      else if(data == 1)
      {
        $scope.notifyToast('Successful Sign Up! Life is Great!');

        $http({
            method: 'POST',
            url: 'api/authenticate',
            data: {email: $scope.signUp.email, password: $scope.signUp.password, region:$rootScope.region, location:$rootScope.location},
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).success(function(data) {
          if(data == 3)
          {
            $scope.notifyToast('Please check your email and confirm your account.');
          }
          else {
            var token = JSON.stringify(data.token);
            localStorage.setItem('token', token);
            $rootScope.currentToken = data.token;
            $http.get('api/authenticate/user?token='+ data.token)
            .success(function(data) {
              var user = JSON.stringify(data.user);
              localStorage.setItem('user', user);
              $rootScope.authenticated = true;
              $rootScope.currentUser = data.user;
              $scope.getAuths();
            });
          }
        });
      }
    });
  };

  $scope.doAuth = function() {
    $http({
        method: 'POST',
        url: 'api/authenticate',
        data: {email: $scope.signIn.email, password: $scope.signIn.password, region:$rootScope.region, location:$rootScope.location},
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    })
    .success(function(data) {
      if(data == 2)
      {
        $scope.notifyToast('Wrong Email!');
      }
      else if(data == 0)
      {
        $scope.notifyToast('Sorry, looks like you were banned.');
      }
      else
      {
        var token = JSON.stringify(data.token);
        localStorage.setItem('token', token);
        $rootScope.currentToken = data.token;
        $http.get('api/authenticate/user?token='+ data.token)
        .success(function(data) {
          var user = JSON.stringify(data.user);

          localStorage.setItem('user', user);
          $rootScope.authenticated = true;
          $rootScope.currentUser = data.user;
          $scope.notifyToast('Welcome Back, '+data.user.name+'!');
          $scope.getAuths();
        });
      }
    }).error(function(data) {
      $scope.notifyToast('Login Incorrect.');
    });
  };

}])



.controller('HomeCtrl', ['$scope', '$rootScope', '$state', '$http', '$mdDialog', '$mdSidenav', '$timeout', '$pusher', '$mdBottomSheet', function($scope, $rootScope, $state, $http, $mdDialog, $mdSidenav, $timeout, $pusher, $mdBottomSheet) {

  $scope.showPrivate = false;
  $scope.activeChannel = null;
  $scope.channels = {};
  $scope.messages = {};
  $scope.friends = {};
  $scope.cNotifications = [];
  $scope.fNotifications = [];

  $scope.messageData = {};

  $scope.allowAuth = 0;

  $scope.chatType = 'Channel';

  var client = new Pusher('15b30ea0c84d5800e0d4');
  var pusher = $pusher(client);

  $scope.getLocation = function()
  {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        $rootScope.lat = position.coords.latitude;
        $rootScope.lon = position.coords.longitude;
        $scope.getChannels(position.coords.latitude, position.coords.longitude);
      });
    }
    else {
      $scope.notifyToast("Could not retrive location.");
    }
  };

  $scope.getAuths = function()
  {
    $http.jsonp('https://api.technopathic.me/api/getAuths?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
    .success(function(data) {
      $rootScope.auths = data;
      $scope.getBlocks();
    }).error(function(data)
    {
      $scope.signOut();
      $scope.notifyToast("Session expired. Please relog.");
    });
  };

  $scope.refreshChannels = function()
  {
    $http.jsonp('https://api.technopathic.me/api/refreshChannels?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
    .success(function(data) {
      $scope.channels = data;
    }).error(function(data)
    {
      $scope.signOut();
      $scope.notifyToast("Session expired. Please relog.");
    });
  }

  $scope.getMessages = function(id, lock, userID)
  {
    if(userID == 0)
    {
      $http.jsonp('https://api.technopathic.me/api/getMessages/'+id+'?callback=JSON_CALLBACK')
      .success(function(data) {
        if(data == 5)
        {
          $scope.notifyToast("Channel not found.");
        }
        else {
          $scope.messages = data.messages;
          $scope.activeChannel = data.activeChannel;
          var bindChannel = pusher.subscribe('channel-'+$scope.activeChannel.channelID);
          bindChannel.bind('messageSend', function(data) {
            $scope.messages.data.push(data);
          });
          $scope.$parent.loaded = true;
        }
      }).error(function(data)
      {
        $scope.signOut();
        $scope.notifyToast("Session expired. Please relog.");
      });
    }
    else
    {
      if($rootScope.authenticated != true)
      {
        $scope.notifyToast("You must be logged in to use Channels.");
      }
      else {
        if(lock === 0)
        {
          $http.jsonp('https://api.technopathic.me/api/showChannel/'+id+'?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
          .success(function(data) {
            if(data == 2)
            {
              $scope.notifyToast("You cannot access this channel.");
            }
            else if(data == 3)
            {
              $scope.notifyToast("You were banned.");
            }
            else if(data == 4)
            {
              $scope.notifyToast("You must be logged in to use channels.");
            }
            else if(data == 5)
            {
              $scope.notifyToast("Channel not found.");
            }
            else {
              $scope.messages = data.messages;
              $scope.activeChannel = data.activeChannel;
              var bindChannel = pusher.subscribe('channel-'+$scope.activeChannel.channelID);
              bindChannel.bind('messageSend', function(data) {
                $scope.messages.data.push(data);
              });
              $scope.$parent.loaded = true;
            }
          }).error(function(data)
          {
            $scope.signOut();
            $scope.notifyToast("Session expired. Please relog.");
          });
        }
        else {
          if($rootScope.auths.indexOf(id) !== -1)
          {
            $http.jsonp('https://api.technopathic.me/api/showChannel/'+id+'?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
            .success(function(data) {
              if(data == 2)
              {
                $scope.notifyToast("You cannot access this channel.");
              }
              else if(data == 3)
              {
                $scope.notifyToast("You were banned.");
              }
              else if(data == 4)
              {
                $scope.notifyToast("You must be logged in to use channels.");
              }
              else if(data == 5)
              {
                $scope.notifyToast("Channel not found.");
              }
              else {
                $scope.messages = data.messages;
                $scope.activeChannel = data.activeChannel;
                var bindChannel = pusher.subscribe('channel-'+$scope.activeChannel.channelID);
                bindChannel.bind('messageSend', function(data) {
                  $scope.messages.data.push(data);
                });
                $scope.$parent.loaded = true;
              }
            }).error(function(data)
            {
              $scope.signOut();
              $scope.notifyToast("Session expired. Please relog.");
            });
          } else {
            $scope.allowAuth = 0;
            $mdDialog.show({
              templateUrl: 'views/templates/channelAuth-Dialog.html',
              parent: angular.element(document.body),
              scope:$scope.$new(),
              preserveScope:true,
              clickOutsideToClose:true,
              controller: 'DialogCtrl',
              locals:{channelID:id}
            }).finally(function() {
              if($scope.allowAuth === 1)
              {
                $rootScope.auths.push(id);
                $scope.getMessages(id, 0, userID);
              }
            });
          }
        }
      }
    }
  };

  $scope.getChannels = function(lat, lon)
  {
    $http.jsonp('https://api.technopathic.me/api/getChannels/'+lat+'&'+lon+'?callback=JSON_CALLBACK')
    .success(function(data) {
      $scope.channels = data.channels;
      $rootScope.location = data.location;
      $rootScope.region = data.region;
      $scope.getMessages(data.public.id, 0, 0);
    }).error(function(data) {
      if($rootScope.lat === undefined || $rootScope.lon === undefined)
      {
        $scope.getLocation();
        $timeout(function() { $scope.getChannels(lat, lon); }, 5000);
      }
      else {
        $scope.getChannels(la, lon);
      }
    });
  };

  $scope.createChannel = function(ev)
  {
    $mdDialog.show({
      templateUrl: 'views/templates/createChannel-Dialog.html',
      parent: angular.element(document.body),
      targetEvent: ev,
      scope:$scope.$new(),
      clickOutsideToClose:true,
      controller: 'DialogCtrl',
      locals:{channelID:""}
    });
  };

  $scope.sendMessage = function(type)
  {
    if(type == "Channel")
    {
      $scope.storeMessage();
    }
    if(type == "Friend")
    {
      $scope.storeFmessage();
    }
  };

  $scope.storeMessage = function()
  {
    if($rootScope.currentToken)
    {
      $http({
        method: 'POST',
        url: 'https://api.technopathic.me/api/storeMessage?token='+$rootScope.currentToken,
        data: {messageBody: $scope.messageData.messageBody, channelID: $scope.activeChannel.channelID, dateTime: getDateTime()},
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).success(function(data){
        if(data == 0)
        {
          $scope.notifyToast("You cannot post empty messages.");
        }
        else if(data == 3)
        {
          $scope.notifyToast("You must be logged in to send messages.");
        }
        else if(data == 4)
        {
          $scope.notifyToast("You were banned from this channel.");
        }
        else if(data == 1)
        {
          $scope.messageData.messageBody = null;
        }
        else if(data == 5)
        {
          $scope.ontifyToast("Your message was too long.");
        }
      }).error(function(data) {
        if(data.error == "token_not_provided")
        {
          $scope.notifyToast("You must be logged in to send messages.");
          $scope.$parent.authDialog();
        }
      });
    } else {
      $scope.notifyToast("You must be logged in to chat.");
    }
  };

  $scope.storeFmessage = function()
  {
    if($rootScope.currentToken)
    {
      $http({
        method: 'POST',
        url: 'https://api.technopathic.me/api/storeFmessage?token='+$rootScope.currentToken,
        data: {messageBody: $scope.messageData.messageBody, friendID: $scope.activeChannel.channelID, dateTime: getDateTime()},
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
      }).success(function(data){
        if(data == 0)
        {
          $scope.notifyToast("You cannot post empty messages.");
        }
        else if(data == 3)
        {
          $scope.notifyToast("You must be logged in to send messages.");
        }
        else if(data == 4)
        {
          $scope.notifyToast("You were blocked.");
        }
        else if(data == 1)
        {
          $scope.messageData.messageBody = null;
        }
        else if(data == 5)
        {
          $scope.ontifyToast("Your message was too long.");
        }
      }).error(function(data) {
        if(data.error == "token_not_provided")
        {
          $scope.notifyToast("You must be logged in to send messages.");
          $scope.$parent.authDialog();
        }
      });
    } else {
      $scope.notifyToast("You must be logged in to chat.");
    }
  };

  $scope.getFriends = function()
  {
    $http.jsonp('https://api.technopathic.me/api/getFriends?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
    .success(function(data) {
      $scope.friends = data;
    });
  };

  $scope.getFmessages = function(id)
  {
    $http.jsonp('https://api.technopathic.me/api/getFmessages/'+id+'?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
    .success(function(data) {
      if(data == 5)
      {
        $scope.notifyToast("Friend not found.");
      }
      else if(data == 3)
      {
        $scope.notifyToast("You were blocked.");
      }
      else if(data == 2)
      {
        $scope.notifyToast("This user has not friended you yet.");
      }
      else {
        $scope.messages = data.messages;
        $scope.activeChannel = data.activeChannel;
        var bindChannel = pusher.subscribe('friend-'+$scope.activeChannel.channelID);
        bindChannel.bind('fmessageSend', function(data) {
          $scope.messages.data.push(data);
        });
      }
    }).error(function(data)
    {
      $scope.signOut();
      $scope.notifyToast("Session expired. Please relog.");
    });
  }

  $scope.bottomMenu = function(userID, messageID) {
   if($rootScope.authenticated == true)
   {
     $mdBottomSheet.show({
       templateUrl: 'views/templates/bottomMenu-Sheet.html',
       escapeToClose: true,
       clickOutsideToClose: true,
       scope:$scope.$new(),
       locals: {UserData: {user:userID, channel:$scope.activeChannel.channelID, message:messageID}},
       controller:'bottomMenuCtrl'
      });
   }
   else {
     $scope.notifyToast("Please Login.");
   }
  };

  $scope.changeType = function(type)
  {
    $scope.chatType= type;
  };

  function getDateTime() {
    var now     = new Date();
    var year    = now.getFullYear();
    var month   = now.getMonth()+1;
    var day     = now.getDate();
    var hour    = now.getHours();
    var minute  = now.getMinutes();
    var second  = now.getSeconds();
    if(month.toString().length == 1) {
      var month = '0'+month;
    }
    if(day.toString().length == 1) {
      var day = '0'+day;
    }
    if(hour.toString().length == 1) {
      var hour = '0'+hour;
    }
    if(minute.toString().length == 1) {
      var minute = '0'+minute;
    }
    if(second.toString().length == 1) {
      var second = '0'+second;
    }
    var dateTime = year+'-'+month+'-'+day+' '+hour+':'+minute+':'+second;
    return dateTime;
  }

  $scope.getLocation();
  if($rootScope.authenticated == true)
  {
    $scope.getAuths();
  }

}])

.controller('DialogCtrl', ['$scope', '$rootScope', '$state', '$http', '$mdDialog', 'channelID', function($scope, $rootScope, $state, $http, $mdDialog, channelID) {

  $scope.channelData = {};
  $scope.authData = {};

  $scope.dialogClose = function() {
    $mdDialog.hide();
  };

  var channelID = channelID;

  $scope.storeChannel = function()
  {
    $http({
      method: 'POST',
      url: 'https://api.technopathic.me/api/storeChannel?token='+$rootScope.currentToken,
      data: {channelName: $scope.channelData.channelName, channelPassword: $scope.channelData.channelPassword, channelLock: $scope.channelData.channelLock},
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function(data){
      if(data == 3)
      {
        $scope.notifyToast("You have too many channels.");
      }
      else if(data == 4)
      {
        $scope.notifyToast("Password was too long.");
      }
      else if(data == 5)
      {
        $scope.notifyToast("Channel Name was too long");
      }
      else if(data == 2)
      {
        $scope.notifyToast("Please sign in to create a channel.");
      }
      else {
        $scope.channels.push(data);
        $mdDialog.hide();
      }
    });
  };

  $scope.chanAuth = function()
  {
    $http({
      method: 'POST',
      url: 'https://api.technopathic.me/api/chanAuth?token='+$rootScope.currentToken,
      data: {channelID: channelID, channelPassword: $scope.authData.channelPassword},
      headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).success(function(data){
      if(data == 2)
      {
        $scope.notifyToast("Incorrect Password.");
      }
      else if(data == 3)
      {
        $scope.notifyToast("Channel not found.");
      }
      else if(data == 4)
      {
        $scope.notifyToast("Please sign in to view channels.");
      }
      else if(data == 1)
      {
        $scope.$parent.allowAuth = data;
        $mdDialog.hide();
      }
    });
  }
}])

.controller('bottomMenuCtrl', ['$scope', '$state', '$http', '$rootScope', '$mdDialog', '$mdBottomSheet', '$mdToast', 'UserData', function($scope, $state, $http, $rootScope, $mdDialog, $mdBottomSheet, $mdToast, UserData) {

  var user = UserData.user;
  var channel = UserData.channel;
  var message = UserData.message;

  $scope.storeFriend = function()
  {
    $http.jsonp('https://api.technopathic.me/api/storeFriend/'+user+'?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
    .success(function(data) {
      if(data == 1)
      {
        $scope.notifyToast("Friend Added.");
      }
      else if(data == 2)
      {
        $scope.notifyToast("This user has you blocked.");
      }
      else if(data == 3)
      {
        $scope.notifyToast("Friend removed.");
      }
      else if(data == 4)
      {
        $scope.notifyToast("You cannot friend yourself.");
      }
      $mdBottomSheet.hide();
    });
  };

  $scope.storeBan = function()
  {
    $http.jsonp('https://api.technopathic.me/api/storeBan/'+user+'/'+channel+'?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
    .success(function(data) {
      if(data == 1)
      {
        $scope.notifyToast("User banned.");
      }
      else if(data == 0)
      {
        $scope.notifyToast("User unbanned.");
      }
      else if(data == 2)
      {
        $scope.notifyToast("You do not own this channel.");
      }
      else if(data == 5)
      {
        $scope.notifyToast("You cannot ban yourself.");
      }
      $mdBottomSheet.hide();
    });
  };

  $scope.storeBlock = function()
  {
    $http.jsonp('https://api.technopathic.me/api/storeBlock/'+user+'?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
    .success(function(data) {
      if(data == 1)
      {
        $scope.notifyToast("User blocked.");
      }
      else if(data == 2)
      {
        $scope.notifyToast("User unblocked.");
      }
      else if(data == 3)
      {
        $scope.notifyToast("You cannot block yourself.");
      }
      $mdBottomSheet.hide();
    });
  };

  $scope.hideMessage = function()
  {
    $scope.$parent.mHidden.push(message);
  };

}])
