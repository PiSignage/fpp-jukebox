<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php
  require_once("config.php");
  require_once("common.php");
  //require_once("bb-common.php");
  require_once("fppversion.php");

  $jquery = glob("$fppDir/www/js/jquery-*.min.js");
  printf("<script type='text/javascript' src='js/%s'></script>\n", basename($jquery[0]));
  ?>

  <script src="js/jquery.jgrowl.min.js"> </script>
  <link rel="stylesheet" href="css/jquery.jgrowl.min.css" />
  <link rel="stylesheet" href="css/fpp-bootstrap/dist/fpp-bootstrap.css" />
  <script type="text/javascript">
    var pluginJson;
    var fppVersionTriplet;

    function sendButtonCommand(i) {
      $.get('api/fppd/status', function(data, status) {
        var static_sequence = pluginJson['static_sequence'];
        var current_sequence = data.current_sequence;

        // console.log('static_sequence: ' + static_sequence);
        // console.log('current_sequence: ' + current_sequence);

        if (static_sequence != '') {
          console.log('static_sequence entered');
          if (current_sequence === static_sequence) {
            console.log("static sequence playing play item");
            console.log("Playing: " + pluginJson["items"][i]["args"]);
            playItem(pluginJson["items"][i]["args"]);
          } else {
            console.log("waiting for static sequence")
          }
        } else {
          if (current_sequence == '') {
            console.log("Playing nothing play item");
            console.log("Playing: " + pluginJson["items"][i]["args"]);
            playItem(pluginJson["items"][i]["args"]);
          } else {
            console.log("waiting for static sequence")
          }
        }
      });
    }

    function playItem(item) {
      var url = "api/command/";

      var data = new Object();
      data["command"] = 'Start Playlist';
      data["args"] = item;
      // Repeat
      data["args"].push(false);
      // If Not Running
      data["args"].push(true);

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
    }

    $(function() {

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

        $.each(pluginJson.items, function(i, item) {
          var $newItem = $($('#itemTemplate').html());
          $newItem.find('.itemName').html(item.name);

          var image_name = item.args[0].replace('.fseq', '.jpg');
          $newItem.find('img').attr('src', '/api/file/Images/' + image_name);

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
        $.get('/api/fppd/status', function(data, status) {
          // console.log(data.current_sequence);
          if (data.current_sequence == '' || data.current_sequence == pluginJson['static_sequence']) {
            var text = 'Nothing Playing - Select a song';
          } else {
            var text = data.current_sequence.replace('.fseq', '');
          }

          if (pluginJson.ticker_other_info != '') {
            text = text + '<span class="dot"></span>' + pluginJson.ticker_other_info;
          }

          $('.news-scroll').html(text)
        });
      }

      currently_playing();
      setInterval(currently_playing, 2000);
    });
  </script>
  <style>
    @font-face {
      font-family: 'Comic-Queens';
      src: url('/plugin.php?plugin=fpp-jukebox&page=fonts/Comic-Queens.ttf.woff&nopage=1') format('woff'),
        url('/plugin.php?plugin=fpp-jukebox&page=fonts/Comic-Queens.ttf.svg&nopage=1#Comic-Queens') format('svg'),
        url('/plugin.php?plugin=fpp-jukebox&page=fonts/Comic-Queens.ttf.eot&nopage=1'),
        url('/plugin.php?plugin=fpp-jukebox&page=fonts/Comic-Queens.ttf.eot&nopage=1?#iefix') format('embedded-opentype');
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
      What are we playing
    </marquee>
  </div>

  <div class="container">
    <h1 class="title">Select A Song</h1>
    <div class="row row-cols-2 g-3" id="items"></div>
  </div>
  <a id="back-to-top" href="plugin.php?_menu=status&plugin=fpp-jukebox&page=donate.php&nopage=1" class="btn btn-light btn-lg back-to-top" role="button">Donate</a>
</body>

</html>
