##Description

Moi is a Local Area Chat application built using Laravel, AngularJS, and Pusher. Public channels are automatically created for each town or city, which can be freely viewed by anybody. To take part in the conversation and access other channels, a user must sign up. Users can also make password protected private channels and invite others. There is also a friend system available which allows users to stay in touch with each other and send private messages.

##Screenshot

![Screenshot](http://h4z.it/Image/850efc_oiscreenshot.PNG)

##Demo
Coming Soon...

##Installation
Moi is not particularly ready right out of the box, there are several configurations which must be made before Moi can be deployed.

#Requirements
- PHP5.6+
- MySQL
- Composer
- NodeJS + Bower
- A Pusher Account

##Instructions
First, ensure that you have all of the above installed and ready on your server. Next, download/clone this git repo. In the repo, run `composer install` to install the dependencies for Laravel. Next, we need to set permissions with `chmod 0777 -R storage` and `chmod 0777 -R public/storage`. Once you've done that, you will edit the .env file with your own database and site information. Run `php artisan key:generate` and `php artisan jwt:generate` to generate the necessary encryption keys. Run `bower install` to install of the Javascript packages we need for the front-end.

To add your Pusher information, Open app/Http/Controllers/MessagesController.php. Search for "Pusher" and you'll come across to Functions that use the Pusher Initialization. Within the '' input your App Key, Secret Key, and App ID, in that order for both.

That's it, you should be ready to go.

##Final Notes
Moi uses Geolocation sent from the User's browser. Google Chrome does not allow Geolocation to work unless your website is running on https. I recommend using LetsEncrypt to get an SSL certificate for your website. Maybe in the future I'll add an IP address fallback. I also might make it easier to add pusher information by using the Pusher Laravel library, will check to see how viable that is first.


I built Moi in a span of ~two days, mainly as a mobile application but turned web app. There will be bugs and it could use a lot of improvement. Post any issues or questions on the Issues page on this repo.

##ToDo
- Add Spam protection
- Make add Pusher configs easier
- Image Uploading in Chat
- Link/Image Previews
- User profiles
- Who's Online List
- Notifications with/without sounds

##License
MIT
