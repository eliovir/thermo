
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


 
 $(document).ready(function()
 {
   
      
    $("#dtBox").DateTimePicker(
            {
                titleContentTime: "Heure",
                isPopup: true,
                minuteInterval: 1,
                langage: "fr",
                setButtonContent: "Ok",
                clearButtonContent: "Effacer"
            });
  
    
   
 });

$("#table_plage").delegate('.ssaThermoPlageDeb', 'change', function () {
    console.log($( this ).val());     
    $( this ).closest('tr').find('.ssaThermoPlageFin').attr('data-min', $( this ).val());
  
   
     
});

$("#cmd_ssa").delegate(".listEquipementInfo", 'click', function() {
    var el = $(this);
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function(result) {
        var calcul = el.closest('div').find('.eqLogicAttr[data-l1key=configuration]');
        
        calcul.atCaret('insert', result.human);
    });
});

$("#cmd_ssa").delegate(".listEquipementAction", 'click', function() {
    var el = $(this);
    jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function(result) {
        var calcul = el.closest('div').find('.eqLogicAttr[data-l1key=configuration]');
        
        calcul.atCaret('insert', result.human);
    });
});

$("#table_plage").delegate('.bt_removePlage', 'click', function () {
    $(this).closest('.plage').remove();
});



$('#bt_addPlage').on('click', function () {
    var plage =new Object();
    plage.name='' ;
    plage.debut='' ;  
    plage.fin='' ;  
    plage.consigne='' ; 
    addPlage(plage);
   
   
});



function saveEqLogic(_eqLogic) {
    if (!isset(_eqLogic.configuration)) {
        _eqLogic.configuration = {};
    }
    var data = getPlage('#form_plage');
    _eqLogic.configuration.plages = data;
    return _eqLogic;
}


function printEqLogic(_eqLogic) {
    $('#table_plage tbody').empty();
    if (isset(_eqLogic.configuration)) {
        if (isset(_eqLogic.configuration.plages)) {
            for (var i in _eqLogic.configuration.plages) {
                addPlage(_eqLogic.configuration.plages[i]) ;
                
            }
        }
    }
}


function getPlage(table)
{ 
    
   var otArr = [];
   var tbl2 = $(table +" tbody  tr").each(function(i) {        
        var plage =new Object();
        
        plage.name=$(this).find("input[name=name]").val() ;
        plage.debut=$(this).find("input[name=debut]").val() ;  
        plage.fin=$(this).find("input[name=fin]").val() ;  
        plage.consigne=$(this).find("input[name=consigne]").val() ;  
        var checked = [];
        $(this).find('.ssaDaySwitch').each(function ()
        {   
            
            if ($(this).bootstrapSwitch('state')==true)
             checked.push($(this).attr('name'));
         
        });
        
        
        plage.calendrier=checked;
        otArr.push(plage);
   })
 
   return otArr;
    
    
    
}



function addPlage(_plage)
{
   
    var random = Math.floor((Math.random() * 1000000) + 1);
    var tr = '<tr class="plage">';
    tr += '<td>';
    
    tr += '<input  name="name" class="form-control" placeholder="{{Nom plage}}" value="'+_plage.name +'">';
    tr += '</td>';
    
    tr += '<td>';
    tr += '<input name="debut" data-field="time" data-max="23:59" readonly class="form-control ssaThermoPlageDeb" placeholder="{{heure Début}}" value="'+_plage.debut +'">';
    tr += '</td>';
    
    tr += '<td>';
   
    tr += '<input name="fin" data-field="time" data-max="23:59" readonly class="form-control ssaThermoPlageFin" placeholder="{{heure Fin}}" value="'+_plage.fin +'">';
    tr += '</td>';
    
    
    tr += '<td>';
    
    //tr += '<input name="consigne" style="width : 140px;" placeholder="{{Consigne}}" value="'+_plage.consigne +'">';
    tr += '<div class="col-sm-1 input-group">';
    
    tr += '<span class="input-group-btn">';
    tr += '    <button type="button" class="btn btn-default" data-value="decrease" data-target="#consigne_'+ random +'" data-toggle="spinner">';
    tr += '        <span class="glyphicon glyphicon-minus"></span>';
    tr += '    </button>';
    tr += '</span>';
    tr += '<input style="width : 80px;"id="consigne_'+ random +'"name="consigne" class="form-control" placeholder="{{Consigne}}" value="'+_plage.consigne +'" data-precision="1" data-step="0.5" data-min="5" data-max="25"/>';                
    
                    
    tr += '<span class="input-group-btn">';
    tr += '    <button type="button" class="btn btn-default" data-value="increase" data-target="#consigne_'+ random +'" data-toggle="spinner">';
    tr += '        <span class="glyphicon glyphicon-plus"></span>';
    tr += '    </button>';
    tr += '</span>';
    tr += '</div>';
    
    
    
    
    
    tr += '</td>';
    tr += '<td>';
   
    
    
    tr += '<input type="checkbox" class="ssaDaySwitch" data-label-text="{{L}}" name="l"   id="l_'+ random +'">';
    
    tr += '<input type="checkbox" class="ssaDaySwitch" data-label-text="{{Ma}}" name="ma" id="ma_'+ random +'">';
    
    tr += '<input type="checkbox"  class="ssaDaySwitch" data-label-text="{{Me}}" name="me" id="me_'+ random +'">';
    
    tr += '<input type="checkbox" class="ssaDaySwitch" data-label-text="{{J}}" name="j" id="j_'+ random +'">';
    
    tr += '<input type="checkbox" class="ssaDaySwitch" data-label-text="{{V}}" name="v" id="v_'+ random +'">';
    
    tr += '<input type="checkbox" class="ssaDaySwitch" data-label-text="{{S}}" name="s" id="s_'+ random +'">';
    
    tr += '<input type="checkbox" class="ssaDaySwitch" data-label-text="{{D}}"  name="d" id="d_'+ random +'">';
    
    tr += '<input type="checkbox" class="ssaDaySwitch" data-label-text="{{JF}}" name="f" id="f_'+ random +'">';
    
    
    tr += '</td>';
    tr += '<td>';
    tr += '<a class=" btn btn-sm bt_removePlage btn-primary"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>';
    
    tr += '</td>';
    $('#table_plage tbody').append(tr);
    $(".ssaDaySwitch").bootstrapSwitch('size', 'mini');
    
    _plage.calendrier.forEach(function(element){
        console.log(element+'_'+random);
        $('#'+element+'_'+random).bootstrapSwitch('state',true);
        });
    console.log(_plage.calendrier);
}


$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */




function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
   
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="type" style="display : none;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="subType" style="display : none;">';
    tr += '<input  readonly class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}"></td>';
    tr += '</td>';
    
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }
    tr += '</td>';
    tr += '</tr>';
    
    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
        $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
}


