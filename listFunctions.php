<?php

function getTableHtmlEasyChair(){
  $aAll              = get_all_registrations(false, false, false);

  $aAux              = array(); 
  $aAuxDuplicates    = array();
  $aConfirmed        = array();
  $aTransferPendant  = array();

  
  foreach($aAll as $key => $user){
    if (isset($aAuxDuplicates[strtolower($user["email"])])){
      if (!($aAuxDuplicates[strtolower($user["email"])]["paymentdone"]=="1")){
        if ($user["paymentdone"]=="1"){
          $aAuxDuplicates[strtolower($user["email"])]["paymentmethod"] = $user["paymentmethod"];
          $aAuxDuplicates[strtolower($user["email"])]["paymentdone"] = 1;
        }else if ($user["paymentmethod"]=="methodtransfer"){
          $aAuxDuplicates[strtolower($user["email"])]["paymentmethod"] = "methodtransfer";
        }  
      }
       if ($aAuxDuplicates[strtolower($user["email"])]["easychairid"]==""){
        
        if ($user["easychairid"]!=""){
          $aAuxDuplicates[strtolower($user["email"])]["easychairid"] = $user["easychairid"];
        } 
       } 
    }else $aAuxDuplicates[strtolower($user["email"])] = $user; 
  }

  foreach($aAuxDuplicates as $key => $user){
    if ($user["easychairid"]!=""){

      $aIds = array();
      $rejected = false;
      $sIds = $user["easychairid"];
      if (strpos($sIds,"rejected")) $rejected = true;
      $sIds = str_replace("REVE2017 Submission #","",$sIds);
      $sIds = str_replace("REVE2017 Submission ","",$sIds);
      
      //$sIds = str_replace("Tutorial \\\\\\\"Product Line Strategies Feature Reuse\\\\\\\"","",$sIds);
      $sIds = str_replace(" Tutorial \\\\\\\"Product Line Strategies and Feature Reuse\\\\\\\"","",$sIds);
      $sIds = str_replace("Workshop ","",$sIds);
      $sIds = str_replace(" - Tutorial on Product Line Strategies and Feature Reuse","",$sIds);
      
      $sIds = str_replace("DSPL-17 Submission ","",$sIds);
      $sIds = str_replace(" (3317918)","",$sIds);
      $sIds = str_replace("DSPL-17 Submission ","",$sIds);
      $sIds = str_replace(" DSPL workshop summary (no ID available)","",$sIds);
      $sIds = str_replace("Data, Demontration and tools track SPLC 2017 for paper ","",$sIds);
      
      $sIds = str_replace("SPLC : paper ","",$sIds);
      $sIds = str_replace("REVE : paper ","",$sIds);
      
      $sIds = str_replace("Submission #","",$sIds);
      $sIds = str_replace("- Paper ID ","",$sIds);
      $sIds = str_replace("tool track paper ","",$sIds);
      $sIds = str_replace("REVE-","",$sIds);
      $sIds = str_replace("REVE2017 submission ","",$sIds);
      
      $sIds = str_replace("(but, rejected)","",$sIds);
      $sIds = str_replace("Paper ","",$sIds);
      
      $sIds = str_replace("#","",$sIds);
      $sIds = str_replace("\r\n"," ",$sIds);
      
      if (strchr($sIds,";")===false){
        $aIds = explode(" ",$sIds);
        
      }else $aIds = explode(";",$sIds);
      foreach($aIds as $key=>$sId){
          $aAuxIds = $aIds;
          unset($aAuxIds[$key]);
          $sOthers = implode(" ",$aAuxIds);
          //$sOthers = str_replace($sId,"",$sOthers);
          $user["otherids"] = $sOthers;
          $user["submitrejected"] = $rejected;
          if (isset($aAux[$sId])){
              $aAux[$sId][$user["email"]] = $user;
          }else $aAux[$sId] = array($user["email"] => $user);
      }
    }else{
      if (isset($aAux["SIN ASIGNAR"])){
        $aAux["SIN ASIGNAR"][$user["email"]] = $user;
      }else $aAux["SIN ASIGNAR"] = array($user["email"] => $user);
    }
  }
  ksort($aAux,SORT_NATURAL);
  foreach($aAux as $sId => $aUser){
    foreach($aUser as $user){
      if ($user["paymentdone"]){
        if (isset($aConfirmed[$sId])){
          $aConfirmed[$sId][] = $user;
        }else $aConfirmed[$sId] = array($user);
      }else if ($user["paymentmethod"]=="methodtransfer"){
        if (isset($aTransferPendant[$sId])){
          $aTransferPendant[$sId][] = $user;
        }else $aTransferPendant[$sId] = array($user);
      }
    }
  }
  
  $sHtml = "";
  $sHtml .= "<style type='text/css' scoped>
        table.zebra tbody tr:nth-child(2n+1) {
          background-color: #ccf;
        }

    </style>
  ";

  $sHtml .= "Confirmed";
  $sHtml .= "<table class='zebra'>";
  $sHtml .= "<thead>";
  $sHtml .= "<tr>";
  $sHtml .= "<th>";
  $sHtml .= "Easychair ID";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Name";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Email";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Reg Type";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Payment";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Others";
  $sHtml .= "</th>";  
  $sHtml .= "</tr>";
  $sHtml .= "</thead>";
  $sHtml .= "<tbody>";
  
  foreach($aConfirmed as $sId =>$aUser){
    foreach($aUser as $user){
      $sHtml .= "<tr>";
      $sHtml .= "<td>";
      $sHtml .= $sId;
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= $user["firstname"]." ".$user["lastname"];
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= $user["email"];
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= textRegistration($user["regoption"]);
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= $user["totalamount"];
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= $user["otherids"].($user["submitrejected"]?"REJECTED":"");
      $sHtml .= "</td>";
      $sHtml .= "</tr>";
      
    }
  }
  $sHtml .= "</tbody>";
  $sHtml .= "</table>";
  $sHtml .= "Transfer Pendant";
  $sHtml .= "<table class='zebra'>";
  $sHtml .= "<thead>";
  $sHtml .= "<tr>";
  $sHtml .= "<th>";
  $sHtml .= "Easychair ID";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Name";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Email";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Reg Type";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Payment";
  $sHtml .= "</th>";
  $sHtml .= "<th>";
  $sHtml .= "Others";
  $sHtml .= "</th>";  
  $sHtml .= "</tr>";
  $sHtml .= "</thead>";
  $sHtml .= "<tbody>";
  
  foreach($aTransferPendant as $sId =>$aUser){
    foreach($aUser as $user){
      $sHtml .= "<tr>";
      $sHtml .= "<td>";
      $sHtml .= $sId;
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= $user["firstname"]." ".$user["lastname"];
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= $user["email"];
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= textRegistration($user["regoption"]);
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= $user["totalamount"];
      $sHtml .= "</td>";
      $sHtml .= "<td>";
      $sHtml .= $user["otherids"].($user["submitrejected"]?"<br/>REJECTED":"");
      $sHtml .= "</td>";
      $sHtml .= "</tr>";
      
    }
  }
  $sHtml .= "</tbody>";
  $sHtml .= "</table>";
  return $sHtml;
}







