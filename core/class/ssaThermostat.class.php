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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class ssaThermostat extends eqLogic {
    /*     * *************************Attributs****************************** */



    /*     * ***********************Methode static*************************** */

    /*
     * Fonction exécutée automatiquement toutes les minutes par Jeedom
     */
    
    public static function cron() 
    { 
     
      
        foreach (eqLogic::byType('ssaThermostat') as $eqLogic) {
    	
    		$autorefresh = $eqLogic->getConfiguration('autorefreshPid');
    		if ($eqLogic->getIsEnable() == 1 && $autorefresh != '') {
    			try {
    				$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
    				if ($c->isDue()) {
    					try {
                                                $eqLogic->triggerPilote( array('thermostat_id' => intval($eqLogic->getId())));
                                                
    						
    					} catch (Exception $exc) {
    						log::add('ssaThermostat', 'error', $eqLogic->getHumanName().'['.__FUNCTION__.']' . ' : ' . $exc->getMessage());
    					}
    				}
    			} catch (Exception $exc) {
    				log::add('ssaThermostat', 'error', $eqLogic->getHumanName().'['.__FUNCTION__.']' .' : Error ' );
    			}
    		}
    	} 
      
     
     
     
    } 
    
    
    public static function cron15($_eqLogic_id = null, $_force = false)
    {
		if ($_eqLogic_id == null) {
			$eqLogics = self::byType('ssaThermostat');
		} else {
			$eqLogics = array(self::byId($_eqLogic_id));
		}
		
		foreach ($eqLogics as $eqLogic)
                {
                    try {
                            $eqLogic->updateConsigne( array('thermostat_id' => intval($eqLogic->getId())));
                        } catch (Exception $exc) {
                            log::add('ssaThermostat', 'error', $eqLogic->getHumanName().'['.__FUNCTION__.']' .' : ' . $exc->getMessage());
                             
                        }
		}
    }
    

    /*
     * Fonction exécutée automatiquement toutes les heures par Jeedom
      public static function cronHourly() {

      }
     */

    /*
     * Fonction exécutée automatiquement tous les jours par Jeedom
      public static function cronDayly() {

      }
     */



    /*     * *********************Méthodes d'instance************************* */
    public static function setOnOff($_options)
    {   $ssaEqlogicObj = ssaThermostat::byId($_options['thermostat_id']);
        
        if (is_object($ssaEqlogicObj) && $ssaEqlogicObj->getIsEnable() == 1)
        {   $ssaCmdMode= $ssaEqlogicObj->getCmd(null, 'mode');
            $ssaCmdMode->setConfiguration('etat', $_options['ordre']);
            $ssaCmdMode->save();
            $ssaEtat= $ssaEqlogicObj->getCmd(null, 'etat'); 
            $ssaEtat->setCollectDate('');
            $ssaEtat->event($_options['ordre']);
            log::add('ssaThermostat','debug', $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' :  change to '.$_options['ordre']);
        }
        
        
    }
   
     
    public static function triggerPilote($_options)
    {   $thermostat_id=$_options['thermostat_id'];
    
        $ssaEqlogicObj = ssaThermostat::byId($_options['thermostat_id']);
        if (is_object($ssaEqlogicObj) && $ssaEqlogicObj->getIsEnable() == 1)
        {   $log_etat=sprintf("________________________________debut________________________________");
            log::add('ssaThermostat','debug', $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
            
        
            $ssaCmdMode= $ssaEqlogicObj->getCmd(null, 'mode');
           
            $ssacommandePilote=$ssaEqlogicObj->getConfiguration('commande');
            if ($ssacommandePilote["tempSonde"]=='')
            {
               $log_etat=sprintf("pas de sonde");
               log::add('ssaThermostat','debug',  $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
               return ;
           
            }
            
            //mesure temperature local
            try {
                $localTemp=cmd::byString($ssacommandePilote["tempSonde"])->execCmd();
                
                            
                } catch (Exception $exc) {
                    log::add('ssaThermostat', 'error', $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .' : ' . $exc->getMessage());
                    return; 
                }
            
           
            
               
           
             
            
            
            
            $dataPid=$ssaCmdMode->getConfiguration('dataPid');
            $mode=$ssaCmdMode->getConfiguration('mode');
            $etat=$ssaCmdMode->getConfiguration('etat');
            $consigne=$ssaCmdMode->getConfiguration('consigne');
            
            if ($consigne["temp"]=='')
            {  $log_etat=sprintf("consigne not set");
               log::add('ssaThermostat','debug',  $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
               return ;
                
            }
            
            if ($etat=='')
            {
                $etat='off';
            }
         
            
            $log_etat=sprintf("mode[%s] etat[%s]",$mode,$etat);
            log::add('ssaThermostat','debug',  $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
            
            

            if ($mode=='on' && $etat=='off')
            {   $ssaEqlogicObj->setOnOff(array('thermostat_id'=>$thermostat_id,'ordre'=>'on'));
                //cmd marche

                return ;
            }

            if ($mode=='off' && $etat=='on')
            {  $ssaEqlogicObj->setOnOff(array('thermostat_id'=>$thermostat_id,'ordre'=>'off'));
               //cmd arret

               return ;
            }

           
           

            if ( $mode=='auto' || $mode=='hg')
            {  
                $pid= new ssaThermostatPID($dataPid,$ssaEqlogicObj->getHumanName(),$consigne["temp"],$localTemp);
              
                
                $pid->pilote();
                
                
                $newData=$pid->getPidData();
                $log_etat=  json_encode($newData);
                log::add('ssaThermostat','debug',  $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
            
                
                $ssaEqlogicObj->setDataPid($ssaCmdMode,$newData);
                
               
            
                //ordre script pid
                $relay=$newData['Relay'];
                
               
                
                $log_etat=sprintf("relay[%s]",$relay);
                log::add('ssaThermostat','debug',  $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
                
                
                if ($relay=='on')
                {   if ($etat=='off') 
                    {       
                           $ssaEqlogicObj->setOnOff(array('thermostat_id'=>$thermostat_id,'ordre'=>'on'));
                            //commande marche
                    }
                    else
                    {   
                        $log_etat=sprintf("etat -> deja On");
                        log::add('ssaThermostat','debug',  $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
               
                    }
                } 
                else
                { 
                    if ($etat=='on') 
                    {   
                        $ssaEqlogicObj->setOnOff(array('thermostat_id'=>$thermostat_id,'ordre'=>'off'));
                        //commande arret
                    }
                    else
                    {  
                       
                       $log_etat=sprintf("etat -> deja Off");
                       log::add('ssaThermostat','debug',  $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
                    }
                }  

            } 
         }
        
        
        
    }
    
    
    
    
    
    
    public function preInsert() {
        
    }

    public function postInsert() {
       
       
       $mode= $this->createInfoCmd("mode", '', 1, "string");
        
       $this->createActionCmd("on",$mode);
       $this->createActionCmd("auto",$mode);
       $this->createActionCmd("off",$mode);
       $this->createActionCmd("hg",$mode);
       
       $ssaThermostatMode= $this->getCmd(null, 'mode'); 
       $this->setDataPid($ssaThermostatMode,array());
        
    }

   
    
    public function preSave() 
    {   $defautTemp=7;
        
        if ($this->getConfiguration('autorefreshPid') == '') 
        {
                $this->setConfiguration('autorefreshPid', '* * * * *');
	}
        
        if ($this->getConfiguration('defaultTemp')!='')
        {   $temp=$this->getConfiguration('defaultTemp');
            //verification consigne HG
            if ($temp["defaultTemp"]=='')
                 $temp["defaultTemp"]=$defautTemp;

            //verification consigne defaut
            if ($temp["hgTemp"]=='')
                 $temp["hgTemp"]=$defautTemp;
            $this->setConfiguration('defaultTemp',$temp);
        
        }
        
        //verification consigne Plages
        if ($this->getConfiguration('plages') != '') 
        {  $plages=$this->getConfiguration('plages');
           foreach ($plages as $k => $plage)
           {   
               //debut
               if ($plage["debut"]=='')
               {   $log_etat=sprintf("heure debut[%s] vide",$plage["name"]);
                   log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
                   unset( $plages[$k]);
                   continue;
               }   
               
               //debut
               if ($plage["fin"]=='')
               {   $log_etat=sprintf("heure fin[%s] vide",$plage["name"]);
                   log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
                   $plages[$k]["fin"]=$plage["debut"];
               }
               
               //name
               if ($plage["name"]=='')
               {   $log_etat=sprintf("heure name[%s] vide",$plage["name"]);
                   log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
                   $plages[$k]["name"]="plage_".$k;
               }
               
               //consigne
               if ($plage["consigne"]=='')
               {   $log_etat=sprintf("consigne[%s] vide",$plage["name"]);
                   log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
                   $plages[$k]["consigne"]=$defautTemp;
               }
               
                
            
           }
           $this->setConfiguration('plages',$plages);
            
        }
        
        
    }
    
    public static function setDataPid($ssaObj, $value)
    {
         //conf pid
        if ($ssaObj->getConfiguration('dataPid') == '') 
        {   
            
            $conf= array(
            'kp' => 2,
            'ki' => 5,
            'kd' => 1,
            'controllerdirection' =>'DIRECT',
            'output' =>0,
            'inAuto' =>'false',
            'SampleTime' =>10,
            'lastTime' => time(),
            'ITerm' => 0,                          
            'lastInput' => 0 ,
            'outMin' => 0,
            'outMax' => 100,
            'WINDOWS_runing' => 'off',
            'WINDOWS_size' =>600,
            'WINDOWS_start_time' =>0,
            'WINDOWS_on_size' =>0,
            'Relay' =>'off'
             );
            
	}
        else {
            $conf=$ssaObj->getConfiguration('dataPid');
            
        }
        
        foreach ($value as $k => $v)
        {   $conf[$k]=$v;
            
        }
        try {
            $ssaObj->setConfiguration('dataPid', $conf);
            $ssaObj->save();
        
        } catch (Exception $exc) {
            log::add('ssaThermostat', 'error',' : ' . $exc->getMessage());
                             
        }
        
        
    }
    

    public function postSave() 
    {
       // $this->updateConsigne( array('thermostat_id' => intval($this->getId())));
        
    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
        
     
               
        
    }
    
    public  function createInfoCmd($name,$unit,$onlyEvent,$subType)
    {
        $ssaThermostatCmd = $this->getCmd(null, $name);
	if (!is_object($ssaThermostatCmd)) {
            $ssaThermostatCmd = new ssaThermostatCmd();
	}
	$ssaThermostatCmd->setName(__($name, __FILE__));
	$ssaThermostatCmd->setLogicalId($name);
	$ssaThermostatCmd->setEqLogic_id($this->getId());
	$ssaThermostatCmd->setUnite($unit);
        $ssaThermostatCmd->setType('info');
	$ssaThermostatCmd->setEventOnly($onlyEvent);
	$ssaThermostatCmd->setSubType($subType);
       // $ssaThermostatCmd->setIsVisible(1);
	$ssaThermostatCmd->save(); 
        
        return $ssaThermostatCmd->getId();
        
    }
    
    public  function createActionCmd($name,$value)
    {
        $ssaThermostatCmd = $this->getCmd(null, $name);
	if (!is_object($ssaThermostatCmd)) {
            $ssaThermostatCmd = new ssaThermostatCmd();
	}
	$ssaThermostatCmd->setName(__($name, __FILE__));
	$ssaThermostatCmd->setLogicalId($name);
	$ssaThermostatCmd->setEqLogic_id($this->getId());
	
        $ssaThermostatCmd->setType("action");
        //$ssaThermostatCmd->setValue($value);
	
	$ssaThermostatCmd->setSubType("other");
       
	$ssaThermostatCmd->save(); 
        
        
       
        
        return true;
        
    }

    public static function updateMode($_options)
    {   
        $ssaEqlogicObj = ssaThermostat::byId($_options['thermostat_id']);
       
        if (is_object($ssaEqlogicObj) && $ssaEqlogicObj->getIsEnable() == 1)
        {
            if (jeedom::isDateOk()) 
            {    
                $ssaThermostatMode= $ssaEqlogicObj->getCmd(null, 'mode'); 
                $ssaThermostatMode->setConfiguration('mode',$_options['mode']);
                $ssaThermostatMode->setCollectDate('');
                $ssaThermostatMode->event($_options['mode']);
                
                if ($_options['mode']=='auto' or $_options['mode']=='hg')
                {  
                     $ssaEqlogicObj->setDataPid($ssaThermostatMode,array('inAuto'=>'true'));
                    
                }
                else {
                     $ssaEqlogicObj->setDataPid($ssaThermostatMode,array('inAuto'=>'false'));
                }
                $ssaThermostatMode->save();             
                $log_etat=sprintf("set [%s]",$_options['mode']);
                log::add('ssaThermostat','debug',  $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
          
                
                
            }
        }
        
        
        
    }
    
    public static function updateConsigne($_options) 
    {   $ssaEqlogicObj = ssaThermostat::byId($_options['thermostat_id']);
	
        if (is_object($ssaEqlogicObj) && $ssaEqlogicObj->getIsEnable() == 1)
        {
    
            $time=date('Hi', time() );
            $now=DateTime::createFromFormat('Hi', $time);

           
            $plages=$ssaEqlogicObj->getConfiguration('plages');

            $default=$ssaEqlogicObj->getConfiguration('defaultTemp');

            //var_dump($plages);
            $consigneTemp = $default["defaultTemp"];
            $consignePlage= "plage par defaut";
            foreach ($plages as $plage)
            { 
                $debut = DateTime::createFromFormat('H:i', $plage["debut"]);
                $fin = DateTime::createFromFormat('H:i', $plage["fin"]);
                if ($now >= $debut &&  $now < $fin)
                {   log::add('ssaThermostat','debug', $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : plage ['.$plage["name"].'] ->'.$plage["debut"]." ".$plage["fin"] );
                    $consigneTemp =  $plage["consigne"];
                    $consignePlage=  $plage["name"];
                }


            }
        
           
            $ssaCmd= $ssaEqlogicObj->getCmd(null, 'mode');
            $mode=$ssaCmd->execCmd();
            if ($mode=="hg")
            {   
                $consigneTemp = $default["hgTemp"];
                $consignePlage= "temperature hors gel";
            }
            
            log::add('ssaThermostat','debug', $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' . ' : plage ['.$consignePlage .'] consigne ['.$consigneTemp.']');
            if (jeedom::isDateOk()) 
            {
                $ssaThermostatMode=$ssaEqlogicObj->getCmd(null, 'mode');
                $ssaThermostatMode->setConfiguration('consigne',array('plage'=>$consignePlage,'temp'=>$consigneTemp));
                $ssaThermostatMode->save();
                
            }
	
	}
    }
    
    
   
    

    public function preRemove() {
        
    }

    public function postRemove() {
        
    }

    
    
   

    public function toHtml($_version = 'dashboard')
    {
        if ($this->getIsEnable() != 1) {
            return '';
        }
        
        $_version = jeedom::versionAlias($_version);
        $mc = cache::byKey('ssaThermostatWidget' . $_version . $this->getId());
        if ($mc->getValue() != '') {
            return $mc->getValue();
        }
        /*
        $html_forecast = '';

        if ($_version != 'mobile' || $this->getConfiguration('fullMobileDisplay', 0) == 1) {
           $thermostat_template = getTemplate('core', $_version, 'simpleThermostat', 'ssaThermostat');
            
        }*/
        
        $ssaCmdMode= $this->getCmd(null, 'mode');
        $mode=$ssaCmdMode->getConfiguration('mode');
        $etat=$ssaCmdMode->getConfiguration('etat');
        
        $replace = array(
            '#id#' => $this->getId(),
            '#name#'=>$this->getName(),
            '#background_color#' => $this->getBackgroundColor($_version),
            '#eqLink#' => $this->getLinkToConfiguration(),
            '#etat#'=>$etat,
            '#mode#'=>$mode
            
                
            
        );
       
        foreach ($this->getCmd('action') as $cmd) {
            $replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
            if ($mode==$cmd->getLogicalId())
              $replace['#mode_' . $cmd->getLogicalId() . '#'] = 'ssaThermoButtonSelect';
            else
              $replace['#mode_' . $cmd->getLogicalId() . '#'] = '';  
	}
        
        
        $html = template_replace($replace, getTemplate('core', $_version, 'simpleThermostat', 'ssaThermostat'));
        cache::set('ssaThermostatWidget' . $_version . $this->getId(), $html, 60);
        return $html;
    }

    /*     * **********************Getteur Setteur*************************** */
}

class ssaThermostatCmd extends cmd {
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

    public function execute($_options = array())
    {   
        $eqLogic = $this->getEqLogic();
        log::add('ssaThermostat','debug',  $eqLogic->getHumanName().'['.__FUNCTION__.']' . ' : appel');
        
        $eqLogic->updateMode( array('thermostat_id' => intval($eqLogic->getId()),'mode' =>$this->getLogicalId() ) );
        $eqLogic->updateConsigne( array('thermostat_id' => intval($eqLogic->getId()),'mode' =>$this->getLogicalId() ) );
        return $this->getLogicalId();
        
    }

    /*     * **********************Getteur Setteur*************************** */
}

?>
