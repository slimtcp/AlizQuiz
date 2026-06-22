<?php
/**
 * includes/session_db.php
 * ------------------------------------------------------------
 * Stockage des sessions PHP dans la base de données (table
 * "sessions") au lieu de fichiers sur le disque.
 *
 * Pourquoi ? Sur Railway le système de fichiers est éphémère et
 * peut être réparti sur plusieurs instances : les sessions stockées
 * en fichiers se "perdent" d'une requête à l'autre, ce qui
 * déconnecte l'utilisateur au moindre rafraîchissement (F5).
 * En base, la session est partagée et persistante : on reste
 * connecté, même après un redéploiement.
 * ------------------------------------------------------------
 */

class GestionnaireSessionBdd implements SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open($path, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT data FROM sessions WHERE id = ?');
            $stmt->execute([$id]);
            $data = $stmt->fetchColumn();
            return $data !== false ? (string) $data : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    public function write($id, $data): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO sessions (id, data, last_activity)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)'
            );
            $stmt->execute([$id, $data, time()]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function destroy($id): bool
    {
        try {
            $this->pdo->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
        } catch (Throwable $e) {}
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        try {
            $this->pdo->prepare('DELETE FROM sessions WHERE last_activity < ?')
                ->execute([time() - $maxlifetime]);
        } catch (Throwable $e) {}
        return 0;
    }
}

/**
 * Active le stockage des sessions en base. À appeler AVANT
 * session_start(). Ne s'enregistre qu'une seule fois par requête.
 * Retourne false si $pdo est absent (on retombe alors sur les
 * fichiers, comportement par défaut de PHP).
 */
function activerSessionsBdd(?PDO $pdo): bool
{
    static $faitDansCetteRequete = false;
    if ($faitDansCetteRequete) {
        return true;
    }
    if (!($pdo instanceof PDO)) {
        return false;
    }
    $handler = new GestionnaireSessionBdd($pdo);
    $ok = session_set_save_handler($handler, true);
    $faitDansCetteRequete = $ok;
    return $ok;
}