function getTableHtmlAll(){
  $aFilters = array("undiscarded","paid","transferpendant","discarded");
  $aParseUrl = parse_url($_SERVER["REQUEST_URI"]);
  parse_str($aParseUrl["query"],$aParseQuery);
  unset($aParseQuery["filter"]);
  unset($aParseQuery["action"]);
  unset($aParseQuery["idOrder"]);
  $aParseUrl["query"] = http_build_query($aParseQuery); 
  $aNewUrl = $aParseUrl["path"]."?".$aParseUrl["query"].$aParseUrl["fragment"];  
  $sHtml = "<div>";
  $sHtml .= "<script>";
  $sHtml .= "function updateFilter(){";
  $sHtml .= "var newaction = document.getElementById('filterusers').value;";
  $sHtml .= "var newUrl = '".$aNewUrl."&filter='+newaction;";
  $sHtml .= "window.location=newUrl;";
  $sHtml .= "}";
  $sHtml .= "</script>";
  $sHtml .= "<a href='".$aNewUrl."&action=generatecsv'>GenerateCSV</a>";
  $sHtml .= "<br/><a href='".$aNewUrl."&action=generateexcel'>GenerateExcel</a>";
  $sHtml .= "<br/><a href='".$aNewUrl."&action=generatecountries'>Generate Countries Summary</a>";
  $sHtml .= "<br/><a href='".$aNewUrl."&action=generateprivate'>Generate Private Summary</a>";
  
  $sHtml .= "<form name='listusers' action='".$_SERVER["REQUEST_URI"]."'>";
  $sHtml .= "<div id='filter'>";
  $sHtml .= "Filter by";
  $sHtml .= "<select id='filterusers' name='filterusers' onchange='updateFilter();'>";
  foreach($aFilters as $aFilter){
    $sHtml .= "<option value='".$aFilter."' ".(($_REQUEST["filter"]==$aFilter)?'selected':'').">".$aFilter."</option>";
  }
  $sHtml .= "</select>";
  $sHtml .= "</div>";
  $sHtml .= "<div id='usersaction'>";
  $sHtml .= "<select name='batchaction'>";
  $sHtml .= "<option value='discard'>Discard</option>";
  $sHtml .= "<option value='paid'>Mark as Paid</option>";
  $sHtml .= "<option value='unpaid'>Mark as Unpaid</option>";
  $sHtml .= "<option value='transferremainder'>Send Transfer Remainder</option>";
  $sHtml .= "</select>";
  $sHtml .= "<button name='batchexecute' type='submit'>Execute Actions</button>";
  $sHtml .= "</div>";
  $discard          = false; //TRUE=Only Discarded FALSE=All
  $paid             = false; //TRUE=Only Paid FALSE=All
  $transferpendant  = false; //TRUE=Only Transfer Pendant FALSE=All
  if ($_REQUEST["filter"]){
    $sFilter = $_REQUEST["filter"];
    switch ($sFilter){
      case "undiscarded":
        $discard = false;
        break;
      case "discarded":
        $discard = true;
        break;
      case "paid":
        $paid = true;
        break;
      case "transferpendant":
        $transferpendant = true;
    }
  }
  $aAll = get_all_registrations($discard, $paid, $transferpendant);
  echo "<label>".count($aAll)." rows</label>";
  if($aAll!=NULL ){
    $arrayFields = array("id"=>"id","paymentmethod"=>"Method","time"=>"time","firstname"=>"Name","lastname"=>"Surname","totalamount"=>"Money","email"=>"Email","affiliation"=>"Affiliation","regoption"=>"Option","paymentdone"=>"Paid","studentcheck"=>"Student");
    $sHtml .= "<table>";
    $sHtml .= "<tr>";
      $sHtml .= "<th>";
      $sHtml .= "<input type='checkbox' name='selectall'/>";
      $sHtml .= "</th>";
    foreach($aAll[0] as $key=>$field){
    
      if (in_array($key,array_keys($arrayFields))){
        $sHtml .= "<th>";
        $sHtml .= $arrayFields[$key];
        $sHtml .= "</th>";
      }
    
    }
      $sHtml .= "<th>";
      $sHtml .= "Action";
      $sHtml .= "</th>";
    
    $sHtml .= "</tr>";
    foreach($aAll as $user){
      $sHtml .= "<tr>";
      $sHtml .= "<td>";
      $sHtml .= "<input type='checkbox' name='idorder[".$user["id"]."]'/>";
      $sHtml .= "</td>";
    
      foreach($user as $key=>$value){
        if (in_array($key,array_keys($arrayFields))){
          $sHtml .= "<td>";
          $sHtml .= $value;
          $sHtml .= "</td>";
        }
      }
      $sHtml .= "<td>";
      if ($user["paymentdone"]!='1')
        $sLinkConfirm = "<a href='".$aNewUrl."&action=confirmuser&idOrder=".$user["id"]."'/>Confirm</a>";
      else 
        $sLinkConfirm = "<a href='".$aNewUrl."&action=unconfirmuser&idOrder=".$user["id"]."'/>Unconfirm</a>";

      if ($user["trashed"]!='1')
        $sHtml .= "<a href='".$aNewUrl."&action=discarduser&idOrder=".$user["id"]."'/>Discard</a><br/>".$sLinkConfirm;
      else 
        $sHtml .= "<a href='".$aNewUrl."&action=redouser&idOrder=".$user["id"]."'/>Redo</a><br/>".$sLinkConfirm;
      $sHtml .= "</td>";
    
      $sHtml .= "</tr>";
    }
    $sHtml .= "</table>";
  }
  $sHtml .= "</form>";
  $sHtml .= "</div>";
  return $sHtml;
}

