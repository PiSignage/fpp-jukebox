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
            var text = 'Nothing Player - Select a song';
          } else {
            var text = data.current_sequence;
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

    .title {
      font-size: 2.3em;
      margin-bottom: 0.65em;
      font-weight: 500;
      color: #fff;
      text-align: center;
    }

    .back-to-top {
      position: fixed;
      bottom: 25px;
      right: 25px;
    }
  </style>
</head>

<body class="is-kiosk" data-fpp-version-triplet="<?= getFPPVersionTriplet(); ?>">
  <div class="container">
    <h1 class="title">Donate</h1>

    <div class="d-flex" style="justify-content: center;">
      <img src="https://www.pixartprinting.it/blog/wp-content/uploads/2022/06/qr_code_cos_e.png" class="img-fluid mb-3" width="30%">
    </div>
    <p style="text-align: center; color: white">Scan QRCODE</p>

  </div>
  <a id="back-to-top" href="plugin.php?_menu=status&plugin=fpp-jukebox&page=jukebox.php&nopage=1" class="btn btn-light btn-lg back-to-top" role="button">Back To Songs</a>
</body>

</html>
