<?php
/* moncycle.app
**
** licence Creative Commons CC BY-NC-SA
**
** https://www.moncycle.app
** https://github.com/jean-io/moncycle.app
*/

require_once "config.php";
require_once "lib/db.php";
require_once "lib/date.php";

session_start();

$output = "";

try {

	if (isset($_REQUEST["deconnexion_svp"])) {
		$_SESSION["connected"] = false;
		session_unset();
		session_destroy();
		session_write_close();
		setcookie(session_name(),'',0,'/');
		session_regenerate_id(true);
		header('Location: /');
		exit;
	}

	if (isset($_SESSION["connected"]) && $_SESSION["connected"]) {
		header('Location: /');
		exit;
	}


	if (isset($_POST["email1"]) && isset($_POST["mdp"]) && filter_var($_POST["email1"], FILTER_VALIDATE_EMAIL)) {

		$db = db_open();

		$compte = db_select_compte_par_mail($db, $_POST["email1"])[0] ?? [];

		if (isset($compte["nb_co_echoue"]) && intval($compte["nb_co_echoue"])>=5) sleep(5);
		elseif (!isset($compte["nb_co_echoue"]) && rand(0,5)==0) sleep(5);

		if (!CONNEXION_COMPTE) $output .= "Les connexions aux comptes sont désactivées. Veuillez nous excuser pour ce désagrément.";
		elseif (empty($_POST["email1"]) || empty($_POST["mdp"])) {
			$output .= "E-mail et mot de passe manquant.";
		}
		elseif (isset($compte["actif"]) && !boolval($compte["actif"])) {
			$output .= "Compte désactivé. Contactez l'administrateur.";
		}		
		elseif (isset($compte["motdepasse"]) && password_verify($_POST["mdp"], $compte["motdepasse"])) {
			$output .= "Connecté!";
		
			unset($compte["motdepasse"]);
			$_SESSION["connected"] = true;
			$_SESSION["compte"] = $compte;
			$_SESSION["no"] = intval($compte["no_compte"] ?? -1);
			$_SESSION["sess_refresh"] = date_sql(new DateTime());

			db_update_compte_connecte($db, $_SESSION["no"]);

			header('Location: /');
			exit;
		}
		else {
			db_update_co_echoue($db, $_POST["email1"]);
			$output .= "Mauvais mot de passe ou compte inexistant.";
		}
	
	}

}
catch (Exception $e){
	
	echo $e->getMessage();

}


?><!doctype html>
<!--
** moncycle.app
**
** licence Creative Commons CC BY-NC-SA
**
** https://www.moncycle.app
** https://github.com/jean-io/moncycle.app
-->
<html lang="fr">
	<head>
		<?= file_get_contents("./vue/head.html") ?>
		<link rel="stylesheet" href="css/commun.css?h=<?= hash_file("sha1", "./css/commun.css") ?>" />
		<link rel="stylesheet" href="css/compte.css?h=<?= hash_file("sha1", "./css/compte.css") ?>" />

		<style>
			.contennu {
				max-width: 300px;
			}
		</style>
	</head>
	<body>
		<center>
			<h1>mon<span class="gradiant_logo">cycle</span>.app</h1>
			<a href="inscription"><button type="button" class="nav_button">Créer un compte</button></a>
			<a href="inscription"><button type="button" class="nav_button">Mot de passe perdu</button></a>
		</center>

		<div class="contennu" id="timeline">
			<h2>Connexion à votre compte</h2>
			<span class="rouge"><?= $output? $output . "<br />" : "" ?></span>
			<form method="post"><br />
			<label for="i_email1">E-mail:</label><br />
			<input name="email1" id="i_email1" type="email" required placeholder="Entrer votre adresse mail"  value="<?= $_REQUEST['email1'] ?? "" ?>" /><br />
			<br />
			<label for="i_mdp">Mot de passe:</label><br />
			<input name="mdp" id="i_mdp" type="password" required placeholder="Entrer votre mot de passe"  value="<?= $_REQUEST['mdp'] ?? "" ?>" /><br />
			<br />
			<input type="submit" value="Connexion &#x1F511;" /></form>
			<br /><br /><br />
			<center><a class="decouverte" href="https://www.moncycle.app">&#x1F60D; découvrir moncycle.app</a></center>
			<center><a class="decouverte" href="/connexion.php?email1=demo@moncycle.app&mdp=demo">&#x1F9D0; visiter la démonstration</a></center>
			<br /><br /><br />
		</div>

		<script>
			window.localStorage.clear();
		</script>
	</body>
</html>

