<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jukebox Locked</title>
  <?php
  require_once("config.php");
  require_once("common.php");
  require_once("jukebox-common.php");
  require_once("fppversion.php");

  $pluginJson = convertAndGetSettings('jukebox');
  $start_time = $pluginJson['locked_start_time'] != '' ? $pluginJson['locked_start_time'] . " PM" : '';
  $end_time = $pluginJson['locked_end_time'] != '' ? $pluginJson['locked_end_time'] . " PM" : '';

  $jquery = glob("$fppDir/www/js/jquery-*.min.js");
  printf("<script type='text/javascript' src='js/%s'></script>\n", basename($jquery[0]));
  ?>

  <link rel="stylesheet" href="/plugin.php?plugin=fpp-jukebox&page=assets/css/bootstrap.min.css&nopage=1" />
  <link rel="stylesheet" href="/plugin.php?plugin=fpp-jukebox&page=assets/css/locked.css&nopage=1" />
  <script type="text/javascript">
    var startTime = '<?php echo $start_time; ?>';
    var endTime = '<?php echo $end_time; ?>';

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
        console.log('inside active time send user back to jukebox page');
        window.location.replace("/plugin.php?_menu=status&plugin=fpp-jukebox&page=jukebox.php&nopage=1");
      } else {
        console.log('outside active time stay here');
      }
    }

    function showTime() {
      // to get current time/ date.
      var date = new Date();
      // to get the current hour
      var h = date.getHours();
      // to get the current minutes
      var m = date.getMinutes();
      //to get the current second
      var s = date.getSeconds();
      // AM, PM setting
      var session = "AM";

      //conditions for times behavior
      if (h == 0) {
        h = 12;
      }
      if (h >= 12) {
        session = "PM";
      }

      if (h > 12) {
        h = h - 12;
      }
      m = (m < 10) ? m = "0" + m : m;
      s = (s < 10) ? s = "0" + s : s;

      //putting time in one variable
      var time = "Current Time: " + h + ":" + m + ":" + s + " " + session;
      //putting time in our div
      $('#clock').html(time);
      //to change time in every seconds
      setTimeout(showTime, 1000);
    }

    $(function() {
      inSideTime();
      setInterval(inSideTime, 10000);
      showTime();
    });
  </script>
</head>

<body class="text-center">
  <div class="cover-container d-flex h-100 p-3 mx-auto flex-column">
    <main role="main" class="inner cover mt-auto mb-auto">
      <div class="row">
        <div class="col-4">
          <img src="/api/file/Images/<?php echo $pluginJson['locked_show_logo'] ?>" alt="" class="img-fluid">
        </div>
        <div class="col-8">
          <h1 class="cover-heading">Welcome To <?php echo $pluginJson['locked_show_name'] ?? 'NOTHING SET'; ?></h1>
          <p class="lead"><?php echo $pluginJson['locked_additional_info'] ?? 'NOTHING SET'; ?></p>
          <?php if ($pluginJson['qr_code'] != '') { ?>
            <p class="lead">
              <a href="plugin.php?_menu=status&plugin=fpp-jukebox&page=donate.php&nopage=1" class="btn btn-lg btn-secondary">Donation Information</a>
            </p>
          <?php } ?>
        </div>
      </div>
      <h3 id="clock"></h3>
    </main>
  </div>
</body>