function getTableHtmlBudget(){
  $sHtml = "";
  $sHtml .= "<style type='text/css' scoped>
        table.zebra tbody tr:nth-child(2n+1) {
          background-color: #ccf;
        }

    </style>
  ";
  $aAll = get_all_registrations(false, false, false);
  
  $aConfirmed           = array("1"=>0,"2"=>0,"3"=>0,"4"=>0,"5"=>0); 
  $aPaid                = array("1"=>0,"2"=>0,"3"=>0,"4"=>0,"5"=>0); 
  
  $aUnconfirmedTransfer = array("1"=>0,"2"=>0,"3"=>0,"4"=>0,"5"=>0); 
  $aUnconfirmedPaid     = array("1"=>0,"2"=>0,"3"=>0,"4"=>0,"5"=>0); 
  
  $aUsersRepeated       = array();   
  $aUsersToPrint        = array();

  $arrayFields = array("id" => "idOrder/Detail","firstname" => "Nombre","time" =>"Fecha", "lastname"=>"Apellido","totalamount"=>"Importe","email"=>"EMAIL","paymentmethod"=>"Forma","paymentdone"=>"Pagado");
  
  foreach($aAll as $user){
    if ($user["paymentdone"]==1){
      //if (!isset($aUsersRepeated[strtolower($user["email"])])){
      if ($user["id"]!="32"){
        if($user["id"]=="41"){
          $user["totalamount"] = "690";
        }
        $aConfirmed[$user["regoption"]]++;
        $aPaid[$user["regoption"]] += $user["totalamount"];
        $aUsersRepeated[strtolower($user["email"])] = 1;
      }
    }
  }
  foreach($aAll as $user){
    if ($user["paymentdone"]==0){
      if (!isset($aUsersRepeated[strtolower($user["email"])])){
        $aUsersRepeated[strtolower($user["email"])] = 1;     
        $aUnconfirmedTransfer[$user["regoption"]]++;
        $aUnconfirmedPaid[$user["regoption"]] += $user["totalamount"];
        $aUsersToPrint[] = $user;
      }
    }
  }

  $sHtml .= "<table class='zebra'>";
  $sHtml .= "<thead>";
  $sHtml .= "<tr>";
  $sHtml .= "<th colspan='12'>Confirmed</th>";
  $sHtml .= "</tr>";
  $sHtml .= "<tr>";
  $sHtml .= "<th colspan='2'>Option 1</th><th colspan='2'>Option 2</th><th colspan='2'>Option 3</th><th colspan='2'>Option 4</th><th colspan='2'>Option 5</th><th colspan='2'>Total</th>";
  $sHtml .= "</tr>";
  $sHtml .= "<tr>";
  $sHtml .= "<th>N</th><th>€</th><th>N</th><th>€</th><th>N</th><th>€</th><th>N</th><th>€</th><th>N</th><th>€</th><th>N</th><th>€</th>";
  $sHtml .= "</tr>";
  $sHtml .= "</thead>";
  $sHtml .= "<tbody>";
  $sHtml .= "<tr>";
  $sHtml .= "<td>".$aConfirmed["1"]."</td><td>".$aPaid["1"]."€</td>";
  $sHtml .= "<td>".$aConfirmed["2"]."</td><td>".$aPaid["2"]."€</td>";
  $sHtml .= "<td>".$aConfirmed["3"]."</td><td>".$aPaid["3"]."€</td>";
  $sHtml .= "<td>".$aConfirmed["4"]."</td><td>".$aPaid["4"]."€</td>";
  $sHtml .= "<td>".$aConfirmed["5"]."</td><td>".$aPaid["5"]."€</td>";
  $sHtml .= "<td>".array_sum($aConfirmed)."</td><td>".array_sum($aPaid)."€</td>";
  $sHtml .= "</tr>";
  $sHtml .= "</tbody>";
  $sHtml .= "</table>";
  $sHtml .= "<br/>";
  
  $sHtml .= "<table class='zebra'>";
  $sHtml .= "<thead>";
  $sHtml .= "<tr>";
  $sHtml .= "<th colspan='12'>Unconfirmed</th>";
  $sHtml .= "</tr>";
  $sHtml .= "<tr>";
  $sHtml .= "<th colspan='2'>Option 1</th><th colspan='2'>Option 2</th><th colspan='2'>Option 3</th><th colspan='2'>Option 4</th><th colspan='2'>Option 5</th><th colspan='2'>Total</th>";
  $sHtml .= "</tr>";
  $sHtml .= "<tr>";
  
  $sHtml .= "<th>N</th><th>€</th><th>N</th><th>€</th><th>N</th><th>€</th><th>N</th><th>€</th><th>N</th><th>€</th><th>N</th><th>€</th>";
  $sHtml .= "</tr>";
  $sHtml .= "</thead>";
  $sHtml .= "<tbody>";
  $sHtml .= "<tr>";
  $sHtml .= "<td>".$aUnconfirmedTransfer["1"]."</td><td>".$aUnconfirmedPaid["1"]."€</td>";
  $sHtml .= "<td>".$aUnconfirmedTransfer["2"]."</td><td>".$aUnconfirmedPaid["2"]."€</td>";
  $sHtml .= "<td>".$aUnconfirmedTransfer["3"]."</td><td>".$aUnconfirmedPaid["3"]."€</td>";
  $sHtml .= "<td>".$aUnconfirmedTransfer["4"]."</td><td>".$aUnconfirmedPaid["4"]."€</td>";
  $sHtml .= "<td>".$aUnconfirmedTransfer["5"]."</td><td>".$aUnconfirmedPaid["5"]."€</td>";
  $sHtml .= "<td>".array_sum($aUnconfirmedTransfer)."</td><td>".array_sum($aUnconfirmedPaid)."€</td>";
  $sHtml .= "</tr>";
  $sHtml .= "</tbody>";
  $sHtml .= "</table>";
  $sHtml .= "<br/>";
  
  $sHtml .= "<table class='zebra'>";
  $sHtml .= "<thead>";
  $sHtml .= "<tr>";
  foreach($aAll[0] as $key=>$field){
    if (array_key_exists($key,$arrayFields)){
      $sHtml .= "<th>";
      $sHtml .= $arrayFields[$key];
      $sHtml .= "</th>";
    }
  }
  $sHtml .= "</tr>";
  $sHtml .= "</thead>";
  $sHtml .= "<tbody>";
  foreach($aUsersToPrint as $user){
      $sHtml .= "<tr>";
      foreach($user as $key=>$value){

        if (array_key_exists($key,$arrayFields)){
          $sHtml .= "<td>";
          switch($key){
            case "time":
              $sHtml .= substr($value,0,10);
              break;
            case "id":
              $sHtml .= "<a href='?detail=".$value."'>".$value."</a>";
              break;
            case "paymentdone":
              if ($value==1) $sHtml .= "<label style='color:green;'>SI</label>";
              else $sHtml .= "<label style='color:red;'>NO</label>";
              break;
            case "paymentmethod":
              if ($value=="methodtransfer") $sHtml .= "Transfer";
              else $sHtml .= "TPV";
              break;
            default: 
              $sHtml .= $value;
              break;
          }
          $sHtml .= "</td>";

        }
      }
      $sHtml .= "</tr>";
    
  }
  $sHtml .= "</tbody>";
  
  $sHtml .= "</table>";
  return $sHtml;

}



