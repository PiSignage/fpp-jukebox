<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jukebox</title>
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

  $jquery = glob("$fppDir/www/js/jquery-*.min.js");
  printf("<script type='text/javascript' src='js/%s'></script>\n", basename($jquery[0]));
  ?>

  <script src="js/jquery.jgrowl.min.js"> </script>
  <script src="/plugin.php?plugin=fpp-jukebox&page=assets/js/sweetalert2.js&nopage=1"></script>
  <link rel="stylesheet" href="css/jquery.jgrowl.min.css" />
  <link rel="stylesheet" href="css/fpp-bootstrap/dist/fpp-bootstrap.css" />
  <script type="text/javascript">
    var baseUrl = "<?php echo $baseUrl; ?>";
    var baseIp = "<?php echo $baseIp; ?>";
    var pluginJson;
    var fppVersionTriplet;
    var startTime = '<?php echo $start_time; ?>';
    var endTime = '<?php echo $end_time; ?>';
    var hide = "<?php echo isset($_GET['hide']) ? $_GET['hide'] : 'nothing' ?>";

    function sendButtonCommand(i) {
      $.get(baseUrl + 'api/fppd/status', function(data, status) {
        var static_sequence = pluginJson['static_sequence'];
        var current_sequence = data.current_sequence;
        var current_status = data.status;

        // console.log('static_sequence: ' + static_sequence);
        // console.log('current_sequence: ' + current_sequence);

        if (static_sequence != '') {
          console.log('static_sequence entered');
          if (current_sequence === static_sequence) {
            console.log("static sequence playing play item");
            console.log("Playing: " + pluginJson["items"][i]["name"]);
            playItem(pluginJson["items"][i]["args"][0]);
            showAlert("Playing: " + pluginJson["items"][i]["name"]);
          } else {
            console.log("waiting for static sequence")
            Swal.fire("Waiting for static sequence");
          }
        } else {
          if (current_status == 1) {
            console.log("something is playing stop it add start the selected item");
            $.ajax({
              type: "GET",
              url: baseUrl + "api/playlists/stop",
              async: false,
              contentType: 'application/json',
              success: function(data) {
                playItem(pluginJson["items"][i]["args"][0]);
                showAlert("Playing: " + pluginJson["items"][i]["name"])
              }
            });
          } else {
            console.log("nothing playing play item")
            playItem(pluginJson["items"][i]["args"][0]);
            showAlert("Playing: " + pluginJson["items"][i]["name"]);
          }
        }
      });
    }

    function playItem(item) {
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
        data["command"] = 'Start Playlist';
        data["args"] = [
          item,
          false,
          true,
        ];
      }

      $.ajax({
        type: "POST",
        url: url,
        dataType: 'json',
        async: false,
        data: JSON.stringify(data),
        processData: false,
        contentType: 'application/json',
        success: function(data) {}
      });

      $.ajax({
        type: "POST",
        url: 'plugin.php?plugin=fpp-jukebox&page=other.php&command=save_song_count&nopage=1',
        async: false,
        data: {
          item: item,
        },
        dataType: 'json',
        async: false,
        success: function(data) {}
      });
    }

    function get24Hr(time) {
      var hours = Number(time.match(/^(\d+)/)[1]);
      var AMPM = time.match(/\s(.*)$/)[1];
      if (AMPM == "PM" && hours < 12) hours = hours + 12;
      if (AMPM == "AM" && hours == 12) hours = hours - 12;

      var minutes = Number(time.match(/:(\d+)/)[1]);
      hours = hours * 100 + minutes;
      // console.log(time + " - " + hours);
      return hours;
    }

    function getval() {
      var currentTime = new Date()
      var hours = currentTime.getHours()
      var minutes = currentTime.getMinutes()

      if (minutes < 10) minutes = "0" + minutes;

      var suffix = "AM";
      if (hours >= 12) {
        suffix = "PM";
        hours = hours - 12;
      }
      if (hours == 0) {
        hours = 12;
      }
      var current_time = hours + ":" + minutes + " " + suffix;

      return current_time;

    }

    function inSideTime() {
      var curr_time = getval();
      if (get24Hr(curr_time) > get24Hr(startTime) && get24Hr(curr_time) < get24Hr(endTime)) {
        console.log('inside active time stay on page');
      } else {
        console.log('outside active time send user back to locked page');
        window.location.replace("/plugin.php?_menu=status&plugin=fpp-jukebox&page=locked.php&nopage=1");
      }
    }

    function showAlert(text, type = "success") {
      Swal.fire({
        title: text,
        timer: 3000,
        showConfirmButton: false,
        icon: type
      });
    }

    $(function() {
      if (startTime != '') {
        inSideTime();
        setInterval(inSideTime, 10000);
      }


      fppVersionTriplet = $('body').data('fpp-version-triplet');

      $.get('api/configfile/plugin.fpp-jukebox.json')
        .done(function(data) {
          processJukeboxConfig(data);
        })
        .fail(function(data) {
          processJukeboxConfigFail([]);
        });

      function processJukeboxConfigFail(data) {
        var link = $('<a>', {
          href: 'plugin.php?_menu=content&plugin=fpp-jukebox&page=config.php',
          text: 'Jukebox is unconfigured, click me to go to the configuration page'
        });

        // Append the link to the body
        $('body').append(link);
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

        $.each(pluginJson.items, function(i, item) {
          var $newItem = $($('#itemTemplate').html());
          $newItem.find('.itemName').html(item.name);
          $newItem.find('img').attr('src', baseUrl + '/api/file/Images/' + item.args[1]);

          $newItem.on('click', function() {
            // $.jGrowl(item.name + " has been activated", {
            //   themeState: 'success'
            // });
            sendButtonCommand(i);
            currently_playing();
          });

          $('#items').append($newItem);
        });
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
            text = text + data.current_sequence.replace('.fseq', '') + '<span class="dot"></span>Remaining Time: <span class="countdown"></span>';
          }

          if (pluginJson.ticker_other_info != '' && pluginJson.ticker_other_info_location == 'after') {
            text = text + '<span class="dot"></span>' + pluginJson.ticker_other_info;
          }

          $('.news-scroll').html(text);
          timer(data.time_remaining);
        });
      }

      function timer(time) {
        var timer2 = time;
        $('.countdown').html(time);
      }

      currently_playing();
      setInterval(currently_playing, 1000);
      if (hide == "buttons") {
        console.log(hide);
        $(".back-to-top").hide();
      }

      $('#stop').on('click', function(e) {
        e.preventDefault();

        $.get(baseUrl + '/api/playlists/stop', function(data, status) {
          Swal.fire({
            title: "Everything has stopped playing",
            timer: 3000,
            showConfirmButton: false,
            icon: "success"
          });
        });
      });

      $('#play_static').on('click', function(e) {
        e.preventDefault();
        var url = "api/command/";
        var data = new Object();
        var thisItem = $(this).attr('data-item');

        data["command"] = 'Start Playlist';
        data["args"] = [
          thisItem,
          false,
          true,
        ];

        $.ajax({
          type: "POST",
          url: url,
          dataType: 'json',
          async: false,
          data: JSON.stringify(data),
          processData: false,
          contentType: 'application/json',
          success: function(data) {
            Swal.fire({
              title: "Static sequence is  playing",
              timer: 3000,
              showConfirmButton: false,
              icon: "success"
            });
          }
        });
      });
    });
  </script>
  <style>
    @font-face {
      font-family: 'Comic-Queens';
      src: url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/Comic-Queens.ttf.woff&nopage=1') format('woff'),
        url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/Comic-Queens.ttf.svg&nopage=1#Comic-Queens') format('svg'),
        url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/Comic-Queens.ttf.eot&nopage=1'),
        url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/Comic-Queens.ttf.eot&nopage=1?#iefix') format('embedded-opentype');
      font-weight: normal;
      font-style: normal;
    }

    html,
    body {
      overscroll-behavior-x: none;
    }

    body {
      background-color: black;
      font-family: 'Comic-Queens';
    }

    body::-webkit-scrollbar {
      display: none;
    }

    .container {
      max-width: 95%;
      margin: auto;
    }

    h5 {
      font-weight: 500;
    }

    .title {
      font-size: 2.3em;
      margin-bottom: 0.65em;
      font-weight: 500;
      color: #fff;
      text-align: center;
    }

    .flex-row {
      -ms-flex-direction: row !important;
      flex-direction: row !important;
    }

    .card-img-left {
      width: auto;
      height: 100%;
      border-radius: calc(0.25rem - 1px);
      border: 10px solid white;
    }

    .flex-column {
      flex-direction: column !important;
    }

    .justify-content-center {
      -ms-flex-pack: center !important;
      justify-content: center !important;
    }

    .align-items-center {
      -ms-flex-align: center !important;
      align-items: center !important;
    }

    .justify-content-between {
      -ms-flex-pack: justify !important;
      justify-content: space-between !important;
    }

    .bg-white {
      background-color: #fff !important;
    }

    .bg-danger {
      background-color: #dc3545 !important;
    }

    .text-white {
      color: #fff !important;
    }

    .card {
      background: transparent;
      text-decoration: none;
    }

    .card .card-body {
      color: white
    }

    .border {
      border: 5px solid #dee2e6 !important;
    }

    .border-white {
      border-color: #fff !important;
    }

    .rounded {
      border-radius: 0.25rem !important;
    }

    .news {
      width: 260px
    }

    .news-scroll a {
      text-decoration: none
    }

    .dot {
      height: 6px;
      width: 6px;
      margin-left: 3px;
      margin-right: 3px;
      margin-top: 2px !important;
      background-color: rgb(207, 23, 23);
      border-radius: 50%;
      display: inline-block
    }

    .flex-grow-1 {
      -ms-flex-positive: 1 !important;
      flex-grow: 1 !important;
    }

    .back-to-top {
      position: fixed;
      bottom: 25px;
      right: 25px;
    }
  </style>
