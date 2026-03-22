<?php 
/**
 * Copyright: Deux Huit Huit 2013
 * Copyright: Sym8 since 2025
 * License: MIT, http://deuxhuithuit.mit-license.org
 */

class CacheManagement
{
    public static function deleteFileCache()
    {
        return self::purgeFileCache(false);
    }

    public static function purgeFileCache($expiredOnly = true, $filter = null, $subdirectory = '')
    {
        $count = 0;
        $files = General::listStructure(CACHE . $subdirectory, $filter, false);

        if (!empty($files)) {
            foreach ($files['filelist'] as $file) {
                if (!$expiredOnly || filemtime($file) < time() - (60 * 60)) {
                    if (General::deleteFile($file, true)) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }

    /** Database */
    public static function deleteDBCache()
    {
        $count = self::getCacheCount();
        Symphony::Database()->delete('tbl_cache', '1=1');
        return $count;
    }

    public static function purgeDBCache()
    {
        $count = self::getCacheCount();
        $cache = new Cacheable(Symphony::Database());
        $cache->clean();
        return $count - self::getCacheCount();
    }

    private static function getCacheCount()
    {
        return Symphony::Database()->fetchVar('c', 0, 'SELECT count(*) as `c` FROM tbl_cache');
    }
}
