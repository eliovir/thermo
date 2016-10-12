<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$listCmd=$_GET['ssa'];

$date = array(
	'start' => init('startDate', date('Y-m-d', strtotime(config::byKey('history::defautShowPeriod') . ' ' . date('Y-m-d')))),
	'end' => init('endDate', date('Y-m-d')),
);
?>
 <div class="md_history">
        <input id="in_startDate" class="form-control input-sm in_datepicker" style="display : inline-block; width: 150px;" value="<?php echo $date['start']; ?>"/>
        <input id="in_endDate" class="form-control input-sm in_datepicker" style="display : inline-block; width: 150px;" value="<?php echo $date['end']; ?>"/>
        <a class="btn btn-success btn-sm tooltips" id='bt_validChangeDate' title="{{Attention une trop grande plage de dates peut mettre très longtemps à être calculée ou même ne pas s'afficher}}">{{Ok}}</a>
       
       

        



<center><div id="div_historyChart"></div></center>
        
       
</div>

<script>
    $(".in_datepicker").datepicker();
    $('#ui-datepicker-div').hide();
    
 
    
    
    
    
    $(function () {
    var seriesOptions = [],
        seriesCounter = 0,
        names = [<?PHP echo $listCmd;?>];
        
    var color = {temperature:"#A5260A", consigne:"#0000FF", etat:"#2D241E"};
    
    
    
    
    /*
     * Create the chart when all data is loaded
     * @returns {undefined}
     */
    function createChart() {

        $('#div_historyChart').highcharts('StockChart', {
            
                title: {
                    text: ''
                },
                
                credits: {
                    text: '',
                    href: ''
                },
                exporting: { 
                    enabled:  false 
                },
                rangeSelector: {
                    buttons: [{
                        type: 'minute',
                        count: 30,
                        text: '30m'
                    }, {
                        type: 'hour',
                        count: 1,
                        text: 'H'
                    }, {
                        type: 'day',
                        count: 1,
                        text: 'J'
                    }, {
                        type: 'week',
                        count: 1,
                        text: 'S'
                    }, {
                        type: 'month',
                        count: 1,
                        text: 'M'
                    }, {
                        type: 'year',
                        count: 1,
                        text: 'A'
                    }, {
                        type: 'all',
                        count: 1,
                        text: 'Tous'
                    }],
                    selected: 4,
                    inputEnabled: false
                },
             
           

           yAxis: [{
                labels: {
                    align: 'right',
                    x: -3,
                    formatter: function() {
                        return this.value + ' °c';
                    }
                },
                title: {
                    text: 'Temperature'
                },
                height: '80%',
                lineWidth: 2
            }, {
                labels: {
                    align: 'right',
                    x: -3
                },
                title: {
                    text: 'On/Off'
                },
                top: '85%',
                height: '15%',
                offset: 0,
                lineWidth: 1,
                max: 1,
                
            }],
            
                
             dataGrouping: {
                    units: [
                        ['hour', [1]],
                        ['day', [1]],
                        ['month', [1]],
                        ['year', null]
                    ],
                    groupPixelWidth: 100
                },
           
            tooltip: {
                formatter: function() 
                {
                    var s = [];
                
                    $.each(this.points, function(i, point) 
                    {   if (point.series.name=='etat')
                        { res=(point.y > 0) ? 'on' : 'off';
                        }    
                        else
                        { res=point.y.toFixed(1);
                        }
                        s.push('<span style="color:#D31B22;font-weight:bold;">'+ point.series.name +' : '+
                        res +'<span>');
                    });
                    return s.join(' <br> ');

                    
                },
                valueDecimals: 1,
                
            },

            series: seriesOptions
        });
    }





 
    
$.each(names, function (i, name) {

        $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "core/ajax/cmd.ajax.php", // url du fichier php
        data: {
            action: "getHistory",
            id: name,
            dateRange: 'all',
            dateStart: $('#in_startDate').value(),
            dateEnd: $('#in_endDate').value(),
            derive:  '',
            allowZero: 1
        },
        dataType: 'json',
        global:  true,
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné 
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            
            if (data.result.data.length < 1) {
                var message = '{{Il n\'existe encore aucun historique pour cette commande :}} ' + data.result.history_name;
                if (init(data.result.dateStart) != '') {
                    if (init(data.result.dateEnd) != '') {
                        message += ' {{du}} ' + data.result.dateStart + ' {{au}} ' + data.result.dateEnd;
                    } else {
                        message += ' {{à partir de}} ' + data.result.dateStart;
                    }
                } else {
                    if (init(data.result.dateEnd) != '') {
                        message += ' {{jusqu\'au}} ' + data.result.dateEnd;
                    }
                }
                $('#div_alert').showAlert({message: message, level: 'danger'});
                return;
            }
            
            
            
            
            
            console.log(data);
            
            seriesOptions[i] = {
                name: data.result.cmd_name,
                color: color[data.result.cmd_name],
                data: data.result.data,
                type: (data.result.cmd_name == 'etat') ?'area':'line',
                yAxis:(data.result.cmd_name == 'etat') ? 1 : 0,
                step: (data.result.cmd_name == 'etat') ? true : false
                
            };
            seriesCounter += 1;

            if (seriesCounter === names.length) {
                createChart();
              
            }
        }
        });  
        
        
        
           

            // As we're loading the data asynchronously, we don't know what order it will arrive. So
           
        });
    });

    $('#bt_validChangeDate').on('click',function(){
                    var modal = false;
                    if($('#md_modal').is(':visible')){
                        modal = $('#md_modal');
                    }else if($('#md_modal2').is(':visible')){
                        modal = $('#md_modal2');
                    }
                    if(modal !== false){
                        modal.dialog({title: "{{Historique}}"});
                        modal.load('index.php?v=d&plugin=ssaThermostat&modal=modal.ssaThermostat&ssa=<?PHP echo $listCmd;?>&startDate='+$('#in_startDate').val()+'&endDate='+$('#in_endDate').val()).dialog('open');
                    }
                });

</script>    

