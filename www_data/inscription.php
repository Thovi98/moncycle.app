<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "password.php";
require_once 'phpmailer/src/Exception.php';
require_once 'phpmailer/src/PHPMailer.php';
require_once 'phpmailer/src/SMTP.php';

session_start();
$cookieLifetime = 365 * 24 * 60 * 60; // A year in seconds
setcookie(session_name(),session_id(),time()+$cookieLifetime);

if (isset($_SESSION["connected"]) && $_SESSION["connected"]) {
	header('Location: /');
	exit;
}

$output = "";

try {

	$db = new PDO('mysql:host=nas_ovpn;dbname=dev_moncyle_app_nas', 'jean_dev', DB_PASSWORD);

	if (isset($_POST["prenom"]) && isset($_POST["email1"]) && isset($_POST["age"]) && filter_var($_POST["email1"], FILTER_VALIDATE_EMAIL)) {
		sleep(5);

		$sql = "select count(no_compte)>0 as compte_existe from compte where email1 like :email1";
		$statement = $db->prepare($sql);
		$statement->bindValue(":email1", $_POST["email1"], PDO::PARAM_STR);
		$statement->execute();

		$compte_existe = boolval($statement->fetchAll(PDO::FETCH_ASSOC)[0]["compte_existe"]);

		if (!$compte_existe) {

			$pass_text = bin2hex(random_bytes(8));
			$pass_hash = password_hash($pass_text, PASSWORD_BCRYPT);

			$sql = "INSERT INTO compte (nom, age, email1, motdepasse) VALUES (:nom, :age, :email1, :motdepasse)";

			$statement = $db->prepare($sql);
			$statement->bindValue(":nom", $_POST["prenom"], PDO::PARAM_STR);
			$statement->bindValue(":age", $_POST["age"], PDO::PARAM_INT);
			$statement->bindValue(":email1", $_POST["email1"], PDO::PARAM_STR);
			$statement->bindValue(":motdepasse", $pass_hash, PDO::PARAM_STR);
			$statement->execute();

			//return $statement->fetchAll(PDO::FETCH_ASSOC);
			
			$mail = new PHPMailer(true);

			//$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
			$mail->isSMTP();                                            //Send using SMTP
			$mail->Host       = 'ssl0.ovh.net';                     //Set the SMTP server to send through
			$mail->SMTPAuth   = true;                                   //Enable SMTP authentication
			$mail->Username   = 'robot@thjn.fr';                     //SMTP username
			$mail->Password   = SMTP_PASSWORD;                               //SMTP password
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
			$mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

			//Recipients
			$mail->setFrom('robot@thjn.fr', 'MONCYCLE.APP');
			$mail->addAddress($_POST["email1"], $_POST["prenom"]);     //Add a recipient
			//$mail->addReplyTo('info@example.com', 'Information');

			//Content
			$mail->isHTML(true);                                  //Set email format to HTML
			$mail->Subject = 'mot de passe MONCYCLE.APP';
			$mail->Body    = 'Votre mot de passe: ' . $pass_text;
			$mail->AltBody = 'Votre mot de passe: ' . $pass_text;

			$mail->send();
		}
		else {
			$output .= "Un compte existe déja pour cette addresse mail.";
		}
	
	}

}
catch (Exception $e){
	
	echo $e->getMessage();

}


?><!doctype html>
<html lang="fr">
	<head>
		<meta charset="utf-8" />
		<meta name="mobile-web-app-capable" content="yes" />
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
		<meta name="apple-mobile-web-app-title" content="Bill" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<link rel="apple-touch-icon" href="/img/bill512.jpg" />
		<meta name="theme-color" media="(prefers-color-scheme: light)" content="white" />
		<meta name="theme-color" media="(prefers-color-scheme: dark)" content="black" />
		<meta name="apple-mobile-web-app-status-bar-style" media="(prefers-color-scheme: light)" content="light-content" />
		<meta name="apple-mobile-web-app-status-bar-style" media="(prefers-color-scheme: dark)" content="dark-content" />
		<title>moncycle.app</title>
		<script src="jquery.min.js"></script> 
		<link rel="stylesheet" href="css/commun.css" />
		<link rel="stylesheet" href="css/compte.css" />
	</head>

	<body>
		<center>
			<h1>mon<span class="gradiant_logo">cycle</span>.app</h1>
			<a href="/"><button type="button" class="nav_button">Revenir à la page de connexion</button></a>
		</center>

		<div class="contennu" id="timeline">
			<h2>Créer votre comte</h2>
			<span class="rouge"><?= $output ?></span>
			<form method="post"><br />
			<label for="i_prenom">Prénoms:</label><br />
			<input name="prenom" type="text" id="i_prenom" required placeholder='ex: "Alice et Bob" ou "Charlotte"' value="<?= $_POST['prenom'] ?>" /><br />
			<br />
			<label for="i_email1">E-mail:</label><br />
			<input name="email1" id="i_email1" type="email" required placeholder="Votre adresse mail."  value="<?= $_POST['email1'] ?>" /><br />
			<br />
			<label for="i_anaissance">Année de naissance:</label><br />
			<select name="age" id="i_anaissance" required>
			<option label=" "></option>
			<?php for ($i = date('Y')-(date('Y')%5)-75; $i < date('Y')-5; $i += 5) { ?>
				<option value="<?= $i ?>">entre <?= $i ?> et <?= $i+4 ?></option>	
			<?php } ?>
			</select><br />
			<p>&#x1F1EB;&#x1F1F7; Ici on ne vend pas vos données. Elles sont hébergées en France et elles soumisent à la règlementation européenne.</p>
			<br />
			<input type="submit" value="Créer mon compte &#x1F942;&#x1F37E;" /></form>
			<br /><br /><?= $output ?><br />
		</div>


	</body>
</html>

