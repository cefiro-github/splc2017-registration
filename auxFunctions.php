<?php

function sendMessageSlack($idRegistration){
    $aRegistrationInfo = get_registration_info($idRegistration);
    $email = $aRegistrationInfo["email"];
    $aAll = get_all_registrations(false, false, false);
    $aTotalRegistered = 0;
    $aUsersRepeated = array();
    $cancel = false;
    foreach($aAll as $user){
      if(strtolower($email) == strtolower($user["email"]) && $idRegistration!=$user["id"]) $cancel = true; 
      if ($user["paymentdone"]==1){
        $aTotalRegistered++;
        $aUsersRepeated[strtolower($user["email"])] = 1;
      }
    }
    foreach($aAll as $user){
      if ($user["paymentdone"]==0){
        if (!isset($aUsersRepeated[strtolower($user["email"])])){
          $aUsersRepeated[strtolower($user["email"])] = 1;     
          $aTotalRegistered++;
        }
      }
    }
    if (!$cancel){
      $number = $aTotalRegistered;
      $domain   = get_option('splcregistration_settings_input')["slackchannel"];

      $bot_name = 'Webhook';
      $icon     = ':alien:';
      $messageInit = "";
      
      if($aRegistrationInfo["affiliation"]!=""){
        $prestigious = array("famous", "renowned", "accredited", "influential", "notorious", "considered", "respected", "appreciated", "well-liked");
        $random = rand(0,count($prestigious)-1);
        $messageInit = "A new user from the ".$prestigious[$random]." ".$aRegistrationInfo["affiliation"]." has registered. ";
      }else $messageInit = "A new user has registered. ";
      $messages = array("#number registered users and going up! :metal:",
                        "We are already #number! :smiley:",
                      "SPLC is proud to announce that there are #number registered users :thumbsup:");

      $specialMessages  = array(100=> "WOW!!!! We reached 100! :trophy:", 144 => "We are a dozen of dozens! Will be surprises at the party? :ring:");
      $specialEmails    = array("jtroya@us.es" => "Made in Costa del Sol and evolved in London, Canada and Vienna.", 
                                "jagalindo@us.es" => "Tostadas completas lover and Dos Hermanas resident.", 
                                "anabsanchez@us.es" => "Carlinhos lover, rosaleña and half-trianera.",
                                "aruiz@us.es" => "<https://www.youtube.com/watch?v=xemgC81-5Uo|The boss> has come!");
      $endMessage = $messageInit;
      if (isset($specialMessages[$number])) $endMessage .= $specialMessages[$number];
      else if (isset($specialEmails[strtolower($aRegistrationInfo["email"])])) $endMessage .= $specialEmails[strtolower($aRegistrationInfo["email"])];
      else {
          $random = rand(0,count($messages)-1);
          $endMessage .= str_replace("#number", $number, $messages[$random]);
      }
      $data = array(
          'channel'     => $channel,
          'username'    => $bot_name,
          'text'        => $endMessage,
          'icon_emoji'  => $icon
      );
      $data_string = json_encode($data);
      $ch = curl_init($domain);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          'Content-Type: application/json',
          'Content-Length: ' . strlen($data_string))
      );

      $result = curl_exec($ch);
      if ($result === false) {
         // echo 'Curl error: ' . curl_error($ch);
      }
      curl_close($ch);
    }
    return $result;
}

function getCheckCodeByEmail($email){
  $code   = hash("md5",$email."splc1");   
  $result = check_code_email($email, $code);
  $sHtml = "The email ".$email." ";
  if ($result == NULL){
    $sHtml .= "has not code registered.";
  }else if ($result == 1){
    $sHtml .= "has the code ".$code." registered and not used";
  }else{
    $sHtml .= "has the code ".$code." registered and used with idOrder=".$result["idorder"];
  }
  return $sHtml;
}

function generateCodeByEmail($email){
    $code   = hash("md5",$email."splc1");   
    $result = check_code_email($email, $code);
    if ($result == NULL){
        insert_code($email,$code);
        $sHmtl = "The email ".$email." has been registered with code=".$code;
    }else if ($result["used"]==0){
        $sHmtl = "The email ".$email." was already registered with code=".$code." (was not used)";
    }else if ($result["used"]){
        $sHmtl = "The email ".$email." was already registered with code=".$code." (and has been used)";
    }
}

function getCheckCodeByCode($code){
  $result = check_code($code);
  
  $sHtml = "INVALID";
  if ($result == 1){
    $sHtml .= "VALID";
  }
  return $sHtml;
}

function processActionUsers(){
    if (isset($_REQUEST["action"])){
      $action = $_REQUEST["action"];
      switch($action){
        case "discarduser":
          trash_user($_REQUEST["idOrder"]);
          break;
        case "redouser":
          untrash_user($_REQUEST["idOrder"]);
          break;
        case "confirmuser":
          confirm_registration($_REQUEST["idOrder"]);
          //sendMessageSlack($_REQUEST["idOrder"]);
          emailAdminRegistration($_REQUEST["idOrder"]);
          emailUserRegistration($_REQUEST["idOrder"]);
          emailPaymentSupervisorRegistration($_REQUEST["idOrder"]);
          break;
        case "unconfirmuser":
          unconfirm_registration($_REQUEST["idOrder"]);
          break;
        case "discardbatch":
          //print_r($_REQUEST);
          break;
        
      }
    }
}

function generateExcelSummaryCountries(){
  $aUsers = exportUsers();
  $aSummary = array();
  $aContinents = array();
  foreach($aUsers as $aUser){
    $sContinent = getContinent($aUser["country"]);
    if (isset($aSummary[$aUser["country"]]))
      $aSummary[$aUser["country"]]++;
    else $aSummary[$aUser["country"]] = 1;

    if (isset($aContinents[$sContinent]))
      $aContinents[$sContinent]++;
    else $aContinents[$sContinent] = 1;
  }
  
  require_once dirname(__FILE__) . '/PhpExcel/Classes/PHPExcel.php';
  require_once dirname(__FILE__) . '/PhpExcel/Classes/PHPExcel/Writer/Excel2007.php';
  
  //require_once 'PhpSpreadsheet/Bootstrap.php';
  
  $spreadsheet = new PHPExcel();
  // Set document properties
  $spreadsheet->getProperties()->setCreator('SPLC2017')
          ->setLastModifiedBy('SPLC2017')
          ->setTitle('SPLC 2017 Countries Summary')
          ->setSubject('SPLC 2017 Countries Summary')
          ->setDescription('Count of registered users by country in SPLC 2017')
          ->setKeywords('splc conference')
          ->setCategory('SPLC');
  // Add some data
  $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1',"Country")
            ->setCellValue('B1',"Attendance")
            ->setCellValue('E1',"Continent")
            ->setCellValue('F1',"Attendance");
      
  $spreadsheet->getActiveSheet()->setTitle('Registration');
  // Set active sheet index to the first sheet, so Excel opens this as the first sheet
  $spreadsheet->setActiveSheetIndex(0);
  $currentRow = 2;
  foreach($aSummary as $key=>$count){
    $spreadsheet->getActiveSheet()
      ->setCellValue('A'.$currentRow,resultCountry($key))
      ->setCellValue('B'.$currentRow,$count);   
    $currentRow++;
  }

  $currentRow = 2;
  foreach($aContinents as $key=>$count){
    $spreadsheet->getActiveSheet()
      ->setCellValue('E'.$currentRow,$key)
      ->setCellValue('F'.$currentRow,$count);   
    $currentRow++;
  }

  // Redirect output to a client’s web browser (Xlsx)
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment;filename="CountriesSummary.xlsx"');
  header('Cache-Control: max-age=0');
  // If you're serving to IE 9, then the following may be needed
  header('Cache-Control: max-age=1');
// If you're serving to IE over SSL, then the following may be needed
  header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
  header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
  header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
  header ('Pragma: public'); // HTTP/1.0
  $objWriter = PHPExcel_IOFactory::createWriter($spreadsheet, 'Excel2007');
  $fileNameTmp = str_replace('.php', '.xlsx', __FILE__);
  $objWriter->save($fileNameTmp);
  readfile($fileNameTmp);
  exit;
}

function generateExcelSummaryPrivate(){
  $aUsers = exportUsers();
  $aSummary = array();
  foreach($aUsers as $aUser){
    $private = "public";
    if ($aUser["privatepublic"]!="0")
      $private = "private";
    if (isset($aSummary[$private]))
      $aSummary[$private]++;
    else $aSummary[$private] = 1;

  }
  
  require_once dirname(__FILE__) . '/PhpExcel/Classes/PHPExcel.php';
  require_once dirname(__FILE__) . '/PhpExcel/Classes/PHPExcel/Writer/Excel2007.php';
  
  //require_once 'PhpSpreadsheet/Bootstrap.php';
  
  $spreadsheet = new PHPExcel();
  // Set document properties
  $spreadsheet->getProperties()->setCreator('SPLC2017')
          ->setLastModifiedBy('SPLC2017')
          ->setTitle('SPLC 2017 Public or Private Summary')
          ->setSubject('SPLC 2017 Public or Private')
          ->setDescription('Count of registered users by Public or Private in SPLC 2017')
          ->setKeywords('splc conference')
          ->setCategory('SPLC');
  // Add some data
  $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1',"Kind")
            ->setCellValue('B1',"Attendance");
      
  $spreadsheet->getActiveSheet()->setTitle('Registration');
  // Set active sheet index to the first sheet, so Excel opens this as the first sheet
  $spreadsheet->setActiveSheetIndex(0);
  $currentRow = 2;
  foreach($aSummary as $key=>$count){
    $spreadsheet->getActiveSheet()
      ->setCellValue('A'.$currentRow,$key)
      ->setCellValue('B'.$currentRow,$count);   
    $currentRow++;
  }

 
  // Redirect output to a client’s web browser (Xlsx)
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment;filename="PrivateSummary.xlsx"');
  header('Cache-Control: max-age=0');
  // If you're serving to IE 9, then the following may be needed
  header('Cache-Control: max-age=1');
// If you're serving to IE over SSL, then the following may be needed
  header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
  header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
  header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
  header ('Pragma: public'); // HTTP/1.0
  $objWriter = PHPExcel_IOFactory::createWriter($spreadsheet, 'Excel2007');
  $fileNameTmp = str_replace('.php', '.xlsx', __FILE__);
  $objWriter->save($fileNameTmp);
  readfile($fileNameTmp);
  exit;
}


