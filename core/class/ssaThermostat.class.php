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
    private  $_errState="ok";
    private  $_errMsg="";
    
    
    public static function cron() 
    { 
        foreach (eqLogic::byType('ssaThermostat') as $eqLogic) {
    	
    		$autorefresh = $eqLogic->getConfiguration('autorefreshPid');
    		if ($eqLogic->getIsEnable() == 1 && $autorefresh != '') {
    			try {
    				$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
    				if ($c->isDue()) {
    					try {
                                                $eqLogic->pilote();
                                                
    						
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
    

   



    /*     * *********************Méthodes d'instance************************* */
    private function setOnOff($ordre)
    {   
        $cmd=array('CmdOff','CmdOn');
        if ( $this->getIsEnable() == 1)
        {   $ssaCmdEtat= $this->getCmd(null, 'etat');
            $ssaCmdEtat->setConfiguration('etat_id', $ordre);
            $ssaCmdEtat->setCollectDate('');
            $ssaCmdEtat->event($ordre);
            $ssaCmdEtat->save();
           
            
            //commande
            
            //excecute
            try {
                $ssacommandePilote=$this->getConfiguration('commande');
                //CmdOff
                $localTemp=cmd::byString($ssacommandePilote[$cmd[$ordre] ])->execCmd();
                            
                } catch (Exception $exc) {
                    log::add('ssaThermostat', 'error', $this->getHumanName().'['.__FUNCTION__.']' .' : ' . $exc->getMessage());
                    $this->_errState='Err';
                    $this->_errMsg=$exc->getMessage();
                }
            log::add('ssaThermostat','debug', $this->getHumanName().'['.__FUNCTION__.']' .  ' :  change to '.$ordre );
        }
        
        
    }
   
   
   
    
    
    
    
    public function preInsert() {
        
    }

    public function postInsert() {
       //createInfoCmd($name,$unit,$onlyEvent,$subType, $configuration,$default)
       //$ssaThermostatMode->setConfiguration('mode_lib',$tabmode[$currentMode]);
       $modeConf=array('mode_lib'=>'Off','mode_id'=>0) ;
       $mode= $this->createInfoCmd("mode", '', 1, "string",$modeConf,'Off');
      
     
       $temp= $this->createInfoCmd("temperature", '°C', 1, "numeric",array(),0);
       $consigne= $this->createInfoCmd("consigne", '°C', 1, "numeric",array(),7);
       $etat= $this->createInfoCmd("etat", '', 1, "binary",array('etat_id'=>0),0);

      
       
       $this->createActionCmd("rollUp",$mode);
       $this->createActionCmd("rollDown",$mode);
       
       
       $this->setDataPid(array());
       
       
       
        
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
    
    private function setDataPid($value)
    {
         //conf pid
        $conf=$this->getConfiguration('dataPid');
        
        /*
        $log_etat=sprintf("conf [%s]",json_encode($conf));
        log::add('ssaThermostat','debug', $this->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
       
        $log_etat=sprintf("value [%s]",json_encode($value));
        log::add('ssaThermostat','debug', $this->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
        */
        if ($conf == '') 
        {   
            
            $conf= array(
            'kp' => 45,
            'ki' => 0.05,
            'kd' => 1,
            'controllerdirection' =>'DIRECT',
            'output' =>0,
            'inAuto' =>'true',
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
      
        foreach ($value as $k => $v)
        {   $conf[$k]=$v;
            
        }
        
        $this->setConfiguration('dataPid', $conf);
        $this->save();
        /*
        $log_etat=sprintf("fin [%s]",json_encode($conf));
        log::add('ssaThermostat','debug', $this->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
           */
        
        
    }
    

    public function postSave() 
    {
       // $this->updateConsigne( array('thermostat_id' => intval($this->getId())));
        
    }

    public function preUpdate() {
        
    }

    public function postUpdate() {
        
     
               
        
    }
    /* @param string $name
     * @param string $unit
     * @param string $onlyEvent
     * @param string $subType
     * @param string $configuration
     * @param string $default
     * 
     */
    
    public  function createInfoCmd($name,$unit,$onlyEvent,$subType, $configuration,$default)
    {   //numeric   
        $ssaThermostatCmd = $this->getCmd(null, $name);
	if (!is_object($ssaThermostatCmd)) {
            $ssaThermostatCmd = new ssaThermostatCmd();
	}
	$ssaThermostatCmd->setName(__($name, __FILE__));
	$ssaThermostatCmd->setLogicalId($name);
	$ssaThermostatCmd->setEqLogic_id($this->getId());
	$ssaThermostatCmd->setUnite($unit);
        $ssaThermostatCmd->setType('info');
        $ssaThermostatCmd->setValue($default);
	$ssaThermostatCmd->setEventOnly($onlyEvent);
	$ssaThermostatCmd->setSubType($subType);
        $ssaThermostatCmd->setIsHistorized(1);
       // $ssaThermostatCmd->setIsVisible(1);
        foreach($configuration as $cle=>$valeur){
                // Affichage
		
                $ssaThermostatCmd->setConfiguration($cle,$valeur);
	}
        
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
        
        $log_etat=sprintf("entrer %s",$_version);
        log::add('ssaThermostat','debug', $this->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
       
       
        $mc = cache::byKey('ssaThermostatWidget' . $_version . $this->getId());
       
        if ($mc->getValue() != '') {
            return $mc->getValue();
        }
        
            
        $localConsigne=$this->getConsigne();
        list($consigne_int, $consigne_dec)=explode(".",number_format( (double)$localConsigne,1));
         
       
        $localMode=$this->getModeLib();
        $localTemp=$this->getTemperature();
        list($temp_int, $temp_dec)=explode(".",number_format( (double)$localTemp,1));
        $localEtat=$this->getEtat();
       
       
        
        
        
        
        $replace = array(
            '#id#' => $this->getId(),
            '#uid#' => '_eq' . $this->getId() . eqLogic::UIDDELIMITER . mt_rand() . eqLogic::UIDDELIMITER,
            '#name#'=>$this->getName(),
            '#background_color#' => $this->getBackgroundColor($_version),
            '#eqLink#' => $this->getLinkToConfiguration(),
            '#etat#'=>$localEtat,
            '#mode_lib#'=>$localMode,
            '#consigne_int#'=>$consigne_int  ,  
            '#consigne_dec#'=>$consigne_dec ,  
            '#temp_int#'=>$temp_int  ,  
            '#temp_dec#'=>$temp_dec
            
        );
       
        //Commande
        foreach ($this->getCmd('action') as $cmd) {
            $replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
             
	}
        $liste_info=array();
        foreach ($this->getCmd('info') as $cmd) {
            if ($cmd->getLogicalId() != 'mode')
                $liste_info[]=$cmd->getId();
            
             
	}
        $replace['#liste_info#'] = implode(',',$liste_info);
             
        //roue
        if ($localEtat==1)
        {    $replace['#activate#'] = 'roue'; 
             $replace['#activateTexte#'] = 'On' ;
        }
        else
        {    $replace['#activate#'] = ''; 
             $replace['#activateTexte#'] = 'Off' ;
        
        }
        $replace['#$errState#'] = $this->_errState;
        $replace['#result_msg#'] = htmlentities($this->getName().": ".str_replace("'", " ", $this->_errMsg));
        $html = template_replace($replace, getTemplate('core', $_version, 'simpleThermostat', 'ssaThermostat'));
        cache::set('ssaThermostatWidget' . $_version . $this->getId(), $html, 60);
        return $html;
    }
    
    
    
    public function refreshScreen()
    {   
        $_version = jeedom::versionAlias("dashboard"); 
        $mc = cache::byKey('ssaThermostatWidget' . $_version . $this->getId());
        $mc->remove();
	$this->toHtml('dashboard');
        
        
        $_version = jeedom::versionAlias("mobile"); 
        $mc = cache::byKey('ssaThermostatWidget' . $_version . $this->getId());
        $mc->remove();
	$this->toHtml('mobile');
        
	
	$this->refreshWidget();
    }
    
    
    /*     * **********************Reactoringr*************************** */
     public static function roll($_options)
    {   
        $tabmode=array("Off","On","Auto","Hors gel");
        $thermostat_id= $_options['thermostat_id'];
        $action= $_options['action'];
        $ssaEqlogicObj = ssaThermostat::byId($thermostat_id);
        

        if (is_object($ssaEqlogicObj) && $ssaEqlogicObj->getIsEnable() == 1)
        {
            if (jeedom::isDateOk()) 
            {    
                $ssaThermostatMode= $ssaEqlogicObj->getCmd(null, 'mode'); 
                $currentMode=$ssaThermostatMode->getConfiguration('mode_id');
                if ($action=="up")
                {  $currentMode=$currentMode+1;
                   if ($currentMode >3)
                      $currentMode =3; 

                }
                else
                {  $currentMode=$currentMode - 1;
                   if ($currentMode <0)
                      $currentMode = 0; 

                }
                $log_etat=sprintf("set [%s]",$tabmode[$currentMode]);
                log::add('ssaThermostat','debug',  $ssaEqlogicObj->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);


                $ssaThermostatMode->setConfiguration('mode_id',$currentMode);
                $ssaThermostatMode->setConfiguration('mode_lib',$tabmode[$currentMode]);
                
                $ssaThermostatMode->setCollectDate('');
                $ssaThermostatMode->event($tabmode[$currentMode]);
                
                
                $ssaThermostatMode->save();  
                $ssaEqlogicObj->pilote();
                
            }
            
        }
    }
    
    
      //0->off 1-->on
    private function getEtat()
    {
        $log_etat=sprintf("enter");
        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
        $ssaThermostatCmd= $this->getCmd(null, 'etat');
        $currentEtat=$ssaThermostatCmd->getConfiguration('etat_id');
        return $currentEtat;


    }
    
    private function getTemperatureCmdId()
    {  $ssaThermostatCmd= $this->getCmd(null, 'temperature');
       return $ssaThermostatCmd->getId();
        
    }
    
    //calcule la temperature et la sauvegarde dans lobjet
    private function getTemperature()
    {   $localTemp=0;
        $log_etat=sprintf("enter");
        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
      
        $ssacommandePilote=$this->getConfiguration('commande');
        if ($ssacommandePilote["tempSonde"]=='')
        {
            $log_etat=sprintf("pas de sonde");
            log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
            
        }
        else    
        {
        //mesure temperature local
            try {
                $localTemp=cmd::byString($ssacommandePilote["tempSonde"])->execCmd();
                            
                } catch (Exception $exc) {
                    log::add('ssaThermostat', 'error', $this->getHumanName().'['.__FUNCTION__.']' .' : ' . $exc->getMessage());
                    $this->_errState='Err';
                    $this->_errMsg=$exc->getMessage();
                }
        }

        $log_etat=sprintf("set temperature [%s]",$localTemp);
        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
        

        $ssaThermostatCmd= $this->getCmd(null, 'temperature');
        $ssaThermostatCmd->setConfiguration('degres',$localTemp);
                
        $ssaThermostatCmd->setCollectDate('');
        $ssaThermostatCmd->event($localTemp);
                
                
        $ssaThermostatCmd->save();   
        return $localTemp;


    }
    
    private function getDataPid()
    {
        $log_etat=sprintf("enter");
        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
        $currentPid=$this->getConfiguration('dataPid');
        return $currentPid;
        
    }
    
    private function isNotWorkable($date)
    {
 
        if ($date === null)
        {
            $date = time();
        }
 
        $date = strtotime(date('m/d/Y',$date));
 
        $year = date('Y',$date);
 
        $easterDate  = easter_date($year);
        $easterDay   = date('j', $easterDate);
        $easterMonth = date('n', $easterDate);
        $easterYear   = date('Y', $easterDate);
 
        $holidays = array(
        // Dates fixes
        mktime(0, 0, 0, 1,  1,  $year),  // 1er janvier
        mktime(0, 0, 0, 5,  1,  $year),  // Fête du travail
        mktime(0, 0, 0, 5,  8,  $year),  // Victoire des alliés
        mktime(0, 0, 0, 7,  14, $year),  // Fête nationale
        mktime(0, 0, 0, 8,  15, $year),  // Assomption
        mktime(0, 0, 0, 11, 1,  $year),  // Toussaint
        mktime(0, 0, 0, 11, 11, $year),  // Armistice
        mktime(0, 0, 0, 12, 25, $year),  // Noel
 
        // Dates variables
        mktime(0, 0, 0, $easterMonth, $easterDay + 1,  $easterYear),
        mktime(0, 0, 0, $easterMonth, $easterDay + 39, $easterYear),
        mktime(0, 0, 0, $easterMonth, $easterDay + 50, $easterYear),
        );
 
      return in_array($date, $holidays);
    }
    
    private  function getConsigne()
    {
        $log_etat=sprintf("enter");
        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
        
        $consigneTemp=7.5;
        
        if ( $this->getIsEnable() == 1)
        {
            //typeOfDay
            $today= time();
            if ($this->isNotWorkable($today))
                $typeOfDay='f';
            else
            {   $day=array("d","l","ma","me","j","v","s");
                $typeOfDay=$day[strftime("%w")];
            }
            
            
            
            
            $time=date('Hi', time() );
            $now=DateTime::createFromFormat('Hi', $time);

           
            $plages=$this->getConfiguration('plages');
            $default=$this->getConfiguration('defaultTemp');

            
            $consigneTemp = $default["defaultTemp"];
            $consignePlage= "plage par defaut";
            
            foreach ($plages as $plage)
            {   $calendrier=$plage["calendrier"];
            
                $debut = DateTime::createFromFormat('H:i', $plage["debut"]);
                $fin = DateTime::createFromFormat('H:i', $plage["fin"]);
                if ($now >= $debut &&  $now < $fin &&  in_array($typeOfDay,$calendrier ))
                {   
                    
                    $log_etat=sprintf('plage [%s]->[%s,%s] jour[%s]',$plage["name"],$plage["debut"],$plage["fin"],$typeOfDay);
                    log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
        
                
                    $consigneTemp =  $plage["consigne"];
                    $consignePlage=  $plage["name"];
                }


            }
        
            $localMode=$this->getMode();
            
            if ($localMode==3)
            {   
                $consigneTemp = $default["hgTemp"];
                $consignePlage= "temperature hors gel";
            }
            //float
            $consigneTemp=  number_format($consigneTemp, 1);
            
            $log_etat=sprintf('plage [%s] consigne [%s]',$consignePlage,$consigneTemp);
            log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
        
            if (jeedom::isDateOk()) 
            {
                $ssaThermostatCmd= $this->getCmd(null, 'consigne');
                $ssaThermostatCmd->setConfiguration('degres',$consigneTemp);
                $ssaThermostatCmd->setConfiguration('plage',$consignePlage);
                $ssaThermostatCmd->setCollectDate('');
                $ssaThermostatCmd->event($consigneTemp);
                $ssaThermostatCmd->save(); 
                
            }
	
	}
        return $consigneTemp;
       


    }

    
      //recupere le mode de l'objet
    //("Off","On","Auto","Hors gel");
    private  function getMode()
    {
        $log_etat=sprintf("enter");
        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
        $ssaThermostatCmd= $this->getCmd(null, 'mode');
        $currentMode=$ssaThermostatCmd->getConfiguration('mode_id');
        return $currentMode;


    }
    
    private function getPidOrder()
    {  
        $log_etat=sprintf("enter consigne[%s] temp[%s]",$this->getConsigne(),$this->getTemperature());
        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
       
        $pid= new ssaThermostatPID($this->getDataPid(),$this->getHumanName(),$this->getConsigne(),$this->getTemperature());
        $ordre=$pid->pilote();
        $this->setDataPid($pid->getPidData());
        
        $log_etat=sprintf("pid result  [%s]",$ordre);
        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
       
        
        if ($ordre=='on')
            return 1;
        else
            return 0;
        
        
    }
    
       //recupere le mode de l'objet
    //("Off","On","Auto","Hors gel");
    private  function getModeLib()
    {
        $log_etat=sprintf("get");
        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
        $ssaThermostatCmd= $this->getCmd(null, 'mode');
        $currentMode=$ssaThermostatCmd->getConfiguration('mode_lib');
        return $currentMode;


    }
    
    private function pilote()
    {   
        $log_etat=sprintf("entrer");
        log::add('ssaThermostat','debug', $this->getHumanName().'['.__FUNCTION__.']' .  ' : '.$log_etat);
            
        if ($this->getIsEnable() == 1)
        {   
            $localConsigne=$this->getConsigne();
            $localMode=$this->getMode();
            $localTemp=$this->getTemperature();
            $localEtat=$this->getEtat();
       
            if ($localEtat=='')
            {
                $localEtat=0;
            }
            $log_etat=sprintf("mode[%s] etat[%s]",$localMode,$localEtat);
            log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);

            //("Off","On","Auto","Hors gel");
            if ($localMode==1 && $localEtat==0)
            {   $this->setOnOff(1);
                //cmd marche
               
            }
            if ($localMode==0 && $localEtat==1)
            {  $this->setOnOff(0);
               //cmd arret              
            }

           
           

            if ( $localMode>1)
            {  
              
                //0:mustOff 1:mustOn
                $mustOnOff=$this->getPidOrder();
                
                $log_etat=sprintf("mustOnOff[%s] localEtat[%s]",$mustOnOff, $localEtat);
                log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
               
                if ($mustOnOff==1)
                {   if ($localEtat==0) 
                    {     $this->setOnOff(1);  
                          //commande marche
                    }
                    else
                    {   
                        $log_etat=sprintf("etat -> deja On");
                        log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
               
                    }
                } 
                else
                { 
                    if ($localEtat==1) 
                    {   $this->setOnOff(0); 
                        
                        //commande arret
                    }
                    else
                    {  
                       
                       $log_etat=sprintf("etat -> deja Off");
                       log::add('ssaThermostat','debug',  $this->getHumanName().'['.__FUNCTION__.']' .  ' : '. $log_etat);
                    }
                }  

            } 
            $this->refreshScreen();
            
         }
        
        
        
    }
    
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
        log::add('ssaThermostat','debug',  $eqLogic->getHumanName().'['.__FUNCTION__.']' . ' : appel '.$this->getLogicalId());
       
               
        
        if ($this->getLogicalId() == 'rollUp') 
        {  $eqLogic->roll(array('thermostat_id' => intval($eqLogic->getId()),'action' =>'up' ));

        }
        if ($this->getLogicalId() == 'rollDown') 
        {  $eqLogic->roll(array('thermostat_id' => intval($eqLogic->getId()),'action' =>'down' ));
        }
        
        
        //$eqLogic->refreshScreen();
        
        return $this->getLogicalId();
        
    }

    /*     * **********************Getteur Setteur*************************** */
}

?>