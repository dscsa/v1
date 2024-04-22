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
	"{0}'s donation with tracking number {2} to {3} was <strong>received.</strong> Our goal is for donating to be easier than destroying - if you have any questions or suggestions, simply reply to this email or call us at ".PHONE.". Thanks again!"
);

$lang['email_received_no_items'] = array
(
	'Donation Received',
	"{0}'s donation with tracking number {2} to {3} was <strong>received.</strong> Our goal is for donating to be easier than destroying - if you have any questions or suggestions, simply reply to this email or call us at ".PHONE.". Thanks again!" //Your complete ".html::link('login?email={email}&to=record/donated', 'donation record')." will be available online shortly.
);

$lang['email_shipped'] = array
(
	'Donation Shipped',
	"Donation {1} with tracking number {2} from {0} was shipped"
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

$lang['email_individual_donation_success'] = array
(
	"Your SIRUM Donation Label",
	"Thank you {0}! To complete your donation:<br><br>
	1. Print the attached shipping label and donation manifest<br>
	2. Place the manifest inside the box and tape the shipping label to the outside.<br>
	3. Seal the box and ".html::link('https://local.fedex.com/{2}/{1}/','drop it off at the nearest FedEx pickup location')."<br>
	4. Keep this email and two attachments for your records<br><br>
	If any of your medications are not eligible for donation, please dispose of them properly. Please drop them off at a ".html::link('https://apps2.deadiversion.usdoj.gov/pubdispsearch/spring/main?execution=e1s1','local collection site').".
  If there is not a convenient collection site and the medications are not controlled substances, you can also dispose of them yourself: simply cross off your information from the prescription bottle, mix the medicine with something unappetizing (such as coffee grounds or kitty litter), and place it into your household trash.<br>
	".html::link('https://www.fda.gov/drugs/safe-disposal-medicines/disposal-unused-medicines-what-you-should-know','Learn more about proper disposal from the FDA')."<br>"
);

$lang['email_individual_donation_failure'] = array
(
	"Your SIRUM Donation Request Is Processing",
	"Thank you for submitting a request to donate your unused medications!<br><br>
	A SIRUM team member will reach out to you within one business day with your shipping label and information<br>"
);