function downloadExcel(){
  //use PhpSpreadsheet\PhpSpreadsheet\Helper\Sample;
  //use PhpSpreadsheet\PhpSpreadsheet\IOFactory;
  //use PhpOffice\PhpSpreadsheet\Spreadsheet;
  //use PhpOffice\PhpSpreadsheet\Spreadsheet;
  require_once dirname(__FILE__) . '/PhpExcel/Classes/PHPExcel.php';
  require_once dirname(__FILE__) . '/PhpExcel/Classes/PHPExcel/Writer/Excel2007.php';
  
  //require_once 'PhpSpreadsheet/Bootstrap.php';
  
  $spreadsheet = new PHPExcel();
  // Set document properties
  $spreadsheet->getProperties()->setCreator('SPLC2017')
          ->setLastModifiedBy('SPLC2017')
          ->setTitle('SPLC 2017 Registration Info')
          ->setSubject('SPLC 2017 Registration Info')
          ->setDescription('List of registered users in SPLC 2017')
          ->setKeywords('splc conference')
          ->setCategory('SPLC');
  // Add some data
  $spreadsheet->setActiveSheetIndex(0)
            ->setCellValue('A1',"REGISTRO")
            ->setCellValue('B1',"SITUACION")
            ->setCellValue('C1',"APELLIDO - NOMBRE")
            ->setCellValue('D1',"BADGE NAME")
            ->setCellValue('E1',"affiliation")
            ->setCellValue('F1',"TIPO INSCRIPCION")
            ->setCellValue('G1',"EMAIL")
            ->setCellValue('H1',"FECHA DE SOLICITUD")
            ->setCellValue('I1',"IMPORTE INSCRIPCION")
            ->setCellValue('J1',"EASY CHAIR ID")
            ->setCellValue('K1',"EXTRA DINNER")
            ->setCellValue('M1',"IMPORTE EXTRA DINNER")
            ->setCelLValue('N1',"FECHA REGISTRO");
  $aFieldsName = array("firstname", "lastname","profile","address","postcode","city","state","country",
                "phone","email","others","arrivaldate","departuredate","studentcheck","firsttime","paymentmethod",
                "billingname","billingvat","billingaddress","billingpostcode","billingcity","billingstate","billingcountry",
                "needvisa");
  
  $spreadsheet->getActiveSheet()
    ->fromArray(
        $aFieldsName ,  // The data to set
        NULL,        // Array values with this value will not be set
        'O1'         // Top left coordinate of the worksheet range where
                     //    we want to set these values (default is A1)
    );
    

  // Rename worksheet
  $aUsers = array();
  $aUsers = exportUsers();
  //print_r($aUsers);
  $initRow = 2;
  foreach($aUsers as $aUser){
    $spreadsheet->getActiveSheet()
    ->fromArray(
        $aUser,  // The data to set
        NULL,        // Array values with this value will not be set
        'A'.$initRow         // Top left coordinate of the worksheet range where
                     //    we want to set these values (default is A1)
    );
    $initRow++;
  }
  $spreadsheet->getActiveSheet()->setTitle('Registration');
  // Set active sheet index to the first sheet, so Excel opens this as the first sheet
  $spreadsheet->setActiveSheetIndex(0);
  $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(5);
  $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(18);
  $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(30);
  $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(35);
  $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(50);
  $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(20);
  $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(30);
  $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(15);
  $spreadsheet->getActiveSheet()->getColumnDimension('I')->setWidth(5);
  $spreadsheet->getActiveSheet()->getColumnDimension('J')->setWidth(3);
  $spreadsheet->getActiveSheet()->getColumnDimension('K')->setWidth(4);


  // Redirect output to a client’s web browser (Xlsx)
  header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
  header('Content-Disposition: attachment;filename="01simple.xlsx"');
  header('Cache-Control: max-age=0');
  // If you're serving to IE 9, then the following may be needed
  header('Cache-Control: max-age=1');
// If you're serving to IE over SSL, then the following may be needed
  header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
  header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
  header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
  header ('Pragma: public'); // HTTP/1.0
  $objWriter = PHPExcel_IOFactory::createWriter($spreadsheet, 'Excel2007');
  $fileNameTmp = str_replace('.php', '-1.xlsx', __FILE__);
  $objWriter->save($fileNameTmp);
  readfile($fileNameTmp);
  exit;
}

function downloadCSV(){
  header('Content-Description: File Transfer');
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename="listadosplc.csv"');
  // Send Headers: Prevent Caching of File
  header('Cache-Control: private', false);
  header('Pragma: private');
  header("Expires: 0");
  $aUsers = exportUsers();
  $result = getCSVString($aUsers);
  
  header('Content-Length: ' . strlen($result)); 
  //header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
  //
  echo $result;
  exit;
}

function exportUsers(){
  $aAll = get_all_registrations(false, false, false);
  $aFinal = array();
  $aAux = array();
  $aUsersRepeated = array();
  foreach($aAll as $user){
    if ($user["paymentdone"]==1){   
      $aUsersRepeated[strtolower($user["email"])] = 1;
      $aFinal[] = $user;
    }
  }
  foreach($aAll as $user){
    if ($user["paymentdone"]==0){
      if (!isset($aUsersRepeated[strtolower($user["email"])])){
        $aUsersRepeated[strtolower($user["email"])] = 1;
        $aFinal[] = $user;
      }
    }
  }
  foreach($aFinal as $user){
    $aCurrent = array();
    $aCurrent["REGISTRO"] = $user["id"];

    if ($user["paymentmethod"]=="methodtransfer"){
      if ($user["paymentdone"]=="1"){
        $status = "OK. CONFIRMADO";
      }else{
        $status = "PTE. TRANSFERENCIA";
      }
    }else{
      if ($user["paymentdone"]=="1"){
        $status = "OK. CONFIRMADO";

      }else{
        $status = "SIN PAGAR";
      }
    }
    $aCurrent["SITUACION"] = $status;
    $aCurrent["APELLIDO - NOMBRE"] = $user["lastname"].", ".$user["firstname"];
    $aCurrent["BADGE NAME"] = $user["profile"];
    $aCurrent["affiliation"] = $user["affiliation"];
    switch ($user["regoption"]){
      case 1: $registrationtype = "ONLY MONDAY"; break;
      case 2: $registrationtype = "ONLY TUESDAY"; break;
      case 3: $registrationtype = "BOTH DAYS"; break;
      case 4: $registrationtype = "MAIN CONFERENCE"; break;
      case 5: $registrationtype = "ALL INCLUDED"; break;
    }
    $aCurrent["TIPO INSCRIPCION"]     = $registrationtype;
    
    $aCurrent["EMAIL"] = $user["email"];
    $aCurrent["FECHA DE SOLICITUD"] = substr($user["time"],0,10);
    $aCurrent["IMPORTE INSCRIPCION"]   = $user["totalamount"];
    $aCurrent["EASYCHAIR ID"]         = $user["easychairid"];
    $aCurrent["EXTRA DINNER"]         = $user["extradinner"];
    $aCurrent["IMPORTE EXTRA DINNER"] = $user["extradinner"]*60;
    $aCurrent["EMPTY"] = "";
    
    $aCurrent["FECHA REGISTRO"] = $user["time"];

    $aFieldsName = array("firstname", "lastname","profile","address","postcode","city","state","country",
                "phone","email","others","arrivaldate","departuredate","studentcheck","firsttime","paymentmethod",
                "billingname","billingvat","billingaddress","billingpostcode","billingcity","billingstate","billingcountry",
                "needvisa","privatepublic");
    foreach($aFieldsName as $key){
      $aCurrent[$key] = $user[$key];
    }    




    $aAux[] = $aCurrent;
  }
  return $aAux;
}

function getCSVString($aUsers){
 $sCsv = "REGISTRO;SITUACION;APELLIDO - NOMBRE;BADGE NAME;EMAIL;FECHA DE SOLICITUD; TIPO INSCRIPCION; IMPORTE INSCRIPCION;EASY CHAIR ID; EXTRA DINNER;IMPORTE EXTRA DINNER\n";
  foreach($aUsers as $aUser){
    $sCsv .= implode($aUser,";")."\n";
  }
  return $sCsv;
}

//DEPRECATED
function getCSV(){
 $aAll = get_all_registrations(false, false, false);
  $aAux = array();
  foreach($aAll as $user){
    $aCurrent = array();
    $aCurrent["REGISTRO"] = $user["id"];

    if ($user["paymentmethod"]=="methodtransfer"){
      $status = "PTE. TRANSFERENCIA";
    }else{
      if ($user["paymentdone"]=="1"){
        $status = "OK. CONFIRMADO";
      }else{
        $status = "CANCELADO";
      }
    }
    $aCurrent["SITUACION"] = $status;
    $aCurrent["APELLIDO - NOMBRE"] = $user["lastname"].", ".$user["firstname"];
    $aCurrent["BADGE NAME"] = $user["profile"];
    $aCurrent["EMAIL"] = $user["email"];
    $aCurrent["FECHA DE SOLICITUD"] = substr($user["time"],0,10);
    switch ($user["regoption"]){
      case 1: $registrationtype = "ONLY MONDAY"; break;
      case 2: $registrationtype = "ONLY TUESDAY"; break;
      case 3: $registrationtype = "BOTH DAYS"; break;
      case 4: $registrationtype = "MAIN CONFERENCE"; break;
      case 5: $registrationtype = "ALL INCLUDED"; break;
    }
    $aCurrent["TIPO INSCRIPCION"]     = $registrationtype;
    $aCurrent["IMPORTE INSCRIPCION"]   = $user["totalamount"];
    $aCurrent["EASYCHAIR ID"]         = $user["easychairid"];
    $aCurrent["EXTRA DINNER"]         = $user["extradinner"];
    $aCurrent["IMPORTE EXTRA DINNER"] = $user["extradinner"]*60;
    $aAux[] = $aCurrent;
  }   
  $sCsv = "REGISTRO;SITUACION;APELLIDO - NOMBRE;BADGE NAME;EMAIL;FECHA DE SOLICITUD; TIPO INSCRIPCION; IMPORTE INSCRIPCION;EASY CHAIR ID; EXTRA DINNER;IMPORTE EXTRA DINNER\n";
  foreach($aAux as $aUser){
    $sCsv .= implode($aUser,";")."\n";
  }
  return $sCsv;
}




function textRegistration($idReg){
  $sText = "";
  switch ($idReg){
    case "1":
      $sText = "Monday 25th September";
      break;
    case "2":
      $sText = "Tuesday 26th September";
      break;
    case "3":
      $sText = "Monday & Tuesday 25nd & 26rd September";
      break;
    case "4":
      $sText = "Main Conference (27th to 29th September)";
      break;
    case "5":
      $sText = "Full Registration (25nd to 29th September)";
      break;
  }
  return $sText;
}

