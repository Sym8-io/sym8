![PHP 7 passing](https://img.shields.io/badge/build-passing-brightgreen?style=flat-square&logo=php&logoColor=green&label=PHP%207) ![PHP 8 passing](https://img.shields.io/badge/build-passing-brightgreen?style=flat-square&logo=php&logoColor=green&label=PHP%208)

# Anti Brute Force #

> Secure your Symphony backend against brute force and dictionary attacks

Prevents ___people and softwares___ to brute force your authors/developers accounts.

## Specs

- After __x__ failed attempt, the IP address will be banned for __y__ min;
  (__x__ and __y__ are settings in the preferences page)
- Features colored list: ___Black list___, __Gray list__, _White list_.
- Features a __unban via email__ capabilities; Must be enabled in the preferences page
- Backend content page for managing blocked IPs and colored lists
- A Facade/Singleton class -ABF- for developers to leverage anti_brute_force capabilities
  (ex.: email reports or use with the member extension)

## Notes about proxies

If you are using Symphony on a server that sits behind a proxy, it will always
track 127.0.0.1 (or your proxy's IP) as remote address, simply because PHP doesn't see anything else
in `$_SERVER['REMOTE_ADDR']`. In order to fix this, please set the 'remote-addr-key'
setting to the field set by your proxy in order to let ABF access the real user IP.
You can also set this value in Symphony's settings backend page.

Most proxies will set the 'HTTP_X_FORWARDED_FOR' field with the respective user's IP
but some other provider (such as CloudFlare) will create a custom field. Your best bet
would be to do some actual penetration testing to be sure ABF works properly.

## Compatibility

The extension is compatible with legacy Symphony CMS installations as well as Sym8.

## Installation

- `git clone` / download and unpack the zip file
- (re)Name the folder __anti_brute_force__
- Put into the extension directory
- Enable/install just like any other extension
- (optional) Go to the _Preferences_ page to customize settings
    - Maximum failed count before user gets banned
    - Banned duration - number of minutes IP is banned
    - Gray list threshold - maximum number of gray list entries before black list
    - Gray list duration - in days - before expire
    - Unban via email - Enables/disable this feature
    - Restrict access from authors - Hide/Show ABF content page to Authors
    - Remote IP address field name - The `getenv()` field to look for the client's IP.
- (optional) See all the banned IPs via Anti Brute Force -> Banned IPs
- (optional) Manage colored lists entries via Anti Brute Force -> Black/Gray/White list
- (optional) Configure a cron job to run `cron/remove_expired.php` at regular interval

## Cron job

__Note__: The cron script is __CLI-only__ and must be executed via a server-side cron job as a PHP CLI script.
HTTP-based cron services are intentionally not supported for security reasons.

Orphaned and expired banned IP entries on the banned IP list can be easily cleaned up and removed using a cron job.

Set up the cron job in the server management of your vHost, for example, once a day:

```
0 0 * * * php /path/to/website/extensions/anti_brute_force/cron/remove_expired.php
```

After that, the following entry will be written to the log file after each execution:

```
Fri, 05 Jun 2026 10:00:28 +0000 [ABF] cleanup completed.
```

## License

[MIT](https://github.com/Sym8-io/anti_brute_force?tab=License-1-ov-file)
