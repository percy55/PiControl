<?php
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Restricted Area"');
        header('HTTP/1.0 401 Unauthorized');
        die('Cancelled!');

    } else {
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        if($username != 'mavanmanen' || $password != 'mitchell1994'){
            header('WWW-Authenticate: Basic realm="Restricted Area"');
            header('HTTP/1.0 401 Unauthorized');
            die('Wrong Credentials!');
        }

    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">
    <style>
        .stat .label {
            color: black;
            text-align: right;
            display: table-cell;
            font-size: 10pt;
        }
        .stat .value p {
            margin: 0;
            display: inline;
        }
        .stat .value {
            font-weight: 500;
            text-align: left;
        }
        .progress {
            height: 8pt;
            margin-bottom: 0;
            border-radius: 0;
            display: inline-block;
            width: 300px;
        }

        iframe {
            width: 100%;
            height: calc(100vh - 48px);
            border: none;
        }

        table.table {
            margin: 0;
        }

        table.table tr {
            min-width: 100vw;
        }

        #processes-table {
            overflow-y: auto;
            width: 100%;
            max-height: calc(100vh - 80px);
        }

        .process-item {

        }

        thead, tbody {
            display: block;
        }

        #stats .panel {
            display: table;
            position: relative;
            margin: auto;
            top: calc(50vh - 125px);
        }

        .glyphicon {
            margin-right: 5px;
        }
    </style>
</head>
<body>

    <div role="tabpanel">

      <!-- Nav tabs -->
      <ul class="nav nav-tabs" role="tablist">

            <li role="presentation" class="active">
                <a href="#stats" aria-controls="stats" role="tab" data-toggle="tab">
                    <span class="glyphicon glyphicon-dashboard" aria-hidden="true"></span>Stats
                </a>
            </li>

            <li role="presentation">
                <a href="#processes" aria-controls="processes" role="tab" data-toggle="tab">
                    <span class="glyphicon glyphicon-th-list" aria-hidden="true"></span>Processes
                </a>
            </li>

            <li role="presentation">
                <a href="#ssh" aria-controls="ssh" role="tab" data-toggle="tab">
                    <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>SSH
                </a>
            </li>

            <li role="presentation">
                <a href="#settings" aria-controls="settings" role="tab" data-toggle="tab">
                    <span class="glyphicon glyphicon-cog" aria-hidden="true"></span>Settings
                </a>
            </li>

      </ul>

      <!-- Tab panes -->
      <div class="tab-content">

            <div role="tabpanel" class="tab-pane active" id="stats">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <table>
                            <tr class="stat uptime">
                                <td class="label">Uptime</td>
                                <td class="label value">Placeholder</td>
                            </tr>
                            <tr class="stat tasks">
                                <td class="label">Tasks</td>
                                <td class="label value">Placeholder</td>
                            </tr>
                            <tr class="stat cpu_usage">
                                <td class="label">Cpu Usage</td>
                                <td class="label value">
                                    <p></p>
                                    <div class="progress">
                                        <div class="progress-bar"
                                            role="progressbar"
                                            aria-valuenow="0"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            style="width: 0%;">
                                        </div>
                                     </div>
                                </td>
                            </tr>
                            <tr class="stat cpu_temp">
                                <td class="label">Cpu Temp</td>
                                <td class="label value">Placeholder</td>
                            </tr>
                            <tr class="stat ram_usage">
                                <td class="label">Ram usage</td>
                                <td class="label value">Placeholder</td>
                            </tr>
                        </table>
                   </div>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="processes">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>PID</th>
                            <th>Cpu Usage</th>
                            <th>Ram Usage</th>
                            <th>Command</th>
                        </tr>
                    </thead>
                    <tbody id="processes-table">
                    </tbody>
                </table>
            </div>

            <div role="tabpanel" class="tab-pane" id="ssh">
                <?php
                    if(exec('pidof shellinaboxd')){ ?>
                        <iframe src=""></iframe>
                    <?php } else { ?>
                        <div class="alert alert-danger" role="alert">shellinaboxd is not running, please execute shellinaboxd -t -b</div>
                    <?php }
                ?>

            </div>
            <div role="tabpanel" class="tab-pane" id="settings">...</div>
      </div>

    </div>

    <!-- <input type="submit" class="button" name="reboot" value="reboot"> -->

    <script src="https://code.jquery.com/jquery-2.1.3.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
    <script>
    $((function(){

        function locGet(key){
            return localStorage.getItem(key);
        }

        function locSet(key, value){
            localStorage.setItem(key, value);
        }

        if(locGet('setup') === 'false'){
            // Setup default settings
            locSet('setup', true);
            locSet('refreshRate', 5000);
            locSet('siabPort', 4200);
        }


        $('#ssh iframe').attr('src', 'http://<?php echo $_SERVER['SERVER_ADDR']; ?>:' + locGet('siabPort'));

        $('.nav-tabs a').click(function (e){
            e.preventDefault()
            $(this).tab('show')
        })

        ajax('getStats');
        updateProcesses();
        setInterval(function(){ ajax('getStats'); }, locGet('refreshRate'));

        function ajax(val){
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                data: {'action': val},
                dataType: 'json',
                success: function(data){
                    console.log(data);
                    updateStats(data);
                }
            });
        }

        function updateStats(data){
            $('.uptime .value').text(data.uptime);
            $('.tasks .value').text(data.tasks);
            $('.cpu_usage .value p').text(data.cpu_usage + '%');
            $('.cpu_usage .value .progress-bar').width(data.cpu_usage + '%');
            $('.cpu_temp .value').text(data.cpu_temp);
            $('.ram_usage .value').text(data.free_mem[1] + ' MB used of ' + data.free_mem[0] + ' MB');
        }

        function updateProcesses(){
            $.ajax({
                url: 'ajax.php',
                type: 'POST',
                data: {'action': 'getProcesses'},
                dataType: 'json',
                success: function(data){
                    $('#processes-table').empty();
                    data.forEach(function(p, index){
                        var row = $('<tr class="process-item">');

                        p.forEach(function(v, index){

                            row.append('<td>' + v + '</td>');
                        });

                        $('#processes-table').append(row);
                    });
                }
            });
        }

        $('.button').click(function(){
            var clickBtnValue = $(this).val();
        });

        $('.process-item').click(function(){
            console.log(this);
        });

    }));
    </script>
</body>
</html>
