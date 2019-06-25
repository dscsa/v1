<?php


//Generated Emails
$lang['email_password'] = array
(
	'Your new password from SIRUM',
	"<b>Your password has been successfully reset.</b><br>
	<br>
	Because you correctly answered the security question on your account, we have assigned you a new password. Once logged in, change this password to something you will remember.<br>
	<br>
	Login/email ".html::link('login?email={email}&to=password/change', '{email}')."<br>
	Password {0}<br>"
);

$lang['email_welcome'] = array
(
	'Welcome to SIRUM!',
	"<strong>Welcome to the SIRUM network!</strong><br>
	<br>
	Thank you for registering.<br>
	<br>
	Email us to setup an ".html::mail(EMAIL.'?subject=Setup Appointment', 'appointment').", or view our ".html::link('http://sirum.org/about-us/#av_section_5', 'contact info').".
	Below is a summary of your account. Edit your facility's information using the link below:<br>
	<br>
	Organization {0}<br>
	Login/Email  ".html::link('login?email={email}&to=account', '{email}')."<br>"
);

//If you must adapt and approve Policies & Procedures before making your first donation, either the ".html::link('http://link.sirum.org/snf_pp', 'SNF Draft')." or ".html::link('http://link.sirum.org/rcfe_pp', 'RCFE Draft')." can serve as a starting point

$lang['email_approval'] = array
(
	'New Donor Approval!',
	"<strong>A new donor has joined the SIRUM network!</strong><br>
	<br>
	To ensure you receive only trusted donations, you must ".html::link('account/donors', 'approve this donor')." before they will be able to donate to you. Below is a summary of the donor's account:<br>
	<br>
	Organization {0}<br>
	License {1}<br>
	Contact {2}<br>
	Telephone {3}<br>
	Email {4}<br>
	Address {5}<br>"
);

$lang['email_received_items'] = array
(
	'Donation Received',
	"{0}'s ".html::link("login?email={email}&to=donations/{1}", 'donation')." with tracking number {2} to {3} was <strong>received.</strong> Our goal is for donating to be easier than destroying - if you have any questions or suggestions, simply reply to this email or call us at ".PHONE.". Thanks again!"
);

$lang['email_received_no_items'] = array
(
	'Donation Received',
	"{0}'s ".html::link("login?email={email}&to=donations/{1}", 'donation')." with tracking number {2} to {3} was <strong>received.</strong> Your complete ".html::link('login?email={email}&to=record/donated', 'donation record')." will be available online shortly. Our goal is for donating to be easier than destroying - if you have any questions or suggestions, simply reply to this email or call us at ".PHONE.". Thanks again!"
);

$lang['email_shipped'] = array
(
	'Donation Shipped',
	html::link("donations/{1}", "Donation {1}")." with tracking number {2} from {0} was shipped"
);

$lang['email_verified'] = array
(
	'Donation Verified',
	html::link("donations/{1}", "Donation {1}")." with tracking number {2} from {0} was verified"
);

$lang['email_missed_pickup'] = array
(
	"Pickup Missed",
	html::link("donations/{1}", "Donation {1}")." with tracking number {2} from {0} was not picked up. Attempting to reschedule..."
);

$lang['email_individual_donation'] = array
(
	"Your SIRUM Donation Label",
	"Thank you! To complete your donation:<br><br>
	1. Print the attached shipping label and donation manifest<br>
	2. Place the manifest inside the box and tape the shipping label to the outside.<br>
	3. Seal the box and <link>arrange a pickup<link> or drop it off at the nearest FedEx pickup location<br>
	4. Keep this email and attachment for your records<br>"
);

$lang['label_thank_you'] = array
(
  "Hello <donor_name>,",
  "Please accept our thanks for your generous donation on <donation_date>. Your support will go a long way in furthering SIRUM’s ability to save lives by connecting unopened, unexpired medications with people in need.",
	"",
	"Please keep this acknowledgement of your donation for your tax records. SIRUM is a 501(c)3 nonprofit organization with the federal tax ID of 27-1103057. No goods or services were provided in exchange for your generous financial donation. Your donation is tax deductible to the full extent allowed by law.",
	"",
	"Thank you again for your support of SIRUM. If you have any questions related to your contribution, please contact us at 650-488-7434 or by email at gifts@sirum.org.",
	"",
	"Best,",
  "The SIRUM Team"
);
