# JustAuth

Simple authentication library.

It supports registration, login and forgotten passwords. You can use password based or password-less auth.
Password-less auth works with 

## Installation

### Composer
  composer require justso/justauth:1.*

### git
  git clone git://github.com/JustsoSoftware/JustAuth.git vendor/justso/justauth
  
## Setup

In config.json you can set the following entries:

{
  "auth": {
    "auto-register": true,
    "needs-activation": true,
    "login-new-users": true
  },
}
```

Auto-registering means, that users who give a new e-mail address are automatically registered as new users. A password
may be specified and an activation link is sent. If auto-registering is not enabled, only already registered users can
login, meaning that those users have to be registered otherwise (outside of this library).

With 'needs-activation' you specify that activating new accounts with the link sent by e-mail is required to log in.
If this option is set to false, all new users are already activated, they may use their credentials immediately to
log in. This option only makes sense with 'auto-register' option set true.

If you set "login-new-users", newly registered users are logged in automatically. Activation may still be necessary to
log in later (in a new session), if 'needs-activation' is also requested. This option only makes sense with
'auto-register' option set true.

## Support & More

If you need support, please contact us: http://justso.de
