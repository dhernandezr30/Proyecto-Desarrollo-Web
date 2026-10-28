<?php
require_once(__DIR__  .  "/Configuration.php");
require_once(__DIR__  .  "/core/Response.php");

date_default_timezone_set("America/Guatemala");

class Connection extends PDO
{
    public function __construct()
    {
        try {
            $dsn = "mysql:host=" . HOST_DB . ";port=" . PORT_DB . ";dbname=" . DATABASE . ";charset=" . CHARSET;
            parent::__construct($dsn, USER_DB, PASSWORD_DB);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch (PDOException $e) {
            Response::error("No se pudo conectar con la base de datos", -1, 500);
        }
    }
}