function countryHtmlCombo($selectName){
  $sHtml = '';
  $sHtml .= '<select name="'.$selectName.'" id="'.$selectName.'">';
  $sHtml .= '<option value="AF">Afghanistan</option>
  <option value="AX">Aland Islands</option>
  <option value="AL">Albania</option>
  <option value="DZ">Algeria</option>
  <option value="AS">American Samoa</option>
  <option value="AD">Andorra</option>
  <option value="AO">Angola</option>
  <option value="AI">Anguilla</option>
  <option value="AQ">Antarctica</option>
  <option value="AG">Antigua and Barbuda</option>
  <option value="AR">Argentina</option>
  <option value="AM">Armenia</option>
  <option value="AW">Aruba</option>
  <option value="AU">Australia</option>
  <option value="AT">Austria</option>
  <option value="AZ">Azerbaijan</option>
  <option value="BS">Bahamas</option>
  <option value="BH">Bahrain</option>
  <option value="BD">Bangladesh</option>
  <option value="BB">Barbados</option>
  <option value="BY">Belarus</option>
  <option value="BE">Belgium</option>
  <option value="BZ">Belize</option>
  <option value="BJ">Benin</option>
  <option value="BM">Bermuda</option>
  <option value="BT">Bhutan</option>
  <option value="BO">Bolivia, Plurinational State of</option>
  <option value="BQ">Bonaire, Sint Eustatius and Saba</option>
  <option value="BA">Bosnia and Herzegovina</option>
  <option value="BW">Botswana</option>
  <option value="BV">Bouvet Island</option>
  <option value="BR">Brazil</option>
  <option value="IO">British Indian Ocean Territory</option>
  <option value="BN">Brunei Darussalam</option>
  <option value="BG">Bulgaria</option>
  <option value="BF">Burkina Faso</option>
  <option value="BI">Burundi</option>
  <option value="KH">Cambodia</option>
  <option value="CM">Cameroon</option>
  <option value="CA">Canada</option>
  <option value="CV">Cape Verde</option>
  <option value="KY">Cayman Islands</option>
  <option value="CF">Central African Republic</option>
  <option value="TD">Chad</option>
  <option value="CL">Chile</option>
  <option value="CN">China</option>
  <option value="CX">Christmas Island</option>
  <option value="CC">Cocos (Keeling) Islands</option>
  <option value="CO">Colombia</option>
  <option value="KM">Comoros</option>
  <option value="CG">Congo</option>
  <option value="CD">Congo, the Democratic Republic of the</option>
  <option value="CK">Cook Islands</option>
  <option value="CR">Costa Rica</option>
  <option value="CI">Côte d\'Ivoire</option>
  <option value="HR">Croatia</option>
  <option value="CU">Cuba</option>
  <option value="CW">Curaçao</option>
  <option value="CY">Cyprus</option>
  <option value="CZ">Czech Republic</option>
  <option value="DK">Denmark</option>
  <option value="DJ">Djibouti</option>
  <option value="DM">Dominica</option>
  <option value="DO">Dominican Republic</option>
  <option value="EC">Ecuador</option>
  <option value="EG">Egypt</option>
  <option value="SV">El Salvador</option>
  <option value="GQ">Equatorial Guinea</option>
  <option value="ER">Eritrea</option>
  <option value="EE">Estonia</option>
  <option value="ET">Ethiopia</option>
  <option value="FK">Falkland Islands (Malvinas)</option>
  <option value="FO">Faroe Islands</option>
  <option value="FJ">Fiji</option>
  <option value="FI">Finland</option>
  <option value="FR">France</option>
  <option value="GF">French Guiana</option>
  <option value="PF">French Polynesia</option>
  <option value="TF">French Southern Territories</option>
  <option value="GA">Gabon</option>
  <option value="GM">Gambia</option>
  <option value="GE">Georgia</option>
  <option value="DE">Germany</option>
  <option value="GH">Ghana</option>
  <option value="GI">Gibraltar</option>
  <option value="GR">Greece</option>
  <option value="GL">Greenland</option>
  <option value="GD">Grenada</option>
  <option value="GP">Guadeloupe</option>
  <option value="GU">Guam</option>
  <option value="GT">Guatemala</option>
  <option value="GG">Guernsey</option>
  <option value="GN">Guinea</option>
  <option value="GW">Guinea-Bissau</option>
  <option value="GY">Guyana</option>
  <option value="HT">Haiti</option>
  <option value="HM">Heard Island and McDonald Islands</option>
  <option value="VA">Holy See (Vatican City State)</option>
  <option value="HN">Honduras</option>
  <option value="HK">Hong Kong</option>
  <option value="HU">Hungary</option>
  <option value="IS">Iceland</option>
  <option value="IN">India</option>
  <option value="ID">Indonesia</option>
  <option value="IR">Iran, Islamic Republic of</option>
  <option value="IQ">Iraq</option>
  <option value="IE">Ireland</option>
  <option value="IM">Isle of Man</option>
  <option value="IL">Israel</option>
  <option value="IT">Italy</option>
  <option value="JM">Jamaica</option>
  <option value="JP">Japan</option>
  <option value="JE">Jersey</option>
  <option value="JO">Jordan</option>
  <option value="KZ">Kazakhstan</option>
  <option value="KE">Kenya</option>
  <option value="KI">Kiribati</option>
  <option value="KP">Korea, Democratic People\'s Republic of</option>
  <option value="KR">Korea, Republic of</option>
  <option value="KW">Kuwait</option>
  <option value="KG">Kyrgyzstan</option>
  <option value="LA">Lao People\'s Democratic Republic</option>
  <option value="LV">Latvia</option>
  <option value="LB">Lebanon</option>
  <option value="LS">Lesotho</option>
  <option value="LR">Liberia</option>
  <option value="LY">Libya</option>
  <option value="LI">Liechtenstein</option>
  <option value="LT">Lithuania</option>
  <option value="LU">Luxembourg</option>
  <option value="MO">Macao</option>
  <option value="MK">Macedonia, the former Yugoslav Republic of</option>
  <option value="MG">Madagascar</option>
  <option value="MW">Malawi</option>
  <option value="MY">Malaysia</option>
  <option value="MV">Maldives</option>
  <option value="ML">Mali</option>
  <option value="MT">Malta</option>
  <option value="MH">Marshall Islands</option>
  <option value="MQ">Martinique</option>
  <option value="MR">Mauritania</option>
  <option value="MU">Mauritius</option>
  <option value="YT">Mayotte</option>
  <option value="MX">Mexico</option>
  <option value="FM">Micronesia, Federated States of</option>
  <option value="MD">Moldova, Republic of</option>
  <option value="MC">Monaco</option>
  <option value="MN">Mongolia</option>
  <option value="ME">Montenegro</option>
  <option value="MS">Montserrat</option>
  <option value="MA">Morocco</option>
  <option value="MZ">Mozambique</option>
  <option value="MM">Myanmar</option>
  <option value="NA">Namibia</option>
  <option value="NR">Nauru</option>
  <option value="NP">Nepal</option>
  <option value="NL">Netherlands</option>
  <option value="NC">New Caledonia</option>
  <option value="NZ">New Zealand</option>
  <option value="NI">Nicaragua</option>
  <option value="NE">Niger</option>
  <option value="NG">Nigeria</option>
  <option value="NU">Niue</option>
  <option value="NF">Norfolk Island</option>
  <option value="MP">Northern Mariana Islands</option>
  <option value="NO">Norway</option>
  <option value="OM">Oman</option>
  <option value="PK">Pakistan</option>
  <option value="PW">Palau</option>
  <option value="PS">Palestinian Territory, Occupied</option>
  <option value="PA">Panama</option>
  <option value="PG">Papua New Guinea</option>
  <option value="PY">Paraguay</option>
  <option value="PE">Peru</option>
  <option value="PH">Philippines</option>
  <option value="PN">Pitcairn</option>
  <option value="PL">Poland</option>
  <option value="PT">Portugal</option>
  <option value="PR">Puerto Rico</option>
  <option value="QA">Qatar</option>
  <option value="RE">Réunion</option>
  <option value="RO">Romania</option>
  <option value="RU">Russian Federation</option>
  <option value="RW">Rwanda</option>
  <option value="BL">Saint Barthélemy</option>
  <option value="SH">Saint Helena, Ascension and Tristan da Cunha</option>
  <option value="KN">Saint Kitts and Nevis</option>
  <option value="LC">Saint Lucia</option>
  <option value="MF">Saint Martin (French part)</option>
  <option value="PM">Saint Pierre and Miquelon</option>
  <option value="VC">Saint Vincent and the Grenadines</option>
  <option value="WS">Samoa</option>
  <option value="SM">San Marino</option>
  <option value="ST">Sao Tome and Principe</option>
  <option value="SA">Saudi Arabia</option>
  <option value="SN">Senegal</option>
  <option value="RS">Serbia</option>
  <option value="SC">Seychelles</option>
  <option value="SL">Sierra Leone</option>
  <option value="SG">Singapore</option>
  <option value="SX">Sint Maarten (Dutch part)</option>
  <option value="SK">Slovakia</option>
  <option value="SI">Slovenia</option>
  <option value="SB">Solomon Islands</option>
  <option value="SO">Somalia</option>
  <option value="ZA">South Africa</option>
  <option value="GS">South Georgia and the South Sandwich Islands</option>
  <option value="SS">South Sudan</option>
  <option value="ES">Spain</option>
  <option value="LK">Sri Lanka</option>
  <option value="SD">Sudan</option>
  <option value="SR">Suriname</option>
  <option value="SJ">Svalbard and Jan Mayen</option>
  <option value="SZ">Swaziland</option>
  <option value="SE">Sweden</option>
  <option value="CH">Switzerland</option>
  <option value="SY">Syrian Arab Republic</option>
  <option value="TW">Taiwan, Province of China</option>
  <option value="TJ">Tajikistan</option>
  <option value="TZ">Tanzania, United Republic of</option>
  <option value="TH">Thailand</option>
  <option value="TL">Timor-Leste</option>
  <option value="TG">Togo</option>
  <option value="TK">Tokelau</option>
  <option value="TO">Tonga</option>
  <option value="TT">Trinidad and Tobago</option>
  <option value="TN">Tunisia</option>
  <option value="TR">Turkey</option>
  <option value="TM">Turkmenistan</option>
  <option value="TC">Turks and Caicos Islands</option>
  <option value="TV">Tuvalu</option>
  <option value="UG">Uganda</option>
  <option value="UA">Ukraine</option>
  <option value="AE">United Arab Emirates</option>
  <option value="GB">United Kingdom</option>
  <option value="US">United States</option>
  <option value="UM">United States Minor Outlying Islands</option>
  <option value="UY">Uruguay</option>
  <option value="UZ">Uzbekistan</option>
  <option value="VU">Vanuatu</option>
  <option value="VE">Venezuela, Bolivarian Republic of</option>
  <option value="VN">Viet Nam</option>
  <option value="VG">Virgin Islands, British</option>
  <option value="VI">Virgin Islands, U.S.</option>
  <option value="WF">Wallis and Futuna</option>
  <option value="EH">Western Sahara</option>
  <option value="YE">Yemen</option>
  <option value="ZM">Zambia</option>
  <option value="ZW">Zimbabwe</option>
</select>';
return $sHtml;
}  

