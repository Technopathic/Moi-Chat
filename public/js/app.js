angular.module('moi', ['ui.router', 'ngMaterial', 'angularMoment', 'ngMessages', 'ngSanitize', 'pusher-angular', 'luegg.directives', 'ngFileUpload', 'moi.controllers'])

.run(['$rootScope', '$state', '$interval', '$http', function($rootScope, $state, $interval, $http) {
  $rootScope.$on('$stateChangeStart', function(event, toState) {
    var user = JSON.parse(localStorage.getItem('user'));
    var token = JSON.parse(localStorage.getItem('token'));
    if(user && token) {
      $rootScope.authenticated = true;
      $rootScope.currentUser = user;
      $rootScope.currentToken = token;
      if(toState.name === "auth") {
        event.preventDefault();
        $state.go('main.home');
      }
    }
  });

  if($rootScope.authenticated === true) {
    $interval(function() {
      $http.jsonp('https://api.technopathic.me/api/refreshToken?token='+$rootScope.currentToken+'&callback=JSON_CALLBACK')
      .success(function(data) {
        localStorage.setItem('token', data);
        $rootScope.currentToken = data;
      });
    }, 3600000);
  }
}])

.config(['$stateProvider', '$mdThemingProvider', '$urlRouterProvider', '$locationProvider', function($stateProvider, $mdThemingProvider, $urlRouterProvider, $locationProvider) {
  $locationProvider.html5Mode(true);
  $mdThemingProvider.theme('default').primaryPalette('teal');

  $stateProvider

  .state('main', {
    templateUrl: 'views/main.html',
    controller: 'MainCtrl',
    abstract: true
  })

  .state('main.home', {
    url: '/',
    templateUrl: 'views/home.html',
    controller: 'HomeCtrl'
  })

  $urlRouterProvider.otherwise('/');


}]);
