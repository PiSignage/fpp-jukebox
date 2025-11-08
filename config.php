<?php
require_once("jukebox-common.php");
$pluginJson = convertAndGetSettings('jukebox');
$baseUrl = isset($pluginJson['remote_ip']) && $pluginJson['remote_ip'] != '' ? 'http://' . $pluginJson['remote_ip'] . '/' : null;
$jukeboxUrl = "http://" . $_SERVER['SERVER_NAME'] . "/plugin.php?_menu=status&plugin=fpp-jukebox&page=jukebox.php&nopage=1";
?>

<div id="global" class="settings">
  <link rel="stylesheet" type="text/css" href="css/jquery.timepicker.css">
  <script type="text/javascript" src="js/jquery.timepicker.js"></script>
  <link rel="stylesheet" href="/plugin.php?plugin=fpp-jukebox&file=config.css&nopage=1" />
  <script src="/plugin.php?plugin=fpp-jukebox&file=assets/js/jquery-ui.js&nopage=1"></script>
  <script src="/plugin.php?plugin=fpp-jukebox&file=config.js&nopage=1"></script>
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

    @font-face {
      font-family: 'Shantell Sans';
      font-style: normal;
      font-weight: 400;
      src: url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/shantell-sans-v13-latin-regular.eot&nopage=1');
      src: url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/shantell-sans-v13-latin-regular.eot&nopage=1?#iefix') format('embedded-opentype'),
        url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/shantell-sans-v13-latin-regular.woff2&nopage=1') format('woff2'),
        url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/shantell-sans-v13-latin-regular.woff&nopage=1') format('woff'),
        url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/shantell-sans-v13-latin-regular.ttf&nopage=1') format('truetype'),
        url('/plugin.php?plugin=fpp-jukebox&page=assets/fonts/shantell-sans-v13-latin-regular.svg&nopage=1#ShantellSans') format('svg');
    }
  </style>

  <style>
    .alert {
      position: relative !important;
      padding: 0.75rem 1.25rem !important;
      margin-bottom: 1rem !important;
      border: 1px solid transparent !important;
      border-radius: 0.25rem !important;
    }

    .alert-info {
      color: #004085 !important;
      background-color: #cce5ff !important;
      border-color: #b8daff !important;
    }

    .alert-link {
      color: #002752 !important;
      font-weight: 700 !important;
      text-decoration: none !important;
    }

    .alert-link:hover {
      text-decoration: underline !important;
    }

    .font-one {
      font-family: 'Shantell Sans';
    }

    .font-two {
      font-family: 'Comic-Queens';
    }
  </style>

  <script>
    var baseUrl = "<?php echo $baseUrl; ?>";
    var playlistEntryTypes = {};
    playlistEntryTypes = {
      "playlist": {
        "name": "playlist",
        "description": "Playlist",
        "args": {
          "name": {
            "name": "name",
            "description": "Playlist",
            "type": "string",
            "contentListUrl": baseUrl + "api/playlists",
            "optional": false,
            "simpleUI": true
          },
          "imageName": {
            "name": "imageName",
            "description": "Image",
            "contentListUrl": baseUrl + "api/files/images?nameOnly=1",
            "type": "string",
            "optional": false,
            "simpleUI": true,
            "default": "placeholder.jpg"
          },
        }
      },
      "sequence": {
        "name": "sequence",
        "description": "Sequence",
        "longDescription": "Sequence Only",
        "args": {
          "sequenceName": {
            "name": "sequenceName",
            "description": "Sequence",
            "contentListUrl": baseUrl + "api/files/sequences?nameOnly=1",
            "type": "string",
            "optional": false,
            "simpleUI": true
          },
          "imageName": {
            "name": "imageName",
            "description": "Image",
            "contentListUrl": baseUrl + "api/files/images?nameOnly=1",
            "type": "string",
            "optional": false,
            "simpleUI": true,
            "default": "placeholder.jpg"
          },
        }
      }
    };
    $(document).ready(function () {
      var remoteIpList = null;
      var remoteIpLookupUrl = $('#remote_ip').attr('data-contentlisturl');
      $.ajax({
        dataType: 'json',
        async: false,
        url: baseUrl + remoteIpLookupUrl,
        success: function (data) {
          remoteIpList = data;
        }
      });

      $.each(remoteIpList, function (k, v) {
        $('#remote_ip_list').append("<option value='" + k + "'>" + v + "</option>");
      });
    });
  </script>

  <template class="configItemTemplate">
    <div class="col-md-6 item dragItem">
      <div class="card mb-4 box-shadow">
        <div class="card-header ItemHeader"><i class="fpp-icon-grip"></i> Item</div>
        <div class="card-body">
          <div class="buttonCommandWrap mb-2">
            <div class="bb_commandTableWrap">
              <div class="bb_commandTableCrop">
                <table border="0" id="tableReaderTPL" class="tableItem">
                  <tr>
                    <td>Item Name:</td>
                    <td>
                      <input type="text" class="form-control itemName" placeholderct Name as on Zettle" required>
                    </td>
                  </tr>
                  <tr>
                    <td>Option</td>
                    <td>
                      <select class="form-control itemOption">
                        <option value="">Select</option>
                        <option value="playlist">Playlist</option>
                        <option value="sequence">Sequence</option>
                      </select>
                    </td>
                  </tr>
                  <tbody class="itemEntryOptions"></tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-outline-danger itemDelete">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <div class="alert alert-info">
    All media needs to be upload to the <a href="filemanager.php" class="alert-link">file manager</a>.
  </div>

  <div class="d-flex flex-row-reverse mb-1">
    <button id="saveItemConfigButton" class="buttons btn-success">
      Save Config
    </button>
  </div>

  <?php if (!file_exists('/home/fpp/media/config/plugin.fpp-jukebox.json')) { ?>
    <div class="alert alert-danger">Base config file missing please run <strong>.
        /home/fpp/media/plugins/fpp-jukebox/scripts/fpp_install.sh</strong> in <a
        href="http://<?php echo $_SERVER['SERVER_NAME']; ?>:4200" target="_blank">SSH shell</a></div>
  <?php } ?>

  <legend>Jukebox Config</legend>

  <p><strong>Kiosk Url</strong>: <a href="<?php echo $jukeboxUrl; ?>" target="_blank"><?php echo $jukeboxUrl; ?></a></p>

  <div class="form-group">
    <label for="remote_ip">Remote IP</label>
    <input type="text" id="remote_ip" class="form-control" aria-describedby="remoteIpHelp"
      data-contentlisturl="api/remotes" list="remote_ip_list"></input>
    <datalist id="remote_ip_list"></datalist>
    <small id="remoteIpHelp" class="form-text text-muted">Do you have the plugin on one controller and
      sequences/playlist on another? Enter the ip address on the remote controller.</small>
  </div>
  <div class="form-group">
    <label for="static_sequence">Static Sequence</label>
    <select id="static_sequence" class="form-control" aria-describedby="staticSequenceHelp"
      data-contentlisturl="api/files/sequences?nameOnly=1"></select>
    <small id="staticSequenceHelp" class="form-text text-muted">Do You have a sequence run between songs? and you want
      the system to wait before allowing the next song to be select</small>
  </div>
  <div class="form-group">
    <label for="ticker_other_info">Additional Ticker Information</label>
    <input type="text" class="form-control" id="ticker_other_info" aria-describedby="tickerOtherInfoHelp">
    <small id="tickerOtherInfoHelp" class="form-text text-muted">Want to display other information on the Currently
      Playing Ticker (Example: Welcome to SHOWNAME)</small>
  </div>
  <div class="form-group">
    <label for="ticker_other_info_location">Location of additional Ticker Information</label>
    <select class="form-control" id="ticker_other_info_location" aria-describedby="tickerOtherInfoLocationHelp">
      <option value="before">Before</option>
      <option value="after">After</option>
    </select>
    <small id="tickerOtherInfoLocationHelp" class="form-text text-muted">Display the what location do you want to put
      Additional Ticker Information to show - Before the currently playing song information or After it</small>
  </div>
  <div class="form-group">
    <label for="qr_code">QR code</label>
    <select id="qr_code" class="form-control" aria-describedby="qrCodeHelp"
      data-contentlisturl="api/files/images?nameOnly=1"></select>
    <small id="qrCodeHelp" class="form-text text-muted">Do you have a QR code that you use for visitors to donate.
      Select the QR Code from your upload images</small>
  </div>
  <div class="form-group">
    <label for="font">Font</label>
    <select name="font" id="font" class="form-control">
      <option value="Shantell Sans" style="font-family: Shantell Sans">Font One</option>
      <option value="Comic-Queens" style="font-family: Comic-Queens">Font Two</option>
    </select>
  </div>
  <hr class="mb-3" />

  <legend>Locked Config</legend>
  <p>Only want your visitors to be able to select an item between a set time.</p>
  <div class="form-group">
    <label for="show_logo">Show Logo</label>
    <select id="show_logo" class="form-control" data-contentlisturl="api/files/images?nameOnly=1"></select>
  </div>
  <div class="form-group">
    <label for="logo_location">Logo Location</label>
    <select name="logo_location" id="logo_location" class="form-control">
      <option value="top">Top</option>
      <option value="left">Left</option>
      <option value="right">Right</option>
    </select>
    <small class="form-text text-muted">The location where your show logo will show on the locked screen</small>
  </div>
  <div class="form-group">
    <label for="show_name">Show Name</label>
    <input type="text" id="show_name" class="form-control">
  </div>
  <div class="form-group">
    <label for="additional_info">Show Information</label>
    <textarea id="additional_info" class="form-control"></textarea>
  </div>
  <div class="form-row">
    <div class="form-group col-md-6">
      <label for="start_time">Start Time</label>
      <input type="text" class="form-control time" id="start_time">
      <small class="form-text text-muted">Only what the allow your visitors to select an item between times</small>
    </div>
    <div class="form-group col-md-6">
      <label for="end_time">End Time</label>
      <input type="text" class="form-control time" id="end_time">
    </div>
  </div>
  <hr class="mb-3" />
  <div class="d-flex justify-content-between mb-1">
    <button id="addNewItem" class="buttons btn-success mr-1">
      <i class="fas fa-plus"></i> Add a Item
    </button>
  </div>
  <div id="dragArea" class="itemList row"></div>
</div>