<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


/**
 * Description of ssaThermostat
 *
 * @author stephane sautron
 */

class ssaThermostatPID
{


    

  protected  $now;
  
  protected  $kp;                  // * (P)roportional Tuning Parameter
  protected  $ki;                  // * (I)ntegral Tuning Parameter
  protected  $kd;                  // * (D)erivative Tuning Parameter
  protected  $controllerdirection;
  protected  $Input;              // * Pointers to the Input, Output, and Setpoint variables
  protected  $Output;             //   This creates a hard link between the variables and the 
  protected  $Setpoint;           //   PID, freeing the user from having to constantly tell us
  protected  $inAuto;
  protected  $SampleTime;
  protected  $lastTime;
  protected  $ITerm;
  protected  $lastInput;
  protected  $outMin;
  protected  $outMax;

  protected  $WINDOWS_runing ;
  protected  $WINDOWS_size;
  protected  $WINDOWS_start_time;
  protected  $WINDOWS_on_size;
  protected  $Relay;
  protected  $thermostat;

  function getOutput()
  {   return $this->Output ;
  }

  function getTime()
  {  return $this->now();

  }



    function getPidData() 
    {   
      
      
        $conf= array(
            'kp' =>$this->kp,
            'ki'=>$this->ki,
            'kd'=>$this->kd,
            'controllerdirection' =>$this->controllerdirection,
            'output' =>$this->Output,
            'inAuto' =>$this->inAuto,
            'SampleTime' =>$this->SampleTime,
            'lastTime' =>$this->lastTime,
            'ITerm' =>$this->ITerm,                          
            'lastInput' =>$this->lastInput,
            'outMin' =>$this->outMin,
            'outMax' =>$this->outMax,
            'WINDOWS_runing' =>$this->WINDOWS_runing,
            'WINDOWS_size' =>$this->WINDOWS_size,
            'WINDOWS_start_time' =>$this->WINDOWS_start_time,
            'WINDOWS_on_size' =>$this->WINDOWS_on_size,
            'Relay' =>$this->Relay
             );
        return $conf;
     
    
  }




   function __construct($configuration, $thermostat,$consigne, $temperature)
   {

      $this->now=time();
     
      
      /*working variables*/
      
      $this->lastTime=$configuration['lastTime'];
      $this->ITerm = $configuration['ITerm'];
      $this->lastInput= $configuration['lastInput'];
      $this->kp= $configuration['kp'];
      $this->ki= $configuration['ki'];
      $this->kd= $configuration['kd'];
      $this->SampleTime = $configuration['SampleTime'];
      $this->outMin=$configuration['outMin'];
      $this->outMax=$configuration['outMax'];
      $this->inAuto=$configuration['inAuto'];
      $this->controllerdirection=$configuration['controllerdirection'];
      $this->Output=$configuration['output'];
      $this->WINDOWS_runing=$configuration['WINDOWS_runing'];
      $this->WINDOWS_size=$configuration['WINDOWS_size'];
      $this->WINDOWS_start_time=$configuration['WINDOWS_start_time'];
      $this->WINDOWS_on_size=$configuration['WINDOWS_on_size'];
      $this->Relay=$configuration['Relay'];
      $this->thermostat=$thermostat;
      
      

      $this->Input=(double)$temperature;
      $this->Setpoint=(double)$consigne;

   }


   function pilote()
   {  
       $log_etat=sprintf(" enterData [%s]",  json_encode($this->getPidData()));
       log::add('ssaThermostat','debug',  $this->thermostat.'[PID]['.__FUNCTION__.']' .  ' : '. $log_etat);
       
      $restant=($this->WINDOWS_start_time + $this->WINDOWS_on_size) - $this->now ;
      
      $log_etat=sprintf("WINDOWS_runing[%s] WINDOWS_on_size[%d] reste[%d]",$this->WINDOWS_runing, $this->WINDOWS_on_size ,$restant);
      //log::add('ssaThermostat','debug',  $this->thermostat.'[PID]['.__FUNCTION__.']' .  ' : '. $log_etat);
      
      
      if($this->WINDOWS_runing=='on')
      { //plage en cours
		
        

        if  ( (($this->WINDOWS_start_time + $this->WINDOWS_on_size) < $this->now) &&  $this->Relay=='on')
        { //fin ON
            
            $log_etat=sprintf("fin relay on");
            //log::add('ssaThermostat','debug',  $this->thermostat.'[PID]['.__FUNCTION__.']' .  ' : '. $log_etat);
      
            
            $this->Relay='off';

        } 
        else
        {
           //test fin de plage 
           if  (($this->WINDOWS_start_time + $this->WINDOWS_size) < $this->now) 
           { //fin de plage
                $this->WINDOWS_runing='off';
                $log_etat=sprintf("fin plage");
                //log::add('ssaThermostat','debug',  $this->thermostat.'[PID]['.__FUNCTION__.']' .  ' : '. $log_etat);
      
           }
           else
           {
               
                $log_etat=sprintf("plage en cours");
                //log::add('ssaThermostat','debug',  $this->thermostat.'[PID]['.__FUNCTION__.']' .  ' : '. $log_etat);
      
            }
         }




      }
      else
      {  //nouvelle plage
         $this->Compute();
         if ($this->Output == 0)
         { //rien a faire
            $log_etat=sprintf("pid==0");
            //log::add('ssaThermostat','debug',  $this->thermostat.'[PID]['.__FUNCTION__.']' .  ' : '. $log_etat);
       

         }
         else
         { //consigne chauffe
           $this->WINDOWS_start_time = $this->now;
           $tmp= ($this->Output * $this->WINDOWS_size) / $this->outMax; 
           $this->WINDOWS_on_size= ($tmp<180)?180:$tmp;
           $this->Relay='on';
           $this->WINDOWS_runing='on';
           $log_etat=sprintf("odre Relay ON");
           //log::add('ssaThermostat','debug',  $this->thermostat.'[PID]['.__FUNCTION__.']' .  ' : '. $log_etat);
       

         }



      }
      return $this->Relay;
   }

   
   
  