function getTableHtmlAllUnpaid(){
  $sHtml = "";
  $sHtml .= "<style type='text/css' scoped>
        table.zebra tbody tr:nth-child(2n+1) {
          background-color: #ccf;
        }

    </style>
  ";
  $aAll = get_all_registrations(false, false, false);
  usort($aAll, function ($a, $b) {
    return ($a['paymentdone'] - $b['paymentdone'])*1000 + ($a['id'] - $b['id']) ;
  });
  $arrayFields = array("id" => "idOrder/Detail","firstname" => "Nombre","time" =>"Fecha", "lastname"=>"Apellido","totalamount"=>"Importe","email"=>"EMAIL","paymentmethod"=>"Forma","paymentdone"=>"Pagado");
  $aTotalPaid = 0;
  $aTotalRegistered = 0;
  $aTotalTransfer = 0;
  $aTotalPendantTransfer = 0;
  $aUsersRepeated = array();   
  $aUsersPendant = array();
  foreach($aAll as $user){
    if ($user["paymentdone"]==1){
      $aTotalRegistered++;
      $aTotalPaid += $user["totalamount"];
      $aUsersRepeated[strtolower($user["email"])] = 1;
    }
  }
  foreach($aAll as $user){
    if ($user["paymentdone"]==0){
      if (!isset($aUsersRepeated[strtolower($user["email"])])){
        $aUsersPendant[] = $user["email"];
        $aUsersRepeated[strtolower($user["email"])] = 1;     
        $aTotalTransfer++;
        $aTotalPendantTransfer += $user["totalamount"];
      }
    }
  }
  //print_r($aUsersPendant);

  $sHtml .= "<table class='zebra'>";
  $sHtml .= "<thead>";
  $sHtml .= "<tr>";
  $sHtml .= "<th colspan='2'>Resumen</th>";
  $sHtml .= "</tr>";
  $sHtml .= "</thead>";
  $sHtml .= "<tbody>";
  $sHtml .= "<tr>";
  $sHtml .= "<td>Confirmados</td><td>".$aTotalRegistered."</td>";
  $sHtml .= "</tr>";
  $sHtml .= "<tr>";
  $sHtml .= "<td>Pagado</td><td>".$aTotalPaid."€</td>";
  $sHtml .= "</tr>";
  $sHtml .= "<tr>";
  $sHtml .= "<td>Numero de Pagos Pendientes</td><td>".$aTotalTransfer."</td>";
  $sHtml .= "</tr>";
  $sHtml .= "<tr>";
  $sHtml .= "<td>Dinero Pendiente</td><td>".$aTotalPendantTransfer."€</td>";
  $sHtml .= "</tr>";
  $sHtml .= "<tr>";
  $sHtml .= "<td colspan='2'>Nota: Para los pendientes se han eliminado los que tienen el mismo email, pero siempre hay incertidumbre</td>";
  $sHtml .= "</tr>";
  
  $sHtml .= "</tbody>";
  $sHtml .= "</table>";

  $sHtml .= "<br/>";
  $sHtml .= "<table class='zebra'>";
  $sHtml .= "<thead>";
  $sHtml .= "<tr>";
  foreach($aAll[0] as $key=>$field){
    if (array_key_exists($key,$arrayFields)){
      $sHtml .= "<th>";
      $sHtml .= $arrayFields[$key];
      $sHtml .= "</th>";
    }
  }
  $sHtml .= "<th>";
  $sHtml .= "Option";
  $sHtml .= "</th>";
  $sHtml .= "</tr>";
  $sHtml .= "</thead>";
  $sHtml .= "<tbody>";
  foreach($aAll as $user){
      $sHtml .= "<tr>";
      foreach($user as $key=>$value){

        if (array_key_exists($key,$arrayFields)){
          $sHtml .= "<td>";
          switch($key){
            case "time":
              $sHtml .= substr($value,0,10);
              break;
            case "id":
              $sHtml .= "<a href='?detail=".$value."'>".$value."</a>";
              break;
            case "paymentdone":
              if ($value==1) $sHtml .= "<label style='color:green;'>SI</label>";
              else $sHtml .= "<label style='color:red;'>NO</label>";
              break;
            case "paymentmethod":
              if ($value=="methodtransfer") $sHtml .= "Transfer";
              else $sHtml .= "TPV";
              break;
            default: 
              $sHtml .= $value;
              break;
          }
          $sHtml .= "</td>";

        }
      }
      $sHtml .= "<td>";
      if ($user["paymentdone"]==0)
        $sHtml .= "<a href='?confirm=1&idOrder=".$user["id"]."'>Confirm</a>";
      else 
        $sHtml .= "<a href='?confirm=0&idOrder=".$user["id"]."'>Unconfirm</a>";
      $sHtml .= "</td>";
      $sHtml .= "</tr>";
    
  }
  $sHtml .= "</tbody>";
  
  $sHtml .= "</table>";
  return $sHtml;
}

