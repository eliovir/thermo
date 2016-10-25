<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'ssaThermostat');
$eqLogics = eqLogic::byType('ssaThermostat');
?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un thermostat}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
foreach ($eqLogics as $eqLogic) {
	echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
?>
           </ul>
       </div>
   </div>

   <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
    <legend>{{Mes thermostats}}
    </legend>

    <div class="eqLogicThumbnailContainer">
      <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
         <center>
            <i class="fa fa-plus-circle" style="font-size : 7em;color:#94ca02;"></i>
        </center>
        <span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>{{Ajouter}}</center></span>
    </div>
    <?php
foreach ($eqLogics as $eqLogic) {
	echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
	echo "<center>";
	echo '<img src="plugins/ssaThermostat/doc/images/thermostat_icon.png" height="105" width="95" />';
	echo "</center>";
	echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
	echo '</div>';
}
?>
</div>
</div>

<div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
    <form class="form-horizontal">
        <fieldset>
            <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}  <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
            <div class="form-group">
                <label class="col-sm-3 control-label">{{Nom de l'équipement Thermostat}}</label>
                <div class="col-sm-3">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement template}}"/>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-3 control-label" >{{Objet parent}}</label>
                <div class="col-sm-3">
                    <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                        <option value="">{{Aucun}}</option>
                        <?php
foreach (object::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
                   </select>
               </div>
           </div>
           <div class="form-group">
            <label class="col-sm-3 control-label" >{{Activer}}</label>
            <div class="col-sm-9">
               <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>
               <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>
           </div>
       </div>
      
</fieldset>
</form>
    

    
<legend>{{Pilote}}</legend>  
<form class="form-horizontal" id="cmd_ssa">
        <fieldset>
            
             <div class="form-group">
                <label class="col-sm-2 control-label">{{Sonde}}</label>
                <div class="col-sm-4" >
                    <input type="text" class="col-sm-2 eqLogicAttr form-control" data-l1key="configuration" data-l2key="commande" data-l3key="tempSonde" />
                    <a class="btn btn-default btn-sm cursor listEquipementInfo" data-input="configuration" style="margin-left : 5px;"><i class="fa fa-list-alt "></i> {{Rechercher équipement}}</a>
                </div>
            </div>
            
                      
            <div class="form-group">
                <label class="col-sm-2 control-label">{{Commande ON}}</label>
                <div class="col-sm-4">
                    <input type="text" class="col-sm-2 eqLogicAttr form-control" data-l1key="configuration" data-l2key="commande" data-l3key="CmdOn" />
                    <a class="btn btn-default btn-sm cursor listEquipementAction" data-input="configuration" style="margin-left : 5px;"><i class="fa fa-list-alt "></i> {{Rechercher équipement}}</a>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{Commande OFF}}</label>
                <div class="col-sm-4">
                    <input type="text" class="col-sm-2 eqLogicAttr form-control" data-l1key="configuration" data-l2key="commande" data-l3key="CmdOff" />
                    <a class="btn btn-default btn-sm cursor listEquipementAction" data-input="configuration" style="margin-left : 5px;"><i class="fa fa-list-alt "></i> {{Rechercher équipement}}</a>
                </div>
            </div>
            
        </fieldset>
</form>

    
<legend>{{Temperature}}</legend>

<form class="form-horizontal" >
        <fieldset>
            
   <!--        
 <div class="input-group">
    <span class="input-group-btn">
        <button type="button" class="btn btn-default" data-value="decrease" data-target="#spinner" data-toggle="spinner">
            <span class="glyphicon glyphicon-minus"></span>
        </button>
    </span>
    <input type="text" data-ride="spinner" id="spinner" class="form-control input-number" value="1">
    <span class="input-group-btn">
        <button type="button" class="btn btn-default" data-value="increase" data-target="#spinner" data-toggle="spinner">
            <span class="glyphicon glyphicon-plus"></span>
        </button>
    </span>
