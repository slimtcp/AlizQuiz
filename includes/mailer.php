<?php
/**
 * includes/mailer.php
 * Mini-client SMTP pour envoyer des emails via Gmail.
 * Configure tes identifiants dans les constantes ci-dessous.
 * Pour Gmail : utilise un "Mot de passe d'application" (pas ton vrai mdp).
 * Guide : https://myaccount.google.com/apppasswords
 */

define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'lyloudavemin@gmail.com');
define('SMTP_PASS',     'kzzylryohobudjkt');
define('SMTP_FROM',     'lyloudavemin@gmail.com');
define('SMTP_FROM_NAME','AlizQuiz');

/**
 * Envoie un email via SMTP Gmail.
 */
function envoyerEmail(string $destinataire, string $sujet, string $htmlBody): bool
{
    $socket = @stream_socket_client(
        'tcp://' . SMTP_HOST . ':' . SMTP_PORT,
        $errno, $errstr, 10
    );
    if (!$socket) return false;

    $read = function() use ($socket) {
        $r = '';
        while ($line = fgets($socket, 512)) {
            $r .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return $r;
    };
    $send = function(string $cmd) use ($socket) {
        fwrite($socket, $cmd . "\r\n");
    };

    $read(); // Bienvenue
    $send('EHLO localhost');
    $read();
    $send('STARTTLS');
    $read();

    // Upgrade vers TLS
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    $send('EHLO localhost');
    $read();
    $send('AUTH LOGIN');
    $read();
    $send(base64_encode(SMTP_USER));
    $read();
    $send(base64_encode(SMTP_PASS));
    $resp = $read();
    if (strpos($resp, '235') === false) { fclose($socket); return false; }

    $send('MAIL FROM:<' . SMTP_FROM . '>');
    $read();
    $send('RCPT TO:<' . $destinataire . '>');
    $read();
    $send('DATA');
    $read();

    $headers  = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM . '>' . "\r\n";
    $headers .= 'To: ' . $destinataire . "\r\n";
    $headers .= 'Subject: =?UTF-8?B?' . base64_encode($sujet) . '?=' . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

    $send($headers . "\r\n" . $htmlBody . "\r\n.");
    $read();
    $send('QUIT');
    fclose($socket);
    return true;
}

/**
 * Email de bienvenue envoyé à l'inscription.
 */
function emailBienvenue(string $email, string $pseudo): bool
{
    $html = '
    <div style="font-family:Inter,sans-serif;max-width:560px;margin:0 auto;background:#0A0E14;color:#EAEEF6;border-radius:16px;overflow:hidden;">
        <div style="background:#3D7CFF;padding:32px;text-align:center;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L4 5.5V11C4 16.5 7.5 20.5 12 22C16.5 20.5 20 16.5 20 11V5.5L12 2Z" stroke="white" stroke-width="1.6" stroke-linejoin="round"/>
                <path d="M9 12L11 14L15.5 9.5" stroke="#34D399" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1 style="margin:12px 0 0;font-size:1.8rem;color:#fff;">AlizQuiz</h1>
        </div>
        <div style="padding:36px 32px;">
            <h2 style="color:#6FA1FF;margin-bottom:8px;">Bienvenue, ' . htmlspecialchars($pseudo) . ' ! 🎉</h2>
            <p style="color:#8C97AC;line-height:1.7;margin-bottom:24px;">
                Ton compte AlizQuiz est créé. Tu peux maintenant commencer ton parcours en cybersécurité,
                débloquer des niveaux, gagner des badges et grimper dans le classement.
            </p>
            <div style="background:#11161F;border-radius:12px;padding:20px;margin-bottom:24px;">
                <p style="margin:0 0 8px;font-weight:600;">Ce qui t\'attend :</p>
                <p style="color:#8C97AC;margin:4px 0;">🛡️ Niveau Débutant — commence maintenant</p>
                <p style="color:#8C97AC;margin:4px 0;">⚡ Niveau Intermédiaire — à débloquer</p>
                <p style="color:#8C97AC;margin:4px 0;">💎 Niveau Expert — pour les meilleurs</p>
                <p style="color:#8C97AC;margin:4px 0;">🏋️ Entraînement — accès libre à tous niveaux</p>
            </div>
            <a href="http://localhost/AlizQuiz/accueil.php"
               style="display:inline-block;background:#3D7CFF;color:#fff;text-decoration:none;padding:14px 28px;border-radius:8px;font-weight:600;">
                Commencer le quiz →
            </a>
        </div>
        <div style="padding:20px 32px;border-top:1px solid #232B3A;text-align:center;color:#5C6478;font-size:0.82rem;">
            © ' . date('Y') . ' AlizQuiz — Projet pédagogique cybersécurité
        </div>
    </div>';

    return envoyerEmail($email, 'Bienvenue sur AlizQuiz ! 🛡️', $html);
}