    /*
   #define MANUAL 0
   #define AUTOMATIC 1
    
   #define DIRECT 0
   #define REVERSE 1
   */
    
   function Compute()
   {
      if($this->inAuto=='false') return;

      
      $timeChange = ($this->now - $this->lastTime);
      if($timeChange>=$this->SampleTime) //on effectue cette poucle si le delais entre deux appel est superieur a sampletime
      {
         /*Compute all the working error variables*/
         $error = $this->Setpoint - $this->Input;
         $this->ITerm= $this->ITerm + ($this->ki * $error);
         
         if($this->ITerm > $this->outMax)
         {
            $this->ITerm= $this->outMax;
         }
         else
         { if($this->ITerm < $this->outMin)
            {  $this->ITerm= $this->outMin;
            }
         }
         
         $dInput = ($this->Input - $this->lastInput);
    
         /*Compute PID Output*/
         $this->Output = $this->kp * $error + $this->ITerm - $this->kd * $dInput;
         
         if($this->Output > $this->outMax) $this->Output = $this->outMax;
         else if($this->Output < $this->outMin) $this->Output = $this->outMin;
    
         /*Remember some variables for next time*/
         $this->lastInput = $this->Input;
         $this->lastTime = $this->now;
      }
   }
    
   function SetTunings($_Kp, $_Ki, $_Kd)
   {
      if ($_Kp<0 || $_Ki<0|| $_Kd<0) return;
    
      
      $this->kp = $_Kp;
      $this->ki = $_Ki * $this->SampleTime;
      $this->kd = $_Kd / $this->SampleTime;
    
     if($this->controllerdirection == 'REVERSE')
      {
         $this->kp = (0 - $this->kp);
         $this->ki = (0 - $this->ki);
         $this->kd = (0 - $this->kd);
      }
   }
    
   function SetSampleTime($NewSampleTime)
   {
      if ($NewSampleTime > 0)
      {
         $ratio  = $NewSampleTime / $this->SampleTime;
         $this->ki = $this->kd * $ratio;
         $this->kd = $this->kd / $ratio;
         $this->SampleTime = $NewSampleTime;
      }
   }
    
   function SetOutputLimits($Min, $Max)
   {
      if($Min > $Max) return;
      $this->outMin = $Min;
      $this->outMax = $Max;
    
      if($this->Output > $this->outMax) $this->Output = $this->outMax;
      else if($this->Output < $this->outMin) $this->Output = $this->outMin;
    
      if($this->ITerm > $this->outMax) $this->ITerm= $this->outMax;
      else if($this->ITerm < $this->outMin) $this->ITerm= $this->outMin;
   }
    
   function SetMode($Mode)
   {   
       //MANUAL 0
        //AUTOMATIC 1
   

       if( $Mode!= 'MANUAL' && $Mode !='AUTOMATIC') return ;
       $newAuto=($Mode=='AUTOMATIC')?true:false;
       $lastmode=($this->inAuto=='AUTOMATIC')?true:false;
       

       
       if($newAuto == !$lastmode)
       {  /*we just went from manual to auto*/
           $tis->Initialize();
       }
       $this->inAuto = $mode;
   }
    
   function Initialize()
   {
      $this->lastInput = $this->Input;
      $this->ITerm = $this->Output;
      if($this->ITerm > $this->outMax) $this->ITerm= $this->outMax;
      else if($this->ITerm < $this->outMin) $this->ITerm= $this->outMin;
   }
    
   function controllerdirection($Direction)
   {  //DIRECT
      //REVERSE 
      if( $Direction!= 'DIRECT' && $DIRECTION !='REVERSE') return ;
      $this->controllerdirection = $Direction;
   }

}

//$pid=new PID();
//$pid->pilote();

