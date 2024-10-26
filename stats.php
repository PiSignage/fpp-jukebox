<?php

include_once 'jukebox-common.php';
$pluginName = 'jukebox';

$songCounts = convertAndGetSettings($pluginName . "-counts");
ksort($songCounts);
?>

<head>
  <meta http-equiv="refresh" content="30">
  <style>
    .table {
      width: 100%;
      max-width: 100%;
      margin-bottom: 1rem;
      background-color: transparent;
    }

    .table-bordered {
      border: 1px solid #dee2e6;
    }

    .table td,
    .table th {
      padding: 0.75rem;
      vertical-align: top;
      border-top: 1px solid #dee2e6;
    }

    .table-bordered td,
    .table-bordered th {
      border: 1px solid #dee2e6;
    }

    .table thead th {
      vertical-align: bottom;
      border-bottom: 2px solid #dee2e6;
    }
  </style>
</head>

<body>
  <p>What is your most popular song being played.</p>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Song Name</th>
        <th>Played</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($songCounts as $count) { ?>
        <tr>
          <td><?php echo $count['name']; ?></td>
          <td><?php echo $count['count']; ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
  <input id="clear_stats" class="buttons" value="Clear Stats">
  <script>
    $(function() {
      $("#clear_stats").on("click", function(e) {
        e.preventDefault();
        var stats = [];
        $.ajax({
          type: "POST",
          url: 'plugin.php?plugin=fpp-jukebox&page=other.php&command=clear_stats&nopage=1',
          dataType: 'json',
          async: false,
          data: JSON.stringify(stats),
          processData: false,
          contentType: 'application/json',
          success: function(data) {
            $.jGrowl('Stats cleared', {
              themeState: 'success'
            });
            setTimeout(function() {
              location.reload();
            }, 3000);
          },
          error: function() {
            $('#save').prop('disabled', false);
            DialogError('Error', "ERROR: There was an error in saving your details, please try again!");
          }
        });
      });
    });
  </script>
</body>
