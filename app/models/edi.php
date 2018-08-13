<?php
class edi extends MY_Model
{

  function create($donation) {
    $pharmacyID    = $donation[0]->org_id;
    $wholesaleID   = 'SIRUM';
    $createdDate   = date('Ymd');
    $createdTime   = date('Hi');
    $fullDate      = date('Ymd-His'); //Seconds are needed because of 500 export max, user might do multiple exports back to back
    $controlNumber = substr($createdDate.$createdTime, 3);
    $spaces        = str_repeat(' ', 10);

    $EDI   = [];
    $EDI[] = ['ISA', '00', $spaces, '00', $spaces, 'ZZ', str_pad($wholesaleID, 15), 'ZZ', str_pad($pharmacyID, 15), substr($createdDate, 2), $createdTime, 'U', '00401', $controlNumber, '0', 'P', '>'];
    $EDI[] = ['GS', 'PR', $wholesaleID, $pharmacyID, $createdDate, $createdTime, $controlNumber, 'X', '004010'];
    $EDI[] = ['ST', '855', $controlNumber];
    $EDI[] = ['BAK', '06', 'AC', $controlNumber, $createdDate];
    //TODO Add N1 segments

    $EDI[] = ['N1', 'SE', '', 91, $wholesaleID];
    $EDI[] = ['N1', 'BY', '', 91, $pharmacyID];

    foreach($donation as $item) {
      $EDI[] = ['PO1', '', $item->donor_qty, 'UN', '', '', 'VN', "S$item->upc", 'N4', $item->upc];
      $EDI[] = ['ACK', 'IA',  $item->donor_qty, 'UN'];
    }

    $EDI[] = ['CTT', count($donation)];
    $EDI[] = ['SE', count($EDI)-1, $controlNumber];
    $EDI[] = ['GE', 1, $controlNumber];
    $EDI[] = ['IEA', 1, $controlNumber];

    $out = '';

    foreach($EDI as $element) {
      $out .= implode('*', $element)."~";
    }

    $filename = "../data/edi/$donation->org_id/EDI-$donation->org_id-$fullDate.txt";

    if (file_put_contents($filename, $out)) {
      chmod($filename, 0777);
      foreach($donation as $item) {
        $this->db->delete('donation_items', "id = $item->id");   //Right now delete the exported inventory. Eventually move it to the Live Inventory Page
      }
    }
  }

}  // END OF CLASS