</div>
            
       -->     
            
            
            <div class="form-group">
                <label class="col-sm-2 control-label">{{Temperature Defaut}}</label>
                <div class="col-sm-3 input-group">
                   
                    
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default" data-value="decrease" data-target="#defaultTemp" data-toggle="spinner">
                            <span class="glyphicon glyphicon-minus"></span>
                        </button>
                    </span>
                    
                    <input id="defaultTemp" type="text" class="col-sm-2 eqLogicAttr form-control" data-l1key="configuration" data-l2key="defaultTemp" data-l3key="defaultTemp" data-precision="1" data-step="0.5" data-min="5" data-max="25"/>
                    
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default" data-value="increase" data-target="#defaultTemp" data-toggle="spinner">
                            <span class="glyphicon glyphicon-plus"></span>
                        </button>
                    </span>
                
                </div>
            </div>
            
            <div class="form-group">
                <label class="col-sm-2 control-label">{{Temperature Hors gel}}</label>
                <div class="col-sm-3 input-group">
                   
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default" data-value="decrease" data-target="#hgTemp" data-toggle="spinner">
                            <span class="glyphicon glyphicon-minus"></span>
                        </button>
                    </span>
                    
                    <input id="hgTemp" type="text" class="col-sm-2 eqLogicAttr form-control" data-l1key="configuration" data-l2key="defaultTemp" data-l3key="hgTemp" data-precision="1" data-step="0.5" data-min="5" data-max="15"/>
                
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-default" data-value="increase" data-target="#hgTemp" data-toggle="spinner">
                            <span class="glyphicon glyphicon-plus"></span>
                        </button>
                    </span>
                
                </div>
            </div>
            
            
           
            
            
        </fieldset>
</form> 





<form class="form-horizontal" id="form_plage">
    <legend>{{Plages}}</legend>
        <a class="btn btn-success btn-xs pull-left" id="bt_addPlage" style="margin-top: 5px;"><i class="fa fa-plus-circle"></i> {{Ajouter Plage}}</a>

    <br/><br/>
    <table id="table_plage" class="table table-bordered table-condensed">
        <thead>
            <tr>
                <th class="col-sm-1">{{Nom}}</th>
                <th class="col-sm-1">{{Debut}}</th>
                <th class="col-sm-1">{{Fin}}</th>
                <th class="col-sm-1">{{Temperature}}</th>
                <th class="col-sm-2">{{Jour}}</th>
                <th class="col-sm-1">{{Supprimer}}</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</form>
<div id="dtBox"></div>


<!--

<legend>{{Consigne}}</legend>
<a class="btn btn-success btn-sm cmdAction" data-action="add"><i class="fa fa-plus-circle"></i> {{plage}}</a><br/><br/>
<table id="table_cmd" class="table table-bordered table-condensed">
    <thead>
        <tr>
            <th>{{Nom}}</th>
            <th>{{Debut}}</th>
            <th>{{Fin}}</th>
            <th>{{Temperature}}</th>
            <th>{{Supprimer}}</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>
-->

<form class="form-horizontal" id="form_command">
    <legend>{{Commandes}}</legend>
    
    <br/>
    <table id="table_cmd" class="table table-bordered table-condensed">
        <thead>
            <tr>
                <th class="col-sm-1">{{Nom}}</th>
                <th class="col-sm-3">{{configuration}}</th>
               
            </tr>
        </thead>
        <tbody>
      
            
        </tbody>
    </table>
</form>






<form class="form-horizontal">
    <fieldset>
        <div class="form-actions">
            <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
            <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
        </div>
    </fieldset>
</form>

</div>
</div>




<?php include_file('3rdparty', 'spin/bootstrap-spinner', 'js', 'ssaThermostat'); ?>

<?php include_file('3rdparty', 'datepicker/DateTimePicker', 'css', 'ssaThermostat'); ?>
<?php include_file('3rdparty', 'datepicker/DateTimePicker', 'js', 'ssaThermostat'); ?>
<?php include_file('3rdparty', 'datepicker/i18n/DateTimePicker-i18n', 'js', 'ssaThermostat'); ?>

<?php include_file('desktop', 'ssaThermostat', 'js', 'ssaThermostat');?>
<?php include_file('core', 'plugin.template', 'js');?>