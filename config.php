<?php
require_once("jukebox-common.php");
$pluginJson = convertAndGetSettings('jukebox');
$baseUrl = isset($pluginJson['remote_ip']) && $pluginJson['remote_ip'] != '' ? 'http://' . $pluginJson['remote_ip'] . '/' : null;
?>

<div id="global" class="settings">
  <link rel="stylesheet" type="text/css" href="css/jquery.timepicker.css">
  <script type="text/javascript" src="js/jquery.timepicker.js"></script>
  <link rel="stylesheet" href="/plugin.php?plugin=fpp-jukebox&page=config.css&nopage=1" />
  <script src="/plugin.php?plugin=fpp-jukebox&page=assets/js/jquery-ui.js&nopage=1"></script>
  <script src="/plugin.php?plugin=fpp-jukebox&page=config.js&nopage=1"></script>

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
  </script>

  <template class="configItemTemplate">
    <div class="col-md-6 item dragItem">
      <div class="card mb-4 box-shadow">
        <div class="card-header ItemHeader"><i class="fpp-icon-grip"></i> Item</div>
        <div class="card-body">
          <div class="buttonCommandWrap mb-2">
            <div class="bb_commandTableWrap">
              <div class="bb_commandTableCrop">
                <table border=0 id="tableReaderTPL" class="tableItem">
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

  <div class="d-flex justify-content-between mb-1">
    <button id="addNewItem" class="buttons btn-success mr-1">
      <i class="fas fa-plus"></i> Add a Item
    </button>
    <button id="saveItemConfigButton" class="buttons btn-success">
      Save Config
    </button>
  </div>
  <legend>Jukebox Config</legend>

  <p>Kiosk Url: http://localhost/plugin.php?_menu=status&plugin=fpp-jukebox&page=jukebox.php&nopage=1</p>

  <div class="form-group">
    <label for="remote_ip">Remote IP</label>
    <input type="text" id="remote_ip" class="form-control" aria-describedby="remoteIpHelp"></input>
    <small id="remoteIpHelp" class="form-text text-muted">Do you have the plugin on one controller and sequences/playlist on another? Enter the ip address on the remote controller.</small>
  </div>
  <div class="form-group">
    <label for="static_sequence">Static Sequence</label>
    <select id="static_sequence" class="form-control" aria-describedby="staticSequenceHelp" data-contentlisturl="api/files/sequences?nameOnly=1"></select>
    <small id="staticSequenceHelp" class="form-text text-muted">Do You have a sequence run between songs?</small>
  </div>
  <div class="form-group">
    <label for="ticker_other_info">Additional Ticker Information</label>
    <input type="text" class="form-control" id="ticker_other_info" aria-describedby="tickerOtherInfoHelp">
    <small id="tickerOtherInfoHelp" class="form-text text-muted">Want to display other information on the Currently Playing Ticker</small>
  </div>
  <div class="form-group">
    <label for="ticker_other_info_location">Location of additional Ticker Information</label>
    <select class="form-control" id="ticker_other_info_location" aria-describedby="tickerOtherInfoLocationHelp">
      <option value="before">Before</option>
      <option value="after">After</option>
    </select>
    <small id="tickerOtherInfoLocationHelp" class="form-text text-muted">Display the what location do you want to put Additional Ticker Information to show - Before the currently playing song information or After it</small>
  </div>
  <div class="form-group">
    <label for="qr_code">QR code</label>
    <select id="qr_code" class="form-control" aria-describedby="qrCodeHelp" data-contentlisturl="api/files/images?nameOnly=1"></select>
    <small id="qrCodeHelp" class="form-text text-muted">Do you have a QR code that you use for visitors to donate. Select the QR Code from your upload images</small>
  </div>
  <hr class="mb-3" />

  <legend>Locked Config</legend>
  <p>Only want your visitors to be able to select an item between a set time.</p>
  <div class="form-group">
    <label for="show_logo">Show Logo</label>
    <select id="show_logo" class="form-control" data-contentlisturl="api/files/images?nameOnly=1"></select>
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
  <div id="dragArea" class="itemList row"></div>
</div>