function resultCountry($code){
  $countries = array
  (
    'AF' => 'Afghanistan',
    'AX' => 'Aland Islands',
    'AL' => 'Albania',
    'DZ' => 'Algeria',
    'AS' => 'American Samoa',
    'AD' => 'Andorra',
    'AO' => 'Angola',
    'AI' => 'Anguilla',
    'AQ' => 'Antarctica',
    'AG' => 'Antigua And Barbuda',
    'AR' => 'Argentina',
    'AM' => 'Armenia',
    'AW' => 'Aruba',
    'AU' => 'Australia',
    'AT' => 'Austria',
    'AZ' => 'Azerbaijan',
    'BS' => 'Bahamas',
    'BH' => 'Bahrain',
    'BD' => 'Bangladesh',
    'BB' => 'Barbados',
    'BY' => 'Belarus',
    'BE' => 'Belgium',
    'BZ' => 'Belize',
    'BJ' => 'Benin',
    'BM' => 'Bermuda',
    'BT' => 'Bhutan',
    'BO' => 'Bolivia',
    'BA' => 'Bosnia And Herzegovina',
    'BW' => 'Botswana',
    'BV' => 'Bouvet Island',
    'BR' => 'Brazil',
    'IO' => 'British Indian Ocean Territory',
    'BN' => 'Brunei Darussalam',
    'BG' => 'Bulgaria',
    'BF' => 'Burkina Faso',
    'BI' => 'Burundi',
    'KH' => 'Cambodia',
    'CM' => 'Cameroon',
    'CA' => 'Canada',
    'CV' => 'Cape Verde',
    'KY' => 'Cayman Islands',
    'CF' => 'Central African Republic',
    'TD' => 'Chad',
    'CL' => 'Chile',
    'CN' => 'China',
    'CX' => 'Christmas Island',
    'CC' => 'Cocos (Keeling) Islands',
    'CO' => 'Colombia',
    'KM' => 'Comoros',
    'CG' => 'Congo',
    'CD' => 'Congo, Democratic Republic',
    'CK' => 'Cook Islands',
    'CR' => 'Costa Rica',
    'CI' => 'Cote D\'Ivoire',
    'HR' => 'Croatia',
    'CU' => 'Cuba',
    'CY' => 'Cyprus',
    'CZ' => 'Czech Republic',
    'DK' => 'Denmark',
    'DJ' => 'Djibouti',
    'DM' => 'Dominica',
    'DO' => 'Dominican Republic',
    'EC' => 'Ecuador',
    'EG' => 'Egypt',
    'SV' => 'El Salvador',
    'GQ' => 'Equatorial Guinea',
    'ER' => 'Eritrea',
    'EE' => 'Estonia',
    'ET' => 'Ethiopia',
    'FK' => 'Falkland Islands (Malvinas)',
    'FO' => 'Faroe Islands',
    'FJ' => 'Fiji',
    'FI' => 'Finland',
    'FR' => 'France',
    'GF' => 'French Guiana',
    'PF' => 'French Polynesia',
    'TF' => 'French Southern Territories',
    'GA' => 'Gabon',
    'GM' => 'Gambia',
    'GE' => 'Georgia',
    'DE' => 'Germany',
    'GH' => 'Ghana',
    'GI' => 'Gibraltar',
    'GR' => 'Greece',
    'GL' => 'Greenland',
    'GD' => 'Grenada',
    'GP' => 'Guadeloupe',
    'GU' => 'Guam',
    'GT' => 'Guatemala',
    'GG' => 'Guernsey',
    'GN' => 'Guinea',
    'GW' => 'Guinea-Bissau',
    'GY' => 'Guyana',
    'HT' => 'Haiti',
    'HM' => 'Heard Island & Mcdonald Islands',
    'VA' => 'Holy See (Vatican City State)',
    'HN' => 'Honduras',
    'HK' => 'Hong Kong',
    'HU' => 'Hungary',
    'IS' => 'Iceland',
    'IN' => 'India',
    'ID' => 'Indonesia',
    'IR' => 'Iran, Islamic Republic Of',
    'IQ' => 'Iraq',
    'IE' => 'Ireland',
    'IM' => 'Isle Of Man',
    'IL' => 'Israel',
    'IT' => 'Italy',
    'JM' => 'Jamaica',
    'JP' => 'Japan',
    'JE' => 'Jersey',
    'JO' => 'Jordan',
    'KZ' => 'Kazakhstan',
    'KE' => 'Kenya',
    'KI' => 'Kiribati',
    'KR' => 'Korea',
    'KW' => 'Kuwait',
    'KG' => 'Kyrgyzstan',
    'LA' => 'Lao People\'s Democratic Republic',
    'LV' => 'Latvia',
    'LB' => 'Lebanon',
    'LS' => 'Lesotho',
    'LR' => 'Liberia',
    'LY' => 'Libyan Arab Jamahiriya',
    'LI' => 'Liechtenstein',
    'LT' => 'Lithuania',
    'LU' => 'Luxembourg',
    'MO' => 'Macao',
    'MK' => 'Macedonia',
    'MG' => 'Madagascar',
    'MW' => 'Malawi',
    'MY' => 'Malaysia',
    'MV' => 'Maldives',
    'ML' => 'Mali',
    'MT' => 'Malta',
    'MH' => 'Marshall Islands',
    'MQ' => 'Martinique',
    'MR' => 'Mauritania',
    'MU' => 'Mauritius',
    'YT' => 'Mayotte',
    'MX' => 'Mexico',
    'FM' => 'Micronesia, Federated States Of',
    'MD' => 'Moldova',
    'MC' => 'Monaco',
    'MN' => 'Mongolia',
    'ME' => 'Montenegro',
    'MS' => 'Montserrat',
    'MA' => 'Morocco',
    'MZ' => 'Mozambique',
    'MM' => 'Myanmar',
    'NA' => 'Namibia',
    'NR' => 'Nauru',
    'NP' => 'Nepal',
    'NL' => 'Netherlands',
    'AN' => 'Netherlands Antilles',
    'NC' => 'New Caledonia',
    'NZ' => 'New Zealand',
    'NI' => 'Nicaragua',
    'NE' => 'Niger',
    'NG' => 'Nigeria',
    'NU' => 'Niue',
    'NF' => 'Norfolk Island',
    'MP' => 'Northern Mariana Islands',
    'NO' => 'Norway',
    'OM' => 'Oman',
    'PK' => 'Pakistan',
    'PW' => 'Palau',
    'PS' => 'Palestinian Territory, Occupied',
    'PA' => 'Panama',
    'PG' => 'Papua New Guinea',
    'PY' => 'Paraguay',
    'PE' => 'Peru',
    'PH' => 'Philippines',
    'PN' => 'Pitcairn',
    'PL' => 'Poland',
    'PT' => 'Portugal',
    'PR' => 'Puerto Rico',
    'QA' => 'Qatar',
    'RE' => 'Reunion',
    'RO' => 'Romania',
    'RU' => 'Russian Federation',
    'RW' => 'Rwanda',
    'BL' => 'Saint Barthelemy',
    'SH' => 'Saint Helena',
    'KN' => 'Saint Kitts And Nevis',
    'LC' => 'Saint Lucia',
    'MF' => 'Saint Martin',
    'PM' => 'Saint Pierre And Miquelon',
    'VC' => 'Saint Vincent And Grenadines',
    'WS' => 'Samoa',
    'SM' => 'San Marino',
    'ST' => 'Sao Tome And Principe',
    'SA' => 'Saudi Arabia',
    'SN' => 'Senegal',
    'RS' => 'Serbia',
    'SC' => 'Seychelles',
    'SL' => 'Sierra Leone',
    'SG' => 'Singapore',
    'SK' => 'Slovakia',
    'SI' => 'Slovenia',
    'SB' => 'Solomon Islands',
    'SO' => 'Somalia',
    'ZA' => 'South Africa',
    'GS' => 'South Georgia And Sandwich Isl.',
    'ES' => 'Spain',
    'LK' => 'Sri Lanka',
    'SD' => 'Sudan',
    'SR' => 'Suriname',
    'SJ' => 'Svalbard And Jan Mayen',
    'SZ' => 'Swaziland',
    'SE' => 'Sweden',
    'CH' => 'Switzerland',
    'SY' => 'Syrian Arab Republic',
    'TW' => 'Taiwan',
    'TJ' => 'Tajikistan',
    'TZ' => 'Tanzania',
    'TH' => 'Thailand',
    'TL' => 'Timor-Leste',
    'TG' => 'Togo',
    'TK' => 'Tokelau',
    'TO' => 'Tonga',
    'TT' => 'Trinidad And Tobago',
    'TN' => 'Tunisia',
    'TR' => 'Turkey',
    'TM' => 'Turkmenistan',
    'TC' => 'Turks And Caicos Islands',
    'TV' => 'Tuvalu',
    'UG' => 'Uganda',
    'UA' => 'Ukraine',
    'AE' => 'United Arab Emirates',
    'GB' => 'United Kingdom',
    'US' => 'United States',
    'UM' => 'United States Outlying Islands',
    'UY' => 'Uruguay',
    'UZ' => 'Uzbekistan',
    'VU' => 'Vanuatu',
    'VE' => 'Venezuela',
    'VN' => 'Viet Nam',
    'VG' => 'Virgin Islands, British',
    'VI' => 'Virgin Islands, U.S.',
    'WF' => 'Wallis And Futuna',
    'EH' => 'Western Sahara',
    'YE' => 'Yemen',
    'ZM' => 'Zambia',
    'ZW' => 'Zimbabwe',
  );
  return $countries[$code];
}   