function getTableHtml($idRegistration){
  
  if (is_array($idRegistration)){
  $registration_info = $idRegistration;  
  }else{
  $registration_info = get_registration_info($idRegistration);
  }
  $sHtml = "";  
  $sHtml .="<fieldset><legend>Registration info</legend>";
  $sHtml .="<table>";
  if (isset($registration_info["id"])){
  
    $sHtml .="<tr>";
    $sHtml .="<td>ID Registration</td>";
    $sHtml .="<td>".$registration_info["id"]."</td>";
    $sHtml .="</tr>";
  }
  $sHtml .="<tr>";  
  $sHtml .="<td>Name</td>";
  $sHtml .="<td>".htmlentities($registration_info["firstname"])." ".htmlentities($registration_info["lastname"])."</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";  
  $sHtml .="<td>Name as it will appear on the badge</td>";
  $sHtml .="<td>".htmlentities($registration_info["profile"])."</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";  
  $sHtml .="<td>Affiliation</td>";
  $sHtml .="<td>".htmlentities($registration_info["affiliation"])."</td>";
  $sHtml .="</tr>";
  
  $sHtml .="<tr>";
  $sHtml .="<td>Address - PostCode</td>";
  $sHtml .="<td>".htmlentities($registration_info["address"])." - ".$registration_info["postcode"]."</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";
  $sHtml .="<td>City, State/Province</td>";
  $sHtml .="<td>".htmlentities($registration_info["city"]).", ".htmlentities($registration_info["state"])."</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";
  $sHtml .="<td>Country</td>";
  $sHtml .="<td>".resultCountry($registration_info["country"])."</td>";
  $sHtml .="</tr>";
  
  $sHtml .="<tr>";  
  $sHtml .="<td>Contact</td>";
  $sHtml .="<td>".$registration_info["email"]."/".$registration_info["phone"]."</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";  
  $sHtml .="<td>EasyChair IDs</td>";
  $sHtml .="<td>".str_replace("\n",",",$registration_info["easychairid"])."</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";  
  $sHtml .="<td>Registration</td>";
  $sHtml .="<td>".textRegistration($registration_info["regoption"])."- Extra Dinner: ".$registration_info["extradinner"]."</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";  
  $sHtml .="<td>Student Discount</td>";
  $sHtml .="<td>".($registration_info["studentcheck"]=="true"?"YES":"NO")."</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";  
  $sHtml .="<td>Payment method</td>";
  $sHtml .="<td>".(($registration_info["paymentmethod"]=="methodtpv")?"Credit Card":"Bank Transfer")."</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";  
  $sHtml .="<td>Payment Amount</td>";
  $sHtml .="<td>".$registration_info["totalamount"]." euros</td>";
  $sHtml .="</tr>";
  $sHtml .="<tr>";  
  $sHtml .="<td>Estimated assistance</td>";
  $sHtml .="<td>From ".$registration_info["arrivaldate"]." To ".$registration_info["departuredate"]."</td>";
  $sHtml .="</tr>";
  
  $sHtml .="</table>";
  $sHtml .="</fieldset>";
  if ($registration_info["billingname"]!="" || $registration_info["billingvat"]!="" || $registration_info["billingaddress"]!="" || $registration_info["billingpostcode"]!="" || $registration_info["billingcity"]!="" || $registration_info["billingstate"]!=""){
    $sHtml .= "<fieldset><legend>Billing Info</legend>";
    $sHtml .="<table>";
    $sHtml .="<tr>";  
    $sHtml .="<td>Name</td>";
    $sHtml .="<td>".htmlentities($registration_info["billingname"])."</td>";
    $sHtml .="<tr>";  
    $sHtml .="<td>VAT</td>";
    $sHtml .="<td>".$registration_info["billingvat"]."</td>";
    $sHtml .="</tr>";
    $sHtml .="<tr>";
    $sHtml .="<td>Address - CP</td>";
    $sHtml .="<td>".htmlentities($registration_info["billingaddress"])." - ".$registration_info["billingpostcode"]."</td>";
    $sHtml .="</tr>";
    $sHtml .="<tr>";
    $sHtml .="<td>City - State/Province</td>";
    $sHtml .="<td>".htmlentities($registration_info["billingcity"]).", ".htmlentities($registration_info["billingstate"])."</td>";
    $sHtml .="</tr>";
    $sHtml .="<tr>";
    $sHtml .="<td>Country</td>";
    $sHtml .="<td>".resultCountry($registration_info["billingcountry"])."</td>";
    $sHtml .="</tr>";
    
    $sHtml .="</table>";
    $sHtml .="</fieldset>";
  }
  return $sHtml;

}

?>