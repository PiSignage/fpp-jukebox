<div id="global" class="settings">
  <link rel="stylesheet" href="/plugin.php?plugin=fpp-jukebox&page=config.css&nopage=1" />
  <script src="/plugin.php?plugin=fpp-jukebox&page=config.js&nopage=1"></script>

  <script>
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
            "contentListUrl": "api/playlists",
            "optional": false,
            "simpleUI": true
          }
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
            "contentListUrl": "api/files/sequences?nameOnly=1",
            "type": "string",
            "optional": false,
            "simpleUI": true
          }
        }
      }
    };
  </script>

  <template class="configItemTemplate">
    <div class="col-md-6 item">
      <div class="card mb-4 box-shadow">
        <div class="card-header ItemHeader">Item</div>
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

  <div class="form-group">
    <label for="static_sequence">Static Sequence</label>
    <input type="text" class="form-control" id="static_sequence" aria-describedby="staticSequenceHelp" placeholder="Sequence Name">
    <small id="staticSequenceHelp" class="form-text text-muted">Do You have a sequence run between songs?</small>
  </div>
  <div class="form-group">
    <label for="ticker_other_info">Ticker Info</label>
    <input type="text" class="form-control" id="ticker_other_info" aria-describedby="tickerOtherInfoHelp">
    <small id="tickerOtherInfoHelp" class="form-text text-muted">Want to display other information on the ticker</small>
  </div>

  <div class="itemList row"></div>
</div>
