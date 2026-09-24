<?php
require_once("config.php");
require_once("common.php");
require_once("jukebox-common.php");
require_once("fppversion.php");

$pluginJson = convertAndGetSettings('jukebox');
$baseUrl = isset($pluginJson['remote_ip']) && $pluginJson['remote_ip'] != '' ? 'http://' . $pluginJson['remote_ip'] . '/' : null;
$baseIp = isset($pluginJson['remote_ip']) && $pluginJson['remote_ip'] != '' ? $pluginJson['remote_ip'] : null;
$start_time = $pluginJson['locked_start_time'] != '' ? $pluginJson['locked_start_time'] : '';
$end_time = $pluginJson['locked_end_time'] != '' ? $pluginJson['locked_end_time'] : '';
$hide_images = $pluginJson['hide_images'] != '' ? $pluginJson['hide_images'] : 'no';
$button_timeout_sec = isset($pluginJson['button_timeout']) != '' ? $pluginJson['button_timeout'] : 60000;
$category = $_GET['category'];

$jquery = glob("$fppDir/www/js/jquery-*.min.js");
printf("<script type='text/javascript' src='js/%s'></script>\n", basename($jquery[0]));
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Album Layout</title>
    <!-- Bootstrap 5 CSS -->
    <link href="/plugin.php?plugin=fpp-jukebox&file=assets/css/bootstrap5.min.css&nopage=1" rel="stylesheet">

    <style>
        .fixed-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #121212;
            color: white;
            z-index: 1000;
            /* Ensure it's above other content */
        }

        .content-wrapper {
            padding-top: 60px;
            /* Add space below the fixed header */
        }

        .album-card {
            border: 1px solid #121212;
            border-radius: 10px;
            overflow: hidden;
            background-color: #121212;
            color: white;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .album-card img {
            width: 100%;
            height: auto;
        }

        .album-card-body {
            padding: 0.5rem;
            flex-grow: 1;
        }

        .album-card-title {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .album-card-description {
            font-size: 0.9rem;
            color: #aaa;
            margin-bottom: 0;
        }

        .btn {
            background-color: #f0ad4e;
            color: #fff;
        }

        .btn:hover {
            background-color: #ec971f;
        }

        .btn-wrapper {
            margin: 0.5rem;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 20px;
        }

        /* Adjust grid layout for different screen sizes */
        @media (max-width: 1200px) {

            /* Large screens (e.g., 1280px) */
            .grid-container {
                grid-template-columns: repeat(3, 1fr);
                /* 3 items per row */
            }
        }

        @media (max-width: 768px) {

            /* Medium screens (e.g., tablets) */
            .grid-container {
                grid-template-columns: repeat(2, fr);
                /* 3 item per row */
            }
        }

        @media (max-width: 576px) {

            /* Small screens (e.g., mobile) */
            .grid-container {
                grid-template-columns: 1fr;
                /* 1 item per row */
            }
        }

        /* Styling for the marquee */
        .marquee-container {
            white-space: nowrap;
            overflow: hidden;
        }

        .marquee {
            display: inline-block;
            animation: marquee 15s linear infinite;
            /* padding-left: 20%; */
        }

        @keyframes marquee {
            from {
                transform: translateX(100%);
            }

            to {
                transform: translateX(-120%);
            }
        }

        .dot {
            height: 6px;
            width: 6px;
            margin-left: 3px;
            margin-right: 3px;
            margin-top: 2px !important;
            background-color: rgb(207, 23, 23);
            border-radius: 50%;
            display: inline-block;
        }
    </style>
</head>

<template id="itemTemplate">
    <div id="song" class="album-card" data-songname="test">
        <img src="jukebox/w.png" alt="Album 1" />
        <div class="album-card-body">
            <h5 class="album-card-title itemName">Item Name</h5>
            <p class="album-card-description itemDescription">
                Item description
            </p>
        </div>
        <div class="btn-wrapper">
            <button id="listen-btn" class="btn w-100">Listen</button>
        </div>
    </div>
</template>

<body class="bg-dark text-light">
    <!-- Fixed Header -->
    <div class="fixed-header">
        <div class="d-flex flex-row align-self-center">
            <!-- Left: Currently Playing -->
            <div class="p-2 bg-danger">
                <strong>Currently Playing</strong>
            </div>

            <!-- Center: Song Name -->
            <div class="p-2 marquee-container flex-grow-1">
                <div class="marquee">
                    <span class="news-scroll">Loading...</span>
                </div>
            </div>

            <!-- Right: Current Time -->
            <div class="p-2 bg-danger">
                <strong><span id="current-time">00:00</span></strong>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div id="noConfigAlert" class="alert alert-info" style="display: none">Jukebox is unconfigured, <a
                    href="plugin.php?_menu=content&plugin=fpp-jukebox&page=config.php" class="alert-link">click me</a>
                to go to
                the configuration page</div>

            <div id="items" class="grid-container">
                <h1>Loading...</h1>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script> -->
    <!-- <script src="https://code.jquery.com/jquery-4.0.0.min.js"
        integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script> -->

    <script src="/plugin.php?plugin=fpp-jukebox&file=assets/js/sweetalert2.js&nopage=1"></script>

    <!-- JavaScript to update current time every second -->
    <script>
        var baseUrl = "<?php echo $baseUrl; ?>";
        var baseIp = "<?php echo $baseIp; ?>";
        var pluginJson;
        var fppVersionTriplet;
        var startTime = '<?php echo $start_time; ?>';
        var endTime = '<?php echo $end_time; ?>';
        var hide = "<?php echo isset($_GET['hide']) ? $_GET['hide'] : 'nothing' ?>";
        var fpp_current_status = 0;
        var fpp_current_sequence = "";
        var itemPlaying = "";
        var hideImages = "<?php echo $hide_images; ?>"
        var buttonTimeoutSet = false;
        var buttonTimeoutSecounds = parseInt(<?php echo $button_timeout_sec; ?>);
        var category = "<?php echo $category; ?>";

        function sendButtonCommand(i) {
            var static_sequence = pluginJson['static_sequence'];
            var playlist_item = pluginJson["items"][i];

            if (playlist_item["option"] == 'script') {
                var itemArgs = playlist_item["args"];
                var args = [
                    itemArgs[0],
                    itemArgs[1]
                ];
            } else {
                var args = playlist_item["args"][0];
            }

            console.log(args);
            return;

            // if (static_sequence != '') {
            //     console.log('static_sequence entered');
            //     if (fpp_current_sequence === static_sequence) {
            //         console.log("static sequence playing play item");
            //         console.log("Playing: " + playlist_item["name"]);
            //         playItem(args, playlist_item["option"]);
            //         showAlert("Playing: " + playlist_item["name"]);
            //         itemPlaying = playlist_item["name"];
            //     } else {
            //         console.log("waiting for static sequence")
            //         Swal.fire("Waiting for static sequence");
            //     }
            // } else {
            //     if (buttonTimeoutSet) {
            //         showAlert('Please wait');
            //     } else {
            //         buttonTimeout();
            //         if (fpp_current_status == 1) {
            //             console.log("something is playing stop it add start the selected item");
            //             // Stop command data
            //             var data = new Object();
            //             data['command'] = 'Stop Now';
            //             data['args'] = [];
            //             // Stop what ever is playing
            //             $.ajax({
            //                 type: "POST",
            //                 url: baseUrl + "api/command",
            //                 data: JSON.stringify(data),
            //                 async: false,
            //                 contentType: 'application/json',
            //                 success: function(data) {
            //                     // Play the selected item
            //                     playItem(args, playlist_item["option"]);
            //                     // showAlert("Playings: " + pluginJson["items"][i]["name"])
            //                     itemPlaying = playlist_item["name"];
            //                 }
            //             });
            //         } else {
            //             console.log("nothing playing play item")
            //             playItem(args, playlist_item["option"]);
            //             // showAlert("Playing: " + pluginJson["items"][i]["name"]);
            //             itemPlaying = playlist_item["name"];
            //         }
            //     }
            // }
        }

        function playItem(item, type) {
            // console.log('Play item: ' + item);
            var url = "api/command/";
            // console.log('Baseurl: ' + baseUrl);
            var data = new Object();
            if (baseUrl != '') {
                data['command'] = 'Remote Playlist Start';
                data['multisyncCommand'] = false;
                data['multisyncHosts'] = '';
                data['args'] = [
                    baseIp,
                    item,
                    false,
                    false
                ];
            } else {
                if (type == 'script') {
                    //{command: "Run Script", multisyncCommand: false, multisyncHosts: "", args: [null, "", ""]}
                    data["command"] = 'Run Script';
                    data['multisyncCommand'] = false;
                    data['multisyncHosts'] = '';
                    data['args'] = item;
                } else {
                    data["command"] = 'Start Playlist';
                    data["args"] = [
                        item,
                        false,
                        true,
                    ];
                }
            }

            $.ajax({
                type: "POST",
                url: url,
                async: false,
                data: JSON.stringify(data),
                success: function(data) {
                    console.log('song playing');

                    var playing = new Object();
                    playing['item'] = item;

                    $.ajax({
                        type: "POST",
                        url: 'plugin.php?plugin=fpp-jukebox&page=other.php&command=save_song_count&nopage=1',
                        async: false,
                        data: {
                            item: item,
                        },
                        dataType: 'json',
                        async: false,
                        success: function(data) {
                            console.log("Save song count");
                        }
                    });
                },
                error: function() {
                    // showAlert("There was a problem playing you selected song, please try agin", "warning");
                    console.log("There was a problem playing you selected song, please try agin");
                }
            });
        }

        function updateTime() {
            const currentTimeElement = document.getElementById("current-time");
            const now = new Date();

            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, "0");

            const ampm = hours >= 12 ? "PM" : "AM";
            hours = hours % 12;
            hours = hours ? hours : 12;
            currentTimeElement.textContent = `${hours}:${minutes} ${ampm}`;
        }
        setInterval(updateTime, 1000);

        function showAlert(text, type = "success") {
            Swal.fire({
                title: text,
                timer: 3000,
                showConfirmButton: false,
                icon: type
            });
        }

        function buttonTimeout() {
            buttonTimeoutSet = true;
            setTimeout(() => {
                buttonTimeoutSet = false;
            }, buttonTimeoutSecounds);
        }

        $(function() {
            $.get('api/configfile/plugin.fpp-jukebox.json')
                .done(function(data) {
                    processJukeboxConfig(data);
                })
                .fail(function(data) {
                    processJukeboxConfigFail([]);
                });

            function processJukeboxConfigFail(data) {
                $("#noConfigAlert").show();
            }

            function processJukeboxConfig(data) {
                if (typeof data === "string") {
                    pluginJson = $.parseJSON(data);
                } else {
                    pluginJson = data;
                }

                if (pluginJson.qr_code == '') {
                    $('#donate_btn').hide();
                }

                $('#items').empty();

                if (pluginJson.items.length > 0) {

                    if (category != "") {
                        var $newItem = $($('#itemTemplate').html());
                        $newItem.find('.itemName').html("Go Back");
                        $newItem.find('.itemDescription').addClass('d-none');
                        $newItem.find('#listen-btn').text("Go Back");

                        $newItem.on('click', function() {
                            let url = new URL(window.location.href);
                            url.searchParams.delete('category');

                            window.location.href = url.href;
                        });

                        $('#items').append($newItem);
                    }

                    $.each(pluginJson.items, function(i, item) {
                        if (hideImages == 'yes') {
                            var $newItem = $($('#itemNoImageTemplate').html());
                        } else {
                            var $newItem = $($('#itemTemplate').html());
                            if (item.option == 'script') {
                                $newItem.find('img').attr('src', baseUrl + 'api/file/Images/' + item.args[2]);
                            } else {
                                $newItem.find('img').attr('src', baseUrl + 'api/file/Images/' + item.args[1]);
                            }
                        }
                        $newItem.find('.itemName').html(item.name);
                        if (item.description) {
                            $newItem.find('.itemDescription').html(item.description);
                        } else {
                            $newItem.find('.itemDescription').addClass('d-none');
                        }

                        $newItem.on('click', function() {
                            var item = pluginJson["items"][i];
                            var itemName = item["name"];
                            console.log("You have clicked:", itemName);
                            console.log("Run:", item["option"]);

                            // showAlert("Playing: " + item["option"]);

                            sendButtonCommand(i);
                            currently_playing();
                        });

                        $('#items').append($newItem);
                    });
                } else {
                    $("#noConfigAlert").show();
                }
            }

            function currently_playing() {
                $.get(baseUrl + '/api/fppd/status', function(data, status) {
                    var text = '';
                    if (pluginJson.ticker_other_info != '' && pluginJson.ticker_other_info_location == 'before') {
                        text = pluginJson.ticker_other_info + '<span class="dot"></span>';
                    }

                    if (data.current_sequence == '') {
                        text = text + 'Nothing Playing - Please select a song';
                    } else if (pluginJson['static_sequence'] != '' && data.current_sequence == pluginJson['static_sequence']) {
                        text = text + 'Playing static sequence - Please select a song';
                    } else {
                        text = text + itemPlaying + '<span class="dot"></span>Remaining Time: <span class="countdown"></span>';
                        //text = text + data.current_sequence.replace(/.fseq/g, '').replace(/_/g, ' ').replace(/-/g, ' ') + '<span class="dot"></span>Remaining Time: <span class="countdown"></span>';
                    }

                    if (pluginJson.ticker_other_info != '' && pluginJson.ticker_other_info_location == 'after') {
                        text = text + '<span class="dot"></span>' + pluginJson.ticker_other_info;
                    }

                    $('.news-scroll').html(text);
                    timer(data.time_remaining);
                    fpp_current_status = data.status;
                    fpp_current_sequence = data.current_sequence;
                });
            }

            function timer(time) {
                var timer2 = time;
                $('.countdown').html(time);
            }

            setInterval(currently_playing, 1000);
        });
    </script>
</body>

</html>