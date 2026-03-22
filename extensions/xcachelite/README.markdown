# xCacheLite

xCacheLite is a lightweight full-page caching extension for Sym8, designed to provide predictable and safe cache behavior without relying on browser-side caching.

## Cache behavior

xCacheLite intentionally disables browser caching (`max-age=0`).

This ensures that users always receive fresh responses and avoids issues with stale content caused by aggressive browser caching.


## Installation

1. Upload the '`xcachelite`' directory to your Symphony 'extensions' directory.
2. Enable it by selecting the "xCacheLite", choose Enable from the `with-selected` menu, then click `Apply`.
3. Go to System -> Preferences and make the settings for the extension.
4. The output of your site will now be cached (not for logged in users).

## Usage

### Excluding pages

By default all pages are cached. You can exclude URLs from the cache by adding them to the list of excluded pages in System > Preferences. Each URL must sit on a separate line and wildcards (`*`) may be used at the end of URLs to match _everything_ below that URL.

Excluded pages are assumed to originate from the root. All the following examples will resolve to the same page (providing there are none below it in the hierarchy):

    /about-us/get-in-touch/*
    http://root.com/about-us/get-in-touch/
    about-us/get-in-touch*
    /about-us/get*

Note that caching is _not_ done for logged in users. This lets you add administrative tools to the frontend of your site without them being cached for normal users.

### Flushing the cache

Caching is done on a per-URL basis. To manually flush the cache for an individual page simply append `?flush` to the end of its URL. To flush the cache for the entire site you just need to append `?flush=site` to any URL in the site. You _must_ be logged in to flush the cache.

You can also remove cache files using your FTP client: navigate to `/manifest/cache` and remove the files named as `cache_{hash}`.

The cache of each page will automatically flush itself after the timeout period is reached. The timeout period can be modified on the System > Preferences page.

### Flushing the cache when content changes

In the Symphony Preferences for xCacheLite the option "Purge cache automatically when entries change (always enabled)" is already enabled and cannot be deactivated.

This means:

a) __When a brand new entry is created__, the cache will be flushed for any pages that show entries from _the entry's parent section_.
  For example if you have an Articles section which is used to display a list of recent article titles on the Home page; a list of articles on an Articles Index page; and another page to read an Article; the cache of all three pages will be flushed when a new Article entry is created.

b) __When an existing entry is edited__, the cache will be flushed for any pages that display _this entry_.
  In the above example, if the article being edited is very old and no longer features on the Home page or Articles Index page, only the specific instance of the Article view page for this entry will be flushed. Other Article view pages remain cached.

The same conditions are provided for frontend Events through the use of Event Filters. To add this functionality to your event, select one or all of the CacheLite event filters when configuring your event and trigger them using values in your HTML form:

a) __"CacheLite: expire cache for pages showing this entry"__
  When editing existing entries (one or many, supports the Allow Multiple option) any pages showing this entry will be flushed. Send the following in your form to trigger this filter:

    <input type="hidden" name="cachelite[flush-entry]" value="yes"/>

b) __CacheLite: expire cache for pages showing content from this section__
  This will flush the cache of pages using any entries from this event's _section_. Since you may want to only run it when creating new entries, this will only run if you pass a specific field in your HTML:

    <input type="hidden" name="cachelite[flush-section]" value="yes"/>

c) __CacheLite: expire cache for the passed URL__
  This allows you to selectively flush the cache during Event execution, which is useful if you want to expire the cache as new entries are added but don't want to flush the whole _section_. This filter will only run if you pass a specific field in your HTML:

    <input type="hidden" name="cachelite[flush-url]" value="/article/123/"/>

If you pass this field with no value, it will default to the _current_ URL. That is, from a page at <http://domain.tld/article/123/>, submitting the following:

    <input type="hidden" name="cachelite[flush-url]"/>

Would have the same result as the previous example.

### Cron job

Deleting lots of cache entries may make the backend slow.

If you feel like you are waiting too long for entries to save, it may be because deleting files on disk is slow.
You can change the cache invalidation strategy and use a cron job to purge the cache.

Note: The cron script is __CLI-only__ and must be executed via a server cron job.
HTTP-based cron services are intentionally not supported for security reasons.

You first need to rename the file `extensions/xcachelite/cron/rename_me.php` to e.g. `cron_abe45a64f6f5e5871.php`.

Then, you need to configure the cron job in Server Management for the vHost that will purge the cache in the background, e.g. every two hours

    0 */2 * * * php /path/to/website/extensions/cachelite/cron/cron_abe45a64f6f5e5871.php

If the cron job has been set up for the vHost in Server Management, the file `cronjob.log` will contain lines like the following:

    Thu, 19 Mar 2026 08:00:36 +0000 [xCacheLite] Please enable this cron job in the Symphony Preferences.

This indicates that the cron job is set up correctly and is running without errors.

Next, you need to enable the cron job in the settings. To do this, go to System -> Preferences and under xCacheLite ticking the checkbox "Enable additional cache cleanup via cron job".

After that, the following entries will be written to the log file after each execution:

    Thu, 19 Mar 2026 15:00:56 +0000 [xCacheLite] Execution of this cron job granted by Symphony Preferences.
    Thu, 19 Mar 2026 15:00:56 +0000 [xCacheLite] Deleted 11 cache files

This checkbox allows you to easily disable/enable the cron job without having to delete the cron job itself in Server Management.

### Bypassing the cache

Extensions can tell cachelite to bypass the cache on particular requests. Extensions must implement the `CacheliteBypass` delegate.

The delegate is as follows:

```php
/**
 * Allows extensions to make this request
 * bypass the cache.
 *
 * @delegate CacheliteBypass
 * @since 2.0.0
 * @param string $context
 *  '/frontend/'
 * @param bool $bypass
 *  A flag to tell if the user is logged in and cache must be disabled
 */
Symphony::ExtensionManager()->notifyMembers('CacheliteBypass', '/frontend/', array(
    'bypass' => &$cachebypass,
));
```

By changing the value of `$context['bypass']` to `true` the request will not use the cache.