function getContinent($countrycode){
  $countries = array(
        "AF" => array( 'alpha2'=>'AF', 'alpha3'=>'AFG', 'num'=>'004', 'isd'=> '93', "name" => "Afghanistan", "continent" => "Asia", ),
        "MF" => array( 'alpha2'=>'MF', 'alpha3'=>'MF', 'num'=>'NO', 'isd'=> 'NO', "name" => "Saint Martin", "continent" => "North America", ),
        "AX" => array( 'alpha2'=>'AX', 'alpha3'=>'ALA', 'num'=>'248', 'isd'=> '358', "name" => "Åland Islands", "continent" => "Europe"),
        "AL" => array( 'alpha2'=>'AL', 'alpha3'=>'ALB', 'num'=>'008', 'isd'=> '355', "name" => "Albania", "continent" => "Europe"),
        "DZ" => array( 'alpha2'=>'DZ', 'alpha3'=>'DZA', 'num'=>'012', 'isd'=> '213', "name" => "Algeria", "continent" => "Africa"),
        "AS" => array( 'alpha2'=>'AS', 'alpha3'=>'ASM', 'num'=>'016', 'isd'=> '1684', "name" => "American Samoa", "continent" => "Oceania"),
        "AD" => array( 'alpha2'=>'AD', 'alpha3'=>'AND', 'num'=>'020', 'isd'=> '376', "name" => "Andorra", "continent" => "Europe"),
        "AO" => array( 'alpha2'=>'AO', 'alpha3'=>'AGO', 'num'=>'024', 'isd'=> '244', "name" => "Angola", "continent" => "Africa"),
        "AI" => array( 'alpha2'=>'AI', 'alpha3'=>'AIA', 'num'=>'660', 'isd'=> '1264', "name" => "Anguilla", "continent" => "North America"),
        "AQ" => array( 'alpha2'=>'AQ', 'alpha3'=>'ATA', 'num'=>'010', 'isd'=> '672', "name" => "Antarctica", "continent" => "Antarctica"),
        "AG" => array( 'alpha2'=>'AG', 'alpha3'=>'ATG', 'num'=>'028', 'isd'=> '1268', "name" => "Antigua and Barbuda", "continent" => "North America"),
        "AR" => array( 'alpha2'=>'AR', 'alpha3'=>'ARG', 'num'=>'032', 'isd'=> '54', "name" => "Argentina", "continent" => "South America"),
        "AM" => array( 'alpha2'=>'AM', 'alpha3'=>'ARM', 'num'=>'051', 'isd'=> '374', "name" => "Armenia", "continent" => "Asia"),
        "AW" => array( 'alpha2'=>'AW', 'alpha3'=>'ABW', 'num'=>'533', 'isd'=> '297', "name" => "Aruba", "continent" => "North America"),
        "AU" => array( 'alpha2'=>'AU', 'alpha3'=>'AUS', 'num'=>'036', 'isd'=> '61', "name" => "Australia", "continent" => "Oceania"),
        "AT" => array( 'alpha2'=>'AT', 'alpha3'=>'AUT', 'num'=>'040', 'isd'=> '43', "name" => "Austria", "continent" => "Europe"),
        "AZ" => array( 'alpha2'=>'AZ', 'alpha3'=>'AZE', 'num'=>'031', 'isd'=> '994', "name" => "Azerbaijan", "continent" => "Asia"),
        "BS" => array( 'alpha2'=>'BS', 'alpha3'=>'BHS', 'num'=>'044', 'isd'=> '1242', "name" => "Bahamas", "continent" => "North America"),
        "BH" => array( 'alpha2'=>'BH', 'alpha3'=>'BHR', 'num'=>'048', 'isd'=> '973', "name" => "Bahrain", "continent" => "Asia"),
        "BD" => array( 'alpha2'=>'BD', 'alpha3'=>'BGD', 'num'=>'050', 'isd'=> '880', "name" => "Bangladesh", "continent" => "Asia"),
        "BB" => array( 'alpha2'=>'BB', 'alpha3'=>'BRB', 'num'=>'052', 'isd'=> '1246', "name" => "Barbados", "continent" => "North America"),
        "BY" => array( 'alpha2'=>'BY', 'alpha3'=>'BLR', 'num'=>'112', 'isd'=> '375', "name" => "Belarus", "continent" => "Europe"),
        "BE" => array( 'alpha2'=>'BE', 'alpha3'=>'BEL', 'num'=>'056', 'isd'=> '32', "name" => "Belgium", "continent" => "Europe"),
        "BZ" => array( 'alpha2'=>'BZ', 'alpha3'=>'BLZ', 'num'=>'084', 'isd'=> '501', "name" => "Belize", "continent" => "North America"),
        "BJ" => array( 'alpha2'=>'BJ', 'alpha3'=>'BEN', 'num'=>'204', 'isd'=> '229', "name" => "Benin", "continent" => "Africa"),
        "BM" => array( 'alpha2'=>'BM', 'alpha3'=>'BMU', 'num'=>'060', 'isd'=> '1441', "name" => "Bermuda", "continent" => "North America"),
        "BT" => array( 'alpha2'=>'BT', 'alpha3'=>'BTN', 'num'=>'064', 'isd'=> '975', "name" => "Bhutan", "continent" => "Asia"),
        "BO" => array( 'alpha2'=>'BO', 'alpha3'=>'BOL', 'num'=>'068', 'isd'=> '591', "name" => "Bolivia", "continent" => "South America"),
        "BA" => array( 'alpha2'=>'BA', 'alpha3'=>'BIH', 'num'=>'070', 'isd'=> '387', "name" => "Bosnia and Herzegovina", "continent" => "Europe"),
        "BW" => array( 'alpha2'=>'BW', 'alpha3'=>'BWA', 'num'=>'072', 'isd'=> '267', "name" => "Botswana", "continent" => "Africa"),
        "BV" => array( 'alpha2'=>'BV', 'alpha3'=>'BVT', 'num'=>'074', 'isd'=> '61', "name" => "Bouvet Island", "continent" => "Antarctica"),
        "BR" => array( 'alpha2'=>'BR', 'alpha3'=>'BRA', 'num'=>'076', 'isd'=> '55', "name" => "Brazil", "continent" => "South America"),
        "IO" => array( 'alpha2'=>'IO', 'alpha3'=>'IOT', 'num'=>'086', 'isd'=> '246', "name" => "British Indian Ocean Territory", "continent" => "Asia"),
        "BN" => array( 'alpha2'=>'BN', 'alpha3'=>'BRN', 'num'=>'096', 'isd'=> '672', "name" => "Brunei Darussalam", "continent" => "Asia"),
        "BG" => array( 'alpha2'=>'BG', 'alpha3'=>'BGR', 'num'=>'100', 'isd'=> '359', "name" => "Bulgaria", "continent" => "Europe"),
        "BF" => array( 'alpha2'=>'BF', 'alpha3'=>'BFA', 'num'=>'854', 'isd'=> '226', "name" => "Burkina Faso", "continent" => "Africa"),
        "BI" => array( 'alpha2'=>'BI', 'alpha3'=>'BDI', 'num'=>'108', 'isd'=> '257', "name" => "Burundi", "continent" => "Africa"),
        "KH" => array( 'alpha2'=>'KH', 'alpha3'=>'KHM', 'num'=>'116', 'isd'=> '855', "name" => "Cambodia", "continent" => "Asia"),
        "CM" => array( 'alpha2'=>'CM', 'alpha3'=>'CMR', 'num'=>'120', 'isd'=> '231', "name" => "Cameroon", "continent" => "Africa"),
        "CA" => array( 'alpha2'=>'CA', 'alpha3'=>'CAN', 'num'=>'124', 'isd'=> '1', "name" => "Canada", "continent" => "North America"),
        "CV" => array( 'alpha2'=>'CV', 'alpha3'=>'CPV', 'num'=>'132', 'isd'=> '238', "name" => "Cape Verde", "continent" => "Africa"),
        "KY" => array( 'alpha2'=>'KY', 'alpha3'=>'CYM', 'num'=>'136', 'isd'=> '1345', "name" => "Cayman Islands", "continent" => "North America"),
        "CF" => array( 'alpha2'=>'CF', 'alpha3'=>'CAF', 'num'=>'140', 'isd'=> '236', "name" => "Central African Republic", "continent" => "Africa"),
        "TD" => array( 'alpha2'=>'TD', 'alpha3'=>'TCD', 'num'=>'148', 'isd'=> '235', "name" => "Chad", "continent" => "Africa"),
        "CL" => array( 'alpha2'=>'CL', 'alpha3'=>'CHL', 'num'=>'152', 'isd'=> '56', "name" => "Chile", "continent" => "South America"),
        "CN" => array( 'alpha2'=>'CN', 'alpha3'=>'CHN', 'num'=>'156', 'isd'=> '86', "name" => "China", "continent" => "Asia"),
        "CX" => array( 'alpha2'=>'CX', 'alpha3'=>'CXR', 'num'=>'162', 'isd'=> '61', "name" => "Christmas Island", "continent" => "Asia"),
        "CC" => array( 'alpha2'=>'CC', 'alpha3'=>'CCK', 'num'=>'166', 'isd'=> '891', "name" => "Cocos (Keeling) Islands", "continent" => "Asia"),
        "CO" => array( 'alpha2'=>'CO', 'alpha3'=>'COL', 'num'=>'170', 'isd'=> '57', "name" => "Colombia", "continent" => "South America"),
        "KM" => array( 'alpha2'=>'KM', 'alpha3'=>'COM', 'num'=>'174', 'isd'=> '269', "name" => "Comoros", "continent" => "Africa"),
        "CG" => array( 'alpha2'=>'CG', 'alpha3'=>'COG', 'num'=>'178', 'isd'=> '242', "name" => "Congo", "continent" => "Africa"),
        "CD" => array( 'alpha2'=>'CD', 'alpha3'=>'COD', 'num'=>'180', 'isd'=> '243', "name" => "The Democratic Republic of The Congo", "continent" => "Africa"),
        "CK" => array( 'alpha2'=>'CK', 'alpha3'=>'COK', 'num'=>'184', 'isd'=> '682', "name" => "Cook Islands", "continent" => "Oceania"),
        "CR" => array( 'alpha2'=>'CR', 'alpha3'=>'CRI', 'num'=>'188', 'isd'=> '506', "name" => "Costa Rica", "continent" => "North America"),
        "CI" => array( 'alpha2'=>'CI', 'alpha3'=>'CIV', 'num'=>'384', 'isd'=> '225', "name" => "Cote D'ivoire", "continent" => "Africa"),
        "HR" => array( 'alpha2'=>'HR', 'alpha3'=>'HRV', 'num'=>'191', 'isd'=> '385', "name" => "Croatia", "continent" => "Europe"),
        "CU" => array( 'alpha2'=>'CU', 'alpha3'=>'CUB', 'num'=>'192', 'isd'=> '53', "name" => "Cuba", "continent" => "North America"),
        "CY" => array( 'alpha2'=>'CY', 'alpha3'=>'CYP', 'num'=>'196', 'isd'=> '357', "name" => "Cyprus", "continent" => "Asia"),
        "CZ" => array( 'alpha2'=>'CZ', 'alpha3'=>'CZE', 'num'=>'203', 'isd'=> '420', "name" => "Czech Republic", "continent" => "Europe"),
        "DK" => array( 'alpha2'=>'DK', 'alpha3'=>'DNK', 'num'=>'208', 'isd'=> '45', "name" => "Denmark", "continent" => "Europe"),
        "DJ" => array( 'alpha2'=>'DJ', 'alpha3'=>'DJI', 'num'=>'262', 'isd'=> '253', "name" => "Djibouti", "continent" => "Africa"),
        "DM" => array( 'alpha2'=>'DM', 'alpha3'=>'DMA', 'num'=>'212', 'isd'=> '1767', "name" => "Dominica", "continent" => "North America"),
        "DO" => array( 'alpha2'=>'DO', 'alpha3'=>'DOM', 'num'=>'214', 'isd'=> '1809', "name" => "Dominican Republic", "continent" => "North America"),
        "EC" => array( 'alpha2'=>'EC', 'alpha3'=>'ECU', 'num'=>'218', 'isd'=> '593', "name" => "Ecuador", "continent" => "South America"),
        "EG" => array( 'alpha2'=>'EG', 'alpha3'=>'EGY', 'num'=>'818', 'isd'=> '20', "name" => "Egypt", "continent" => "Africa"),
        "SV" => array( 'alpha2'=>'SV', 'alpha3'=>'SLV', 'num'=>'222', 'isd'=> '503', "name" => "El Salvador", "continent" => "North America"),
        "GQ" => array( 'alpha2'=>'GQ', 'alpha3'=>'GNQ', 'num'=>'226', 'isd'=> '240', "name" => "Equatorial Guinea", "continent" => "Africa"),
        "ER" => array( 'alpha2'=>'ER', 'alpha3'=>'ERI', 'num'=>'232', 'isd'=> '291', "name" => "Eritrea", "continent" => "Africa"),
        "EE" => array( 'alpha2'=>'EE', 'alpha3'=>'EST', 'num'=>'233', 'isd'=> '372', "name" => "Estonia", "continent" => "Europe"),
        "ET" => array( 'alpha2'=>'ET', 'alpha3'=>'ETH', 'num'=>'231', 'isd'=> '251', "name" => "Ethiopia", "continent" => "Africa"),
        "FK" => array( 'alpha2'=>'FK', 'alpha3'=>'FLK', 'num'=>'238', 'isd'=> '500', "name" => "Falkland Islands (Malvinas)", "continent" => "South America"),
        "FO" => array( 'alpha2'=>'FO', 'alpha3'=>'FRO', 'num'=>'234', 'isd'=> '298', "name" => "Faroe Islands", "continent" => "Europe"),
        "FJ" => array( 'alpha2'=>'FJ', 'alpha3'=>'FJI', 'num'=>'243', 'isd'=> '679', "name" => "Fiji", "continent" => "Oceania"),
        "FI" => array( 'alpha2'=>'FI', 'alpha3'=>'FIN', 'num'=>'246', 'isd'=> '238', "name" => "Finland", "continent" => "Europe"),
        "FR" => array( 'alpha2'=>'FR', 'alpha3'=>'FRA', 'num'=>'250', 'isd'=> '33', "name" => "France", "continent" => "Europe"),
        "GF" => array( 'alpha2'=>'GF', 'alpha3'=>'GUF', 'num'=>'254', 'isd'=> '594', "name" => "French Guiana", "continent" => "South America"),
        "PF" => array( 'alpha2'=>'PF', 'alpha3'=>'PYF', 'num'=>'258', 'isd'=> '689', "name" => "French Polynesia", "continent" => "Oceania"),
        "TF" => array( 'alpha2'=>'TF', 'alpha3'=>'ATF', 'num'=>'260', 'isd'=> '262', "name" => "French Southern Territories", "continent" => "Antarctica"),
        "GA" => array( 'alpha2'=>'GA', 'alpha3'=>'GAB', 'num'=>'266', 'isd'=> '241', "name" => "Gabon", "continent" => "Africa"),
        "GM" => array( 'alpha2'=>'GM', 'alpha3'=>'GMB', 'num'=>'270', 'isd'=> '220', "name" => "Gambia", "continent" => "Africa"),
        "GE" => array( 'alpha2'=>'GE', 'alpha3'=>'GEO', 'num'=>'268', 'isd'=> '995', "name" => "Georgia", "continent" => "Asia"),
        "DE" => array( 'alpha2'=>'DE', 'alpha3'=>'DEU', 'num'=>'276', 'isd'=> '49', "name" => "Germany", "continent" => "Europe"),
        "GH" => array( 'alpha2'=>'GH', 'alpha3'=>'GHA', 'num'=>'288', 'isd'=> '233', "name" => "Ghana", "continent" => "Africa"),
        "GI" => array( 'alpha2'=>'GI', 'alpha3'=>'GIB', 'num'=>'292', 'isd'=> '350', "name" => "Gibraltar", "continent" => "Europe"),
        "GR" => array( 'alpha2'=>'GR', 'alpha3'=>'GRC', 'num'=>'300', 'isd'=> '30', "name" => "Greece", "continent" => "Europe"),
        "GL" => array( 'alpha2'=>'GL', 'alpha3'=>'GRL', 'num'=>'304', 'isd'=> '299', "name" => "Greenland", "continent" => "North America"),
        "GD" => array( 'alpha2'=>'GD', 'alpha3'=>'GRD', 'num'=>'308', 'isd'=> '1473', "name" => "Grenada", "continent" => "North America"),
        "GP" => array( 'alpha2'=>'GP', 'alpha3'=>'GLP', 'num'=>'312', 'isd'=> '590', "name" => "Guadeloupe", "continent" => "North America"),
        "GU" => array( 'alpha2'=>'GU', 'alpha3'=>'GUM', 'num'=>'316', 'isd'=> '1871', "name" => "Guam", "continent" => "Oceania"),
        "GT" => array( 'alpha2'=>'GT', 'alpha3'=>'GTM', 'num'=>'320', 'isd'=> '502', "name" => "Guatemala", "continent" => "North America"),
        "GG" => array( 'alpha2'=>'GG', 'alpha3'=>'GGY', 'num'=>'831', 'isd'=> '44', "name" => "Guernsey", "continent" => "Europe"),
        "GN" => array( 'alpha2'=>'GN', 'alpha3'=>'GIN', 'num'=>'324', 'isd'=> '224', "name" => "Guinea", "continent" => "Africa"),
        "GW" => array( 'alpha2'=>'GW', 'alpha3'=>'GNB', 'num'=>'624', 'isd'=> '245', "name" => "Guinea-bissau", "continent" => "Africa"),
        "GY" => array( 'alpha2'=>'GY', 'alpha3'=>'GUY', 'num'=>'328', 'isd'=> '592', "name" => "Guyana", "continent" => "South America"),
        "HT" => array( 'alpha2'=>'HT', 'alpha3'=>'HTI', 'num'=>'332', 'isd'=> '509', "name" => "Haiti", "continent" => "North America"),
        "HM" => array( 'alpha2'=>'HM', 'alpha3'=>'HMD', 'num'=>'334', 'isd'=> '672', "name" => "Heard Island and Mcdonald Islands", "continent" => "Antarctica"),
        "VA" => array( 'alpha2'=>'VA', 'alpha3'=>'VAT', 'num'=>'336', 'isd'=> '379', "name" => "Holy See (Vatican City State)", "continent" => "Europe"),
        "HN" => array( 'alpha2'=>'HN', 'alpha3'=>'HND', 'num'=>'340', 'isd'=> '504', "name" => "Honduras", "continent" => "North America"),
        "HK" => array( 'alpha2'=>'HK', 'alpha3'=>'HKG', 'num'=>'344', 'isd'=> '852', "name" => "Hong Kong", "continent" => "Asia"),
        "HU" => array( 'alpha2'=>'HU', 'alpha3'=>'HUN', 'num'=>'348', 'isd'=> '36', "name" => "Hungary", "continent" => "Europe"),
        "IS" => array( 'alpha2'=>'IS', 'alpha3'=>'ISL', 'num'=>'352', 'isd'=> '354', "name" => "Iceland", "continent" => "Europe"),
        "IN" => array( 'alpha2'=>'IN', 'alpha3'=>'IND', 'num'=>'356', 'isd'=> '91', "name" => "India", "continent" => "Asia"),
        "ID" => array( 'alpha2'=>'ID', 'alpha3'=>'IDN', 'num'=>'360', 'isd'=> '62', "name" => "Indonesia", "continent" => "Asia"),
        "IR" => array( 'alpha2'=>'IR', 'alpha3'=>'IRN', 'num'=>'364', 'isd'=> '98', "name" => "Iran", "continent" => "Asia"),
        "IQ" => array( 'alpha2'=>'IQ', 'alpha3'=>'IRQ', 'num'=>'368', 'isd'=> '964', "name" => "Iraq", "continent" => "Asia"),
        "IE" => array( 'alpha2'=>'IE', 'alpha3'=>'IRL', 'num'=>'372', 'isd'=> '353', "name" => "Ireland", "continent" => "Europe"),
        "IM" => array( 'alpha2'=>'IM', 'alpha3'=>'IMN', 'num'=>'833', 'isd'=> '44', "name" => "Isle of Man", "continent" => "Europe"),
        "IL" => array( 'alpha2'=>'IL', 'alpha3'=>'ISR', 'num'=>'376', 'isd'=> '972', "name" => "Israel", "continent" => "Asia"),
        "IT" => array( 'alpha2'=>'IT', 'alpha3'=>'ITA', 'num'=>'380', 'isd'=> '39', "name" => "Italy", "continent" => "Europe"),
        "JM" => array( 'alpha2'=>'JM', 'alpha3'=>'JAM', 'num'=>'388', 'isd'=> '1876', "name" => "Jamaica", "continent" => "North America"),
        "JP" => array( 'alpha2'=>'JP', 'alpha3'=>'JPN', 'num'=>'392', 'isd'=> '81', "name" => "Japan", "continent" => "Asia"),
        "JE" => array( 'alpha2'=>'JE', 'alpha3'=>'JEY', 'num'=>'832', 'isd'=> '44', "name" => "Jersey", "continent" => "Europe"),
        "JO" => array( 'alpha2'=>'JO', 'alpha3'=>'JOR', 'num'=>'400', 'isd'=> '962', "name" => "Jordan", "continent" => "Asia"),
        "KZ" => array( 'alpha2'=>'KZ', 'alpha3'=>'KAZ', 'num'=>'398', 'isd'=> '7', "name" => "Kazakhstan", "continent" => "Asia"),
        "KE" => array( 'alpha2'=>'KE', 'alpha3'=>'KEN', 'num'=>'404', 'isd'=> '254', "name" => "Kenya", "continent" => "Africa"),
        "KI" => array( 'alpha2'=>'KI', 'alpha3'=>'KIR', 'num'=>'296', 'isd'=> '686', "name" => "Kiribati", "continent" => "Oceania"),
        "KP" => array( 'alpha2'=>'KP', 'alpha3'=>'PRK', 'num'=>'408', 'isd'=> '850', "name" => "Democratic People's Republic of Korea", "continent" => "Asia"),
        "KR" => array( 'alpha2'=>'KR', 'alpha3'=>'KOR', 'num'=>'410', 'isd'=> '82', "name" => "Republic of Korea", "continent" => "Asia"),
        "KW" => array( 'alpha2'=>'KW', 'alpha3'=>'KWT', 'num'=>'414', 'isd'=> '965', "name" => "Kuwait", "continent" => "Asia"),
        "KG" => array( 'alpha2'=>'KG', 'alpha3'=>'KGZ', 'num'=>'417', 'isd'=> '996', "name" => "Kyrgyzstan", "continent" => "Asia"),
        "LA" => array( 'alpha2'=>'LA', 'alpha3'=>'LAO', 'num'=>'418', 'isd'=> '856', "name" => "Lao People's Democratic Republic", "continent" => "Asia"),
        "LV" => array( 'alpha2'=>'LV', 'alpha3'=>'LVA', 'num'=>'428', 'isd'=> '371', "name" => "Latvia", "continent" => "Europe"),
        "LB" => array( 'alpha2'=>'LB', 'alpha3'=>'LBN', 'num'=>'422', 'isd'=> '961', "name" => "Lebanon", "continent" => "Asia"),
        "LS" => array( 'alpha2'=>'LS', 'alpha3'=>'LSO', 'num'=>'426', 'isd'=> '266', "name" => "Lesotho", "continent" => "Africa"),
        "LR" => array( 'alpha2'=>'LR', 'alpha3'=>'LBR', 'num'=>'430', 'isd'=> '231', "name" => "Liberia", "continent" => "Africa"),
        "LY" => array( 'alpha2'=>'LY', 'alpha3'=>'LBY', 'num'=>'434', 'isd'=> '218', "name" => "Libya", "continent" => "Africa"),
        "LI" => array( 'alpha2'=>'LI', 'alpha3'=>'LIE', 'num'=>'438', 'isd'=> '423', "name" => "Liechtenstein", "continent" => "Europe"),
        "LT" => array( 'alpha2'=>'LT', 'alpha3'=>'LTU', 'num'=>'440', 'isd'=> '370', "name" => "Lithuania", "continent" => "Europe"),
        "LU" => array( 'alpha2'=>'LU', 'alpha3'=>'LUX', 'num'=>'442', 'isd'=> '352', "name" => "Luxembourg", "continent" => "Europe"),
        "MO" => array( 'alpha2'=>'MO', 'alpha3'=>'MAC', 'num'=>'446', 'isd'=> '853', "name" => "Macao", "continent" => "Asia"),
        "MK" => array( 'alpha2'=>'MK', 'alpha3'=>'MKD', 'num'=>'807', 'isd'=> '389', "name" => "Macedonia", "continent" => "Europe"),
        "MG" => array( 'alpha2'=>'MG', 'alpha3'=>'MDG', 'num'=>'450', 'isd'=> '261', "name" => "Madagascar", "continent" => "Africa"),
        "MW" => array( 'alpha2'=>'MW', 'alpha3'=>'MWI', 'num'=>'454', 'isd'=> '265', "name" => "Malawi", "continent" => "Africa"),
        "MY" => array( 'alpha2'=>'MY', 'alpha3'=>'MYS', 'num'=>'458', 'isd'=> '60', "name" => "Malaysia", "continent" => "Asia"),
        "MV" => array( 'alpha2'=>'MV', 'alpha3'=>'MDV', 'num'=>'462', 'isd'=> '960', "name" => "Maldives", "continent" => "Asia"),
        "ML" => array( 'alpha2'=>'ML', 'alpha3'=>'MLI', 'num'=>'466', 'isd'=> '223', "name" => "Mali", "continent" => "Africa"),
        "MT" => array( 'alpha2'=>'MT', 'alpha3'=>'MLT', 'num'=>'470', 'isd'=> '356', "name" => "Malta", "continent" => "Europe"),
        "MH" => array( 'alpha2'=>'MH', 'alpha3'=>'MHL', 'num'=>'584', 'isd'=> '692', "name" => "Marshall Islands", "continent" => "Oceania"),
        "MQ" => array( 'alpha2'=>'MQ', 'alpha3'=>'MTQ', 'num'=>'474', 'isd'=> '596', "name" => "Martinique", "continent" => "North America"),
        "MR" => array( 'alpha2'=>'MR', 'alpha3'=>'MRT', 'num'=>'478', 'isd'=> '222', "name" => "Mauritania", "continent" => "Africa"),
        "MU" => array( 'alpha2'=>'MU', 'alpha3'=>'MUS', 'num'=>'480', 'isd'=> '230', "name" => "Mauritius", "continent" => "Africa"),
        "YT" => array( 'alpha2'=>'YT', 'alpha3'=>'MYT', 'num'=>'175', 'isd'=> '262', "name" => "Mayotte", "continent" => "Africa"),
        "MX" => array( 'alpha2'=>'MX', 'alpha3'=>'MEX', 'num'=>'484', 'isd'=> '52', "name" => "Mexico", "continent" => "North America"),
        "FM" => array( 'alpha2'=>'FM', 'alpha3'=>'FSM', 'num'=>'583', 'isd'=> '691', "name" => "Micronesia", "continent" => "Oceania"),
        "MD" => array( 'alpha2'=>'MD', 'alpha3'=>'MDA', 'num'=>'498', 'isd'=> '373', "name" => "Moldova", "continent" => "Europe"),
        "MC" => array( 'alpha2'=>'MC', 'alpha3'=>'MCO', 'num'=>'492', 'isd'=> '377', "name" => "Monaco", "continent" => "Europe"),
        "MN" => array( 'alpha2'=>'MN', 'alpha3'=>'MNG', 'num'=>'496', 'isd'=> '976', "name" => "Mongolia", "continent" => "Asia"),
        "ME" => array( 'alpha2'=>'ME', 'alpha3'=>'MNE', 'num'=>'499', 'isd'=> '382', "name" => "Montenegro", "continent" => "Europe"),
        "MS" => array( 'alpha2'=>'MS', 'alpha3'=>'MSR', 'num'=>'500', 'isd'=> '1664', "name" => "Montserrat", "continent" => "North America"),
        "MA" => array( 'alpha2'=>'MA', 'alpha3'=>'MAR', 'num'=>'504', 'isd'=> '212', "name" => "Morocco", "continent" => "Africa"),
        "MZ" => array( 'alpha2'=>'MZ', 'alpha3'=>'MOZ', 'num'=>'508', 'isd'=> '258', "name" => "Mozambique", "continent" => "Africa"),
        "MM" => array( 'alpha2'=>'MM', 'alpha3'=>'MMR', 'num'=>'104', 'isd'=> '95', "name" => "Myanmar", "continent" => "Asia"),
        "NA" => array( 'alpha2'=>'NA', 'alpha3'=>'NAM', 'num'=>'516', 'isd'=> '264', "name" => "Namibia", "continent" => "Africa"),
        "NR" => array( 'alpha2'=>'NR', 'alpha3'=>'NRU', 'num'=>'520', 'isd'=> '674', "name" => "Nauru", "continent" => "Oceania"),
        "NP" => array( 'alpha2'=>'NP', 'alpha3'=>'NPL', 'num'=>'524', 'isd'=> '977', "name" => "Nepal", "continent" => "Asia"),
        "NL" => array( 'alpha2'=>'NL', 'alpha3'=>'NLD', 'num'=>'528', 'isd'=> '31', "name" => "Netherlands", "continent" => "Europe"),
        "AN" => array( 'alpha2'=>'AN', 'alpha3'=>'ANT', 'num'=>'530', 'isd'=> '599', "name" => "Netherlands Antilles", "continent" => "North America"),
        "NC" => array( 'alpha2'=>'NC', 'alpha3'=>'NCL', 'num'=>'540', 'isd'=> '687', "name" => "New Caledonia", "continent" => "Oceania"),
        "NZ" => array( 'alpha2'=>'NZ', 'alpha3'=>'NZL', 'num'=>'554', 'isd'=> '64', "name" => "New Zealand", "continent" => "Oceania"),
        "NI" => array( 'alpha2'=>'NI', 'alpha3'=>'NIC', 'num'=>'558', 'isd'=> '505', "name" => "Nicaragua", "continent" => "North America"),
        "NE" => array( 'alpha2'=>'NE', 'alpha3'=>'NER', 'num'=>'562', 'isd'=> '227', "name" => "Niger", "continent" => "Africa"),
        "NG" => array( 'alpha2'=>'NG', 'alpha3'=>'NGA', 'num'=>'566', 'isd'=> '234', "name" => "Nigeria", "continent" => "Africa"),
        "NU" => array( 'alpha2'=>'NU', 'alpha3'=>'NIU', 'num'=>'570', 'isd'=> '683', "name" => "Niue", "continent" => "Oceania"),
        "NF" => array( 'alpha2'=>'NF', 'alpha3'=>'NFK', 'num'=>'574', 'isd'=> '672', "name" => "Norfolk Island", "continent" => "Oceania"),
        "MP" => array( 'alpha2'=>'MP', 'alpha3'=>'MNP', 'num'=>'580', 'isd'=> '1670', "name" => "Northern Mariana Islands", "continent" => "Oceania"),
        "NO" => array( 'alpha2'=>'NO', 'alpha3'=>'NOR', 'num'=>'578', 'isd'=> '47', "name" => "Norway", "continent" => "Europe"),
        "OM" => array( 'alpha2'=>'OM', 'alpha3'=>'OMN', 'num'=>'512', 'isd'=> '968', "name" => "Oman", "continent" => "Asia"),
        "PK" => array( 'alpha2'=>'PK', 'alpha3'=>'PAK', 'num'=>'586', 'isd'=> '92', "name" => "Pakistan", "continent" => "Asia"),
        "PW" => array( 'alpha2'=>'PW', 'alpha3'=>'PLW', 'num'=>'585', 'isd'=> '680', "name" => "Palau", "continent" => "Oceania"),
        "PS" => array( 'alpha2'=>'PS', 'alpha3'=>'PSE', 'num'=>'275', 'isd'=> '970', "name" => "Palestinia", "continent" => "Asia"),
        "PA" => array( 'alpha2'=>'PA', 'alpha3'=>'PAN', 'num'=>'591', 'isd'=> '507', "name" => "Panama", "continent" => "North America"),
        "PG" => array( 'alpha2'=>'PG', 'alpha3'=>'PNG', 'num'=>'598', 'isd'=> '675', "name" => "Papua New Guinea", "continent" => "Oceania"),
        "PY" => array( 'alpha2'=>'PY', 'alpha3'=>'PRY', 'num'=>'600', 'isd'=> '595', "name" => "Paraguay", "continent" => "South America"),
        "PE" => array( 'alpha2'=>'PE', 'alpha3'=>'PER', 'num'=>'604', 'isd'=> '51', "name" => "Peru", "continent" => "South America"),
        "PH" => array( 'alpha2'=>'PH', 'alpha3'=>'PHL', 'num'=>'608', 'isd'=> '63', "name" => "Philippines", "continent" => "Asia"),
        "PN" => array( 'alpha2'=>'PN', 'alpha3'=>'PCN', 'num'=>'612', 'isd'=> '870', "name" => "Pitcairn", "continent" => "Oceania"),
        "PL" => array( 'alpha2'=>'PL', 'alpha3'=>'POL', 'num'=>'616', 'isd'=> '48', "name" => "Poland", "continent" => "Europe"),
        "PT" => array( 'alpha2'=>'PT', 'alpha3'=>'PRT', 'num'=>'620', 'isd'=> '351', "name" => "Portugal", "continent" => "Europe"),
        "PR" => array( 'alpha2'=>'PR', 'alpha3'=>'PRI', 'num'=>'630', 'isd'=> '1', "name" => "Puerto Rico", "continent" => "North America"),
        "QA" => array( 'alpha2'=>'QA', 'alpha3'=>'QAT', 'num'=>'634', 'isd'=> '974', "name" => "Qatar", "continent" => "Asia"),
        "RE" => array( 'alpha2'=>'RE', 'alpha3'=>'REU', 'num'=>'638', 'isd'=> '262', "name" => "Reunion", "continent" => "Africa"),
        "RO" => array( 'alpha2'=>'RO', 'alpha3'=>'ROU', 'num'=>'642', 'isd'=> '40', "name" => "Romania", "continent" => "Europe"),
        "RU" => array( 'alpha2'=>'RU', 'alpha3'=>'RUS', 'num'=>'643', 'isd'=> '7', "name" => "Russian Federation", "continent" => "Europe"),
        "RW" => array( 'alpha2'=>'RW', 'alpha3'=>'RWA', 'num'=>'646', 'isd'=> '250', "name" => "Rwanda", "continent" => "Africa"),
        "SH" => array( 'alpha2'=>'SH', 'alpha3'=>'SHN', 'num'=>'654', 'isd'=> '290', "name" => "Saint Helena", "continent" => "Africa"),
        "KN" => array( 'alpha2'=>'KN', 'alpha3'=>'KNA', 'num'=>'659', 'isd'=> '1869', "name" => "Saint Kitts and Nevis", "continent" => "North America"),
        "LC" => array( 'alpha2'=>'LC', 'alpha3'=>'LCA', 'num'=>'662', 'isd'=> '1758', "name" => "Saint Lucia", "continent" => "North America"),
        "PM" => array( 'alpha2'=>'PM', 'alpha3'=>'SPM', 'num'=>'666', 'isd'=> '508', "name" => "Saint Pierre and Miquelon", "continent" => "North America"),
        "VC" => array( 'alpha2'=>'VC', 'alpha3'=>'VCT', 'num'=>'670', 'isd'=> '1784', "name" => "Saint Vincent and The Grenadines", "continent" => "North America"),
        "WS" => array( 'alpha2'=>'WS', 'alpha3'=>'WSM', 'num'=>'882', 'isd'=> '685', "name" => "Samoa", "continent" => "Oceania"),
        "SM" => array( 'alpha2'=>'SM', 'alpha3'=>'SMR', 'num'=>'674', 'isd'=> '378', "name" => "San Marino", "continent" => "Europe"),
        "ST" => array( 'alpha2'=>'ST', 'alpha3'=>'STP', 'num'=>'678', 'isd'=> '239', "name" => "Sao Tome and Principe", "continent" => "Africa"),
        "SA" => array( 'alpha2'=>'SA', 'alpha3'=>'SAU', 'num'=>'682', 'isd'=> '966', "name" => "Saudi Arabia", "continent" => "Asia"),
        "SN" => array( 'alpha2'=>'SN', 'alpha3'=>'SEN', 'num'=>'686', 'isd'=> '221', "name" => "Senegal", "continent" => "Africa"),
        "RS" => array( 'alpha2'=>'RS', 'alpha3'=>'SRB', 'num'=>'688', 'isd'=> '381', "name" => "Serbia", "continent" => "Europe"),
        "SC" => array( 'alpha2'=>'SC', 'alpha3'=>'SYC', 'num'=>'690', 'isd'=> '248', "name" => "Seychelles", "continent" => "Africa"),
        "SL" => array( 'alpha2'=>'SL', 'alpha3'=>'SLE', 'num'=>'694', 'isd'=> '232', "name" => "Sierra Leone", "continent" => "Africa"),
        "SG" => array( 'alpha2'=>'SG', 'alpha3'=>'SGP', 'num'=>'702', 'isd'=> '65', "name" => "Singapore", "continent" => "Asia"),
        "SK" => array( 'alpha2'=>'SK', 'alpha3'=>'SVK', 'num'=>'703', 'isd'=> '421', "name" => "Slovakia", "continent" => "Europe"),
        "SI" => array( 'alpha2'=>'SI', 'alpha3'=>'SVN', 'num'=>'705', 'isd'=> '386', "name" => "Slovenia", "continent" => "Europe"),
        "SB" => array( 'alpha2'=>'SB', 'alpha3'=>'SLB', 'num'=>'090', 'isd'=> '677', "name" => "Solomon Islands", "continent" => "Oceania"),
        "SO" => array( 'alpha2'=>'SO', 'alpha3'=>'SOM', 'num'=>'706', 'isd'=> '252', "name" => "Somalia", "continent" => "Africa"),
        "ZA" => array( 'alpha2'=>'ZA', 'alpha3'=>'ZAF', 'num'=>'729', 'isd'=> '27', "name" => "South Africa", "continent" => "Africa"),
        "SS" => array( 'alpha2'=>'SS', 'alpha3'=>'SSD', 'num'=>'710', 'isd'=> '211', "name" => "South Sudan", "continent" => "Africa" ),
        "GS" => array( 'alpha2'=>'GS', 'alpha3'=>'SGS', 'num'=>'239', 'isd'=> '500', "name" => "South Georgia and The South Sandwich Islands", "continent" => "Antarctica"),
        "ES" => array( 'alpha2'=>'ES', 'alpha3'=>'ESP', 'num'=>'724', 'isd'=> '34', "name" => "Spain", "continent" => "Europe"),
        "LK" => array( 'alpha2'=>'LK', 'alpha3'=>'LKA', 'num'=>'144', 'isd'=> '94', "name" => "Sri Lanka", "continent" => "Asia"),
        "SD" => array( 'alpha2'=>'SD', 'alpha3'=>'SDN', 'num'=>'736', 'isd'=> '249', "name" => "Sudan", "continent" => "Africa"),
        "SR" => array( 'alpha2'=>'SR', 'alpha3'=>'SUR', 'num'=>'740', 'isd'=> '597', "name" => "Suriname", "continent" => "South America"),
        "SJ" => array( 'alpha2'=>'SJ', 'alpha3'=>'SJM', 'num'=>'744', 'isd'=> '47', "name" => "Svalbard and Jan Mayen", "continent" => "Europe"),
        "SZ" => array( 'alpha2'=>'SZ', 'alpha3'=>'SWZ', 'num'=>'748', 'isd'=> '268', "name" => "Swaziland", "continent" => "Africa"),
        "SE" => array( 'alpha2'=>'SE', 'alpha3'=>'SWE', 'num'=>'752', 'isd'=> '46', "name" => "Sweden", "continent" => "Europe"),
        "CH" => array( 'alpha2'=>'CH', 'alpha3'=>'CHE', 'num'=>'756', 'isd'=> '41', "name" => "Switzerland", "continent" => "Europe"),
        "SY" => array( 'alpha2'=>'SY', 'alpha3'=>'SYR', 'num'=>'760', 'isd'=> '963', "name" => "Syrian Arab Republic", "continent" => "Asia"),
        "TW" => array( 'alpha2'=>'TW', 'alpha3'=>'TWN', 'num'=>'158', 'isd'=> '886', "name" => "Taiwan, Province of China", "continent" => "Asia"),
        "TJ" => array( 'alpha2'=>'TJ', 'alpha3'=>'TJK', 'num'=>'762', 'isd'=> '992', "name" => "Tajikistan", "continent" => "Asia"),
        "TZ" => array( 'alpha2'=>'TZ', 'alpha3'=>'TZA', 'num'=>'834', 'isd'=> '255', "name" => "Tanzania, United Republic of", "continent" => "Africa"),
        "TH" => array( 'alpha2'=>'TH', 'alpha3'=>'THA', 'num'=>'764', 'isd'=> '66', "name" => "Thailand", "continent" => "Asia"),
        "TL" => array( 'alpha2'=>'TL', 'alpha3'=>'TLS', 'num'=>'626', 'isd'=> '670', "name" => "Timor-leste", "continent" => "Asia"),
        "TG" => array( 'alpha2'=>'TG', 'alpha3'=>'TGO', 'num'=>'768', 'isd'=> '228', "name" => "Togo", "continent" => "Africa"),
        "TK" => array( 'alpha2'=>'TK', 'alpha3'=>'TKL', 'num'=>'772', 'isd'=> '690', "name" => "Tokelau", "continent" => "Oceania"),
        "TO" => array( 'alpha2'=>'TO', 'alpha3'=>'TON', 'num'=>'776', 'isd'=> '676', "name" => "Tonga", "continent" => "Oceania"),
        "TT" => array( 'alpha2'=>'TT', 'alpha3'=>'TTO', 'num'=>'780', 'isd'=> '1868', "name" => "Trinidad and Tobago", "continent" => "North America"),
        "TN" => array( 'alpha2'=>'TN', 'alpha3'=>'TUN', 'num'=>'788', 'isd'=> '216', "name" => "Tunisia", "continent" => "Africa"),
        "TR" => array( 'alpha2'=>'TR', 'alpha3'=>'TUR', 'num'=>'792', 'isd'=> '90', "name" => "Turkey", "continent" => "Asia"),
        "TM" => array( 'alpha2'=>'TM', 'alpha3'=>'TKM', 'num'=>'795', 'isd'=> '993', "name" => "Turkmenistan", "continent" => "Asia"),
        "TC" => array( 'alpha2'=>'TC', 'alpha3'=>'TCA', 'num'=>'796', 'isd'=> '1649', "name" => "Turks and Caicos Islands", "continent" => "North America"),
        "TV" => array( 'alpha2'=>'TV', 'alpha3'=>'TUV', 'num'=>'798', 'isd'=> '688', "name" => "Tuvalu", "continent" => "Oceania"),
        "UG" => array( 'alpha2'=>'UG', 'alpha3'=>'UGA', 'num'=>'800', 'isd'=> '256', "name" => "Uganda", "continent" => "Africa"),
        "UA" => array( 'alpha2'=>'UA', 'alpha3'=>'UKR', 'num'=>'804', 'isd'=> '380', "name" => "Ukraine", "continent" => "Europe"),
        "AE" => array( 'alpha2'=>'AE', 'alpha3'=>'ARE', 'num'=>'784', 'isd'=> '971', "name" => "United Arab Emirates", "continent" => "Asia"),
        "GB" => array( 'alpha2'=>'GB', 'alpha3'=>'GBR', 'num'=>'826', 'isd'=> '44', "name" => "United Kingdom", "continent" => "Europe"),
        "US" => array( 'alpha2'=>'US', 'alpha3'=>'USA', 'num'=>'840', 'isd'=> '1', "name" => "United States", "continent" => "North America"),
        "UM" => array( 'alpha2'=>'UM', 'alpha3'=>'UMI', 'num'=>'581', 'isd'=> '1', "name" => "United States Minor Outlying Islands", "continent" => "Oceania"),
        "UY" => array( 'alpha2'=>'UY', 'alpha3'=>'URY', 'num'=>'858', 'isd'=> '598', "name" => "Uruguay", "continent" => "South America"),
        "UZ" => array( 'alpha2'=>'UZ', 'alpha3'=>'UZB', 'num'=>'860', 'isd'=> '998', "name" => "Uzbekistan", "continent" => "Asia"),
        "VU" => array( 'alpha2'=>'VU', 'alpha3'=>'VUT', 'num'=>'548', 'isd'=> '678', "name" => "Vanuatu", "continent" => "Oceania"),
        "VE" => array( 'alpha2'=>'VE', 'alpha3'=>'VEN', 'num'=>'862', 'isd'=> '58', "name" => "Venezuela", "continent" => "South America"),
        "VN" => array( 'alpha2'=>'VN', 'alpha3'=>'VNM', 'num'=>'704', 'isd'=> '84', "name" => "Vietnam", "continent" => "Asia"),
        "VG" => array( 'alpha2'=>'VG', 'alpha3'=>'VGB', 'num'=>'092', 'isd'=> '1284', "name" => "Virgin Islands, British", "continent" => "North America"),
        "VI" => array( 'alpha2'=>'VI', 'alpha3'=>'VIR', 'num'=>'850', 'isd'=> '1430', "name" => "Virgin Islands, U.S.", "continent" => "North America"),
        "WF" => array( 'alpha2'=>'WF', 'alpha3'=>'WLF', 'num'=>'876', 'isd'=> '681', "name" => "Wallis and Futuna", "continent" => "Oceania"),
        "EH" => array( 'alpha2'=>'EH', 'alpha3'=>'ESH', 'num'=>'732', 'isd'=> '212', "name" => "Western Sahara", "continent" => "Africa"),
        "YE" => array( 'alpha2'=>'YE', 'alpha3'=>'YEM', 'num'=>'887', 'isd'=> '967', "name" => "Yemen", "continent" => "Asia"),
        "ZM" => array( 'alpha2'=>'ZM', 'alpha3'=>'ZMB', 'num'=>'894', 'isd'=> '260', "name" => "Zambia", "continent" => "Africa"),
        "ZW" => array( 'alpha2'=>'ZW', 'alpha3'=>'ZWE', 'num'=>'716', 'isd'=> '263', "name" => "Zimbabwe", "continent" => "Africa")
    );
  return $countries[$countrycode]["continent"];
}
       
?>