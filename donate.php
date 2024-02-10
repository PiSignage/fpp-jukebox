<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php
  require_once("config.php");
  require_once("common.php");
  require_once("jukebox-common.php");
  require_once("fppversion.php");

  $pluginJson = convertAndGetSettings('jukebox');
  ?>

  <link rel="stylesheet" href="css/fpp-bootstrap/dist/fpp-bootstrap.css" />
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
      <img src="/api/file/Images/<?php echo $pluginJson['qr_code'] ?>" class="img-fluid mb-3" width="30%">
    </div>
    <p style="text-align: center; color: white">Scan QR CODE</p>

  </div>
  <a id="back-to-top" href="plugin.php?_menu=status&plugin=fpp-jukebox&page=jukebox.php&nopage=1" class="btn btn-light btn-lg back-to-top" role="button">Back To Songs</a>
</body>

</html>