</head>
<template id="itemTemplate">
  <div class="col">
    <div class="card mb-3">
      <div class="row g-0">
        <div class="col-md-4">
          <img src="" class="img-fluid border border-white rounded">
        </div>
        <div class="col-md-8">
          <div class="card-body">
            <h5 class="card-title itemName">Card title</h5>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<body class="is-kiosk" data-fpp-version-triplet="<?= getFPPVersionTriplet(); ?>">
  <div class="d-flex justify-content-between align-items-center breaking-news bg-white mb-3">
    <div class="d-flex flex-row flex-grow-1 flex-fill justify-content-center bg-danger py-2 text-white px-1 news"><span class="d-flex align-items-center">&nbsp;Currently Playing</span></div>
    <marquee class="news-scroll" behavior="scroll" direction="left" onmouseover="this.stop();" onmouseout="this.start();">
      Loading.....
    </marquee>
  </div>

  <div class="container">
    <h1 class="title">Select A Song</h1>
    <div class="row row-cols-2 g-3" id="items"></div>
  </div>
  <div class="back-to-top">
    <a id="donate_btn" href="plugin.php?_menu=status&plugin=fpp-jukebox&page=donate.php&nopage=1" class="btn btn-light btn-lg" role="button">Donate</a>
    <a id="stop" href="" class="btn btn-danger btn-lg">Stop All</a>
    <?php if ($pluginJson['static_sequence'] != '') { ?>
      <a id="play_static" href="" data-item="<?php echo $pluginJson['static_sequence']; ?>" class="btn btn-light">Play static sequence</a>
    <?php } ?>
  </div>
</body>

</html>
