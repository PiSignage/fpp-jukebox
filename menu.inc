<?php
# menu.inc — Jukebox
# FPP reads this file from each plugin directory and injects matching entries
# into the nav dropdown for the current menu section ($menu variable).

$menuEntries = Array(
    Array(
        'text' => 'Jukebox - Stats',
        'type' => 'status',
        'page' => 'stats.php',
        'wrap' => 1
    ),
    Array(
        'text' => 'Jukebox - Config',
        'type' => 'content',
        'page' => 'config.php',
        'wrap' => 1
    ),
    Array(
        'text' => 'Jukebox - Help',
        'type' => 'help',
        'page' => 'help.php',
        'wrap' => 1
    )
);

##############################################################################
# Display the menu entries for this plugin.
#
# It is expected that two variables are already set:
# $plugin - contains the name of the current plugin directory/repoName
# $menu - contains the name of the menu section/type
foreach ($menuEntries as $entry)
{
    if ($entry['type'] != $menu)
        continue;

    if (preg_match('/^http.?:\/\//', $entry['page']))
    {
        printf("<li><a href='%s' target='_blank'>%s</a></li>\n",
            $entry['page'], $entry['text']);
    }
    else
    {
        $nopage = '';
        if (isset($entry['wrap']) && ($entry['wrap'] == 0))
            $nopage = '&nopage=1';

        printf("<li><a href='plugin.php?plugin=%s&page=%s%s'>%s</a></li>\n",
            $plugin, $entry['page'], $nopage, $entry['text']);
    }
}
?>
