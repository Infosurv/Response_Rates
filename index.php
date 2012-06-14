<?php
session_start();
include_once('config.php');

if (isset($_POST['logout'])) {
  unset($_SESSION['login']);
}
if (!isset($_POST['access']) && (isset($_SESSION['login']) || ($_POST['username'] == $username && $_POST['password'] == $password))):
  $_SESSION['login'] = true; 
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Response Rates</title>
  <style>
body {
  font-family: Georgia;
  padding: 10px;
}
fieldset {
  border: 0;
}
fieldset input {
  margin-left: 15px;
}
div {
  float:right;
}
  </style>
</head>
<body>
  <form action="" method="post">
    <div><input type="submit" name="logout" value="Logout" /></div>
    <fieldset>
      <legend>Which survey do you wish to see?</legend>
      <input type="radio" name="survey" value="parent" id="parent"><label for="parent">Parent</label><br>
      <input type="radio" name="survey" value="principal" id="prin"><label for="prin">Principal</label><br>
      <input type="radio" name="survey" value="studentlementaryProper" id="sep"><label for="sep">Student - Elementary Proper</label><br>
      <input type="radio" name="survey" value="studentElementaryCorrections" id="sec"><label for="sec">Student - Elementary Corrections</label><br>
      <input type="radio" name="survey" value="studentSecondary" id="ss"><label for="ss">Student - Secondary</label><br>
      <input type="radio" name="survey" value="teacher" id="teacher"><label for="teacher">Teacher</label><br>
    </fieldset>
    <input type="submit" name="access" value="See Response Rates" />
  </form>
</body>
</html>
<?php elseif (isset($_POST['access']) && isset($_SESSION['login'])):
  include_once('vovici.api.class.php');
  $s        = $_POST['survey'];
  $id       = $projects[$s];
  $location = $locations[$s];
  $total    = $totals[$s];
  $title    = $titles[$s];
  $overall  = $overalls[$s];
  $vovici   = new voviciAPI($api_user, $api_pass, $api_url);
  $data     = $vovici->getCompleteArray($id, null, null, $location);
  
  $total_completes = count($data);
  
  $offices = array();
  foreach ($data as $record) {
    if (!isset($offices[$record])) {
      $offices[$record] = 0;
    }
    $offices[$record]++;
  }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Response Rates: <?php echo $title; ?></title>
  <style>
body {
  font-family: Georgia;
  padding: 10px;
}
table {
  border-collapse: collapse;
  border: 1px solid #999;
}
table tr:nth-child(even) {
  background: #eee;
}
table th,
table td {
  border: 1px solid #999;
}
table td {
  text-align: center;
}
table thead th {
  padding: 5px;
  border: 2px solid #333;
}
table tfoot tr {
  border-top: 2px solid #333;
}
table tfoot th {
  border-right: 2px solid #333;
}
table tfoot th,
table tfoot td {
  padding: 5px;
  border: 2px solid #333;
}
table tbody th {
  border-right: 2px solid #333;
}
a {
  font-size: .7em;
  margin-left: -5px; 
}
  </style>
</head>
<body>
  <a href=""><- Go Back</a>
  <h1>Response Rates: <?php echo $title; ?></h1>

  <table>
    <thead>
      <tr>
        <th>Office</th>
        <th>Completes</th>
        <th># Invited</th>
        <th></th>
        <th>Response Rate</th>
      </tr>
    </thead>
    <tbody>   
<?php foreach ($total as $office => $count): ?>
      <tr>
        <th><?php echo $office; ?></th>
        <td><?php echo isset($offices[$office]) ? $offices[$office] : 0; ?></td>
        <td><?php echo $count; ?></td>
        <td style="width:200px;">
          <div style="width:100%;">
            <div style="width:<?php echo number_format((isset($offices[$office]) ? $offices[$office] : 0)/$count,4)*100; ?>%; background:#58bc80;height:1em;"></div>
          </div>
        </td>
        <td><?php echo number_format((isset($offices[$office]) ? $offices[$office] : 0)/$count,4)*100; ?>%</td>
      </tr>
<?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th>Overall Total</th>
        <td><?php echo $total_completes;?></td>
        <td><?php echo $overall; ?></td>
        <td style="width:200px;">
          <div style="width:100%;">
            <div style="width:<?php echo ($overall > 0) ? number_format($total_completes/$overall, 4)*100 : "0"; ?>%;background:#6284c4;height:1em;"></div>
          </div>
        </td>
        <td><?php echo ($overall > 0) ? number_format($total_completes/$overall, 4)*100 : "-"; ?>%</td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Response Rates</title>
  <style>
body {
  font-family: Georgia;
  padding: 10px;
}
fieldset {
  border: 0;
}
fieldset label,
fieldset input {
  display:block;
}
  </style>
</head>
<body>
  <form action="" method="post">
    <fieldset>
      <label for="username">Username:</label>
      <input type="text" name="username" id="username">
      
      <label for="password">Username:</label>
      <input type="password" name="password" id="password">
    </fieldset>
    <input type="submit" name="login" value="Login" />
  </form>
</body>
</html>
<?php endif; ?>