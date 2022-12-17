<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta http-equiv="Content-Style-Type" content="text/css">
  <style type="text/css">
    a { text-decoration: none; color: #1088d1 }
    a:hover { text-decoration: underline; color: #1088d1 }
    img { border:0px; }
</style>
</head>
<body width="100%" style="background-color: #545454; line-height: 20.0px; font-family:Lucida Sans">
<table width="700.0"  style="padding:10px 30px;" cellspacing="0" cellpadding="0">
  <tbody>
    <tr>
      <td>

        <table style="margin:0px; padding:0px; background-color:#FFFFFF;" width="700.0" cellspacing="0" cellpadding="0">
          <tbody>
            <tr>
              <td style="padding:10px 0px 5px 15px; line-height: 20.0px; font-size: 15.0px;">Hi <?= $user; ?>,</td>
              <td align="right" style="width:181px; padding:10px 15px 5px 0px;">
						<?= html::image('SIRUM_Logo2.png', 'height:25px'); ?>
				  </td>
            </tr>
            <tr>
              <td style="padding: 0px 15px; font-size:13px;" colspan="2" valign="top"><?= $body; ?></td>
            </tr>
            <tr>
              <td style="padding: 10px 15px 15px 15px; font-size:13px;">Saving Medicine : Saving Lives<br><strong>The SIRUM Team</strong></td>
              <td></td>
            </tr>
          </tbody>
        </table>

        <table style="margin:0px; padding:0px;" width="700.0" cellspacing="0" cellpadding="0">
          <tbody>
            <tr>
              <td style="line-height:12px; font:9px Geneva; color:#999999; padding:5px 0px;">© <?= date('Y'); ?> SIRUM, a 501(c)3 non-profit born at Stanford University</td>
            </tr>
          </tbody>
        </table>

      </td>
    </tr>
  </tbody>
</table>
</body>
</html>
