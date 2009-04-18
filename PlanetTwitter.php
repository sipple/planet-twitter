<?php

    /*
    Plugin Name: PlanetTwitter
    Description: Pulls in a feed based on a search term passed to it using
                this format: [planettwitter:searchterm]
    Version: 1.1
    Author: Eric Sipple & Brennen Bearnes
    Author URI: http://saalonmuyo.com
    */
    
    /*  Copyright 2009  Eric Sipple  (email : saalon@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    */

    // Sample:
    // print get_planet_money_chatter ('[planetTwitter:planetmoney]');
    
    /**
     * This function receives text and if that text contains '[planettwitter]'
     * it replaces that text with a feed of the top 15 tweets containing
     * the word 'planetmoney'
     */
    function get_planet_money_chatter($text)
    {
        // If we don't have a [planettwitter:...], then bail out:
        $pattern = '/\[planettwitter:(.*?)\]/i';
        if (! preg_match($pattern, $text, $matches))
            return $text;

        $toReplace    = $matches[0];
        $searchString = urlencode($matches[1]);

        // ...otherwise, if $text contains [planettwitter] then stick in the feed:

        // We're going to cache our response for better performance
        $cacheFile = md5($searchString) . '.json';
        
        // Get the last modified date of the cache file, and the current time
        $last = @filemtime($cacheFile);
        $now = time();
        
        /* If the cache file is more than 60 seconds old, replace it with
           a fresh JSON file of the top tweets containing the search term. */
        if (!$last || ($now - $last) > 60)
        {
            $ch = curl_init("http://search.twitter.com/search.json?q={$searchString}");
            $fp = fopen($cacheFile, "w");
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }
    
        // Get contents of the cache file:
        $buffer = @file_get_contents($cacheFile);

        // Bail out if we haven't got anything:
        if (! strlen($buffer))
            return $text;
        
        // Let JSON.php decode the JSON into an object so we can parse it.
        $searchResults = json_decode($buffer);

        // Set up our string format templates
        $img             = '<img src="%s"/>';
        $bold            = '<b>%s</b>';
        $fromUser        = '<a href="http://www.twitter.com/%s"><b>%s</b></a> ';
        $planetMoneyFeed = ''; 
        
        // For each response we found, parse it into something pretty
        foreach ($searchResults->results as $result)
        {
            // Replace any @replies or links with hrefs so they link to
            // the right places.
            $twitterText = $result->text;
            $twitterText = preg_replace("#(^|[\n ])@([^ \'\"\t\n\r<]*)#ise", "'\\1@<a href=\"http://www.twitter.com/\\2\" >\\2</a>'", $twitterText);
            $twitterText = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t<]*)#ise", "'\\1<a href=\"\\2\" >\\2</a>'", $twitterText);
            $twitterText = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://\\2\" >\\2</a>'", $twitterText); 
            
            // This gets the profile image, the from user and the created date
            // and sticks it into HTML to make it look attractive.
            $planetMoneyFeed .= '<div style="padding: 5 5 5 5; width: 400px; border-bottom:1px dashed #CCCCCC; clear: left;">'
                              . '<div style="padding: 0 5 0 5; float: left;">'
                              . sprintf($img, $result->profile_image_url)
                              . '</div>'
                              . '<div style="padding: 0 5 0 5; display: block;">'
                              . sprintf($fromUser,$result->from_user, $result->from_user)
                              . $twitterText
                              . '<span style="font-size:0.8em; text-align:right; font-style:italics; font-family:georgia; color:#999999;">'
                              . ' - at ' . $result->created_at
                              . '</div>'
                              . '</span>'
                              . '</div>';
        }
        
        $text = str_replace($toReplace, $planetMoneyFeed, $text);
        return $text;
    }

    // This is here so it can be used as a Wordpress Plugin.  To use it elsewhere
    // and just call the above function, comment this out.
    add_filter('the_content', 'get_planet_money_chatter');
?>
