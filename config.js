var jukeboxConfig = null;

function SaveJukeboxConfig(config) {
  var data = JSON.stringify(config);
  $(".itemList").addClass("loading");
  $.ajax({
    type: "POST",
    url: 'api/configfile/plugin.fpp-jukebox.json',
    dataType: 'json',
    async: false,
    data: data,
    processData: false,
    contentType: 'application/json',
    success: function (data) {
      $(".itemList").removeClass("loading");
      $.jGrowl('Jukebox Config Saved!', {
        themeState: 'success'
      });
    }
  });
}

function GetItem(i, v) {
  var item = {
    "name": $('#item-' + i + '_Name').val(),
    "option": $('#item-' + i + '_Option').val(),
  };

  var type = $('#item-' + i + '_Option').val();

  var pet = playlistEntryTypes[type];
  var args = new Array()
  var keys = Object.keys(pet.args);
  for (var ii = 0; ii < keys.length; ii++) {
    var a = pet.args[keys[ii]];

    if ((a.type == 'string')) {
      var inp = $('#item-' + i + '_EntryOptions').find('.arg_' + a.name)
      var val = inp.val();
      if (val !== undefined) {
        args.push(val);
      }
    }
  }

  item['args'] = args
  return item;
}

function SaveItems() {
  jukeboxConfig["static_sequence"] = $('#static_sequence').val();
  jukeboxConfig["ticker_other_info"] = $('#ticker_other_info').val();
  // Reset items to empty array
  jukeboxConfig["items"] = [];
  // Look over itemList children
  $.each($('.itemList').children(), function (i, v) {
    var key = "" + i;
    var item = GetItem(i, v);
    jukeboxConfig["items"][key] = item;
  });
  // console.log(jukeboxConfig);
  SaveJukeboxConfig(jukeboxConfig);
}

function updateItemRow(i, v) {
  var $newItemRow = $(v);
  //var newReaderRowCommand = 'reader-' + i + '_Command';
  var newItemRowName = 'item-' + i + '_Name';
  var newItemRowTable = 'tableItem-' + i;

  $newItemRow.data('rKey', i);
  //$newItemRow.find('.buttonCommand').attr('id', newReaderRowCommand);
  $newItemRow.find('.itemName').attr('id', newItemRowName);
  $newItemRow.find('.itemHeader').html('Item ' + (i + 1));

  $newItemRow.find('[id^="tableItem"]').each(function () {
    var oldId = $(this).prop('id')
    var idArr = oldId.split('_');
    idArr[0] = newItemRowTable
    $(this).attr('id', idArr.join('_'))

  });
  return $newItemRow;
}

function createItemRow(i, v) {
  // console.log('createItemRow');
  var $newItemRow = $($(".configItemTemplate").html());
  var newItemRowEntryOptions = 'item-' + i + '_EntryOptions';
  var newItemRowOption = 'item-' + i + '_Option';
  var newItemRowName = 'item-' + i + '_Name';
  var newItemRowTable = 'tableItem-' + i;

  $newItemRow.data('rKey', i);
  $newItemRow.find('.itemEntryOptions').attr('id', newItemRowEntryOptions);
  $newItemRow.find('.itemOption').attr('id', newItemRowOption).on('change', function () {
    var option = $(this).val();

    $('#' + newItemRowEntryOptions).html('');
    PrintArgInputs(newItemRowEntryOptions, true, playlistEntryTypes[option].args);
  });

  $newItemRow.find('.itemHeader').html('Item ' + (i + 1));
  if (!v) {
    $newItemRow.find('.itemName').attr('id', newItemRowName).val();
  } else {
    $newItemRow.find('.itemName').attr('id', newItemRowName).val(v.name);
  }
  $newItemRow.find('.tableItem').attr('id', newItemRowTable);

  $newItemRow.find('.itemDelete').click(function () {
    $(this).closest('.item').remove();
    $.each($('.itemList').children(), function (iteration, value) {
      updateItemRow(iteration, value);
    });
  });

  $('.itemList').append($newItemRow);

  return $newItemRow;
}

$(function () {
  $('#saveItemConfigButton').click(function () {
    SaveItems();
  });

  $(".itemList").addClass("loading");
  $.get('api/configfile/plugin.fpp-jukebox.json')
    .done(function (data) {
      $(".itemList").removeClass("loading");
      processItemConfig(data);
    })
    .fail(function (data) {
      $(".itemList").removeClass("loading");
      processItemConfig('{"static_sequence":"","ticker_other_info":"","items":[]}');
    });

  function processItemConfig(data) {
    if (typeof data === "string") {
      jukeboxConfig = $.parseJSON(data);
    } else {
      jukeboxConfig = data;
    }

    // console.log(jukeboxConfig);

    if (jukeboxConfig.items.length < 1) {
      jukeboxConfig.items.push({
        "name": "",
        "option": "",
      });
    }

    $('#static_sequence').val(jukeboxConfig.static_sequence);
    $('#ticker_other_info').val(jukeboxConfig.ticker_other_info);

    $.each(jukeboxConfig.items, function (i, v) {
      $newItemRow = createItemRow(i, v);
      var json = v;

      if (typeof json != "undefined") {
        if (json["option"] != '') {
          $('#item-' + i + '_Option').val(json["option"]);
          PrintArgInputs('item-' + i + '_EntryOptions', true, playlistEntryTypes[json["option"]].args);
          var baseUrl = "";

          if (typeof json['args'] != "undefined") {
            var count = 1;
            $.each(json['args'], function (key, v) {
              var inp = $("#item-" + i + "_EntryOptions_arg_" + count);
              if (inp.data('contentlisturl') != null && baseUrl != "") {
                console.log('ReloadContentList');
                ReloadContentList(baseUrl, inp);
              }

              $('#item-' + i + '_EntryOptions_arg_' + count).val(v);
              console.log(v);
              count = count + 1;
            })
          }
        }
      }
    });
  }

  $("#addNewItem").click(function () {
    var i = $(".itemList").children().length;
    var $newItemRow = createItemRow(i, null);
  });
});
