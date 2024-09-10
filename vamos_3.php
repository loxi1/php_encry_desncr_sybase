<?php
require 'vendor/autoload.php';

use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
final class CryptoHelper
{
    // Cargar las variables de entorno    

    private $user = "";
    private $dsn = "";
    private $password = "";
    
    private $ODBCConnection = null;
    private $php_metodo = "";
    private $php_secre_key = "";
    private $php_secre_iv = "";

    private $keey = "";
    private $iiv = "";
    private $iiv_ = "";
    private $keey_ = "";
    private $valor_ = false;
    private $openssl_raw_data = 0; //OPENSSL_RAW_DATA==1 ò 0
    public function __construct()
    {
        $this->user = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PWD'];
        $this->dsn = $_ENV['DB_DNS'];
        $this->php_metodo = $_ENV['ENCR_METHOD'];
        $this->php_secre_key = $_ENV['ENCR_SECRET_KEY'];
        $this->php_secre_iv = $_ENV['ENCR_SECRET_IV'];

        $this->keey = hash('sha256', $this->php_secre_key, $this->valor_);
        $this->iiv = substr(hash('sha256', $this->php_secre_iv, $this->valor_), 0, 16);

        $this->iiv_ = substr(hash('sha256', $this->php_secre_iv, true), 0, 16);
        $this->keey_ = hash('sha256', $this->php_secre_key, true);
    }
/**
     * Encripta una cadena de texto.
     *
     * @param string $string Cadena de texto a encriptar.
     * @return string Texto encriptado en formato Base64.
     */
    public function encryption($string)
    {
        $output = openssl_encrypt($string, $this->php_metodo, $this->keey, $this->openssl_raw_data, $this->iiv);
        $output = base64_encode($output);

        return $output;
    }

    public function encriptar($string) {        
        $output = openssl_encrypt($string, $this->php_metodo, $this->keey_, true, $this->iiv_);
        $output = base64_encode($output);

        return $output;
    }

    public function  imprimirvalor() {
        return $this->iiv;
    }

    /**
     * Desencripta una cadena de texto en Base64.
     *
     * @param string $string Cadena de texto en Base64 a desencriptar.
     * @return string Texto desencriptado.
     */
    public function decryption($string)
    {
        $output = openssl_decrypt(base64_decode($string), $this->php_metodo, $this->keey, $this->openssl_raw_data, $this->iiv);
        return $output;
    }

    public function desencriptar($string)
    {
        $output = openssl_decrypt(base64_decode($string), $this->php_metodo, $this->keey_, true, $this->iiv_);
        return $output;
    }


    function getOpen() {        
        return $this->openssl_raw_data;
    }

    function getValor() {
        return ($this->valor_) ? "true" : "false";
    }

    public function buscar_valores() {
        $SQLQuery = "SELECT identificador, codigo, datos, empresa, estado,clave,clave1 FROM usuario_timbrado";
        return odbc_exec($this->ODBCConnection, $SQLQuery);
    }

    public function update($sql) {
        return odbc_exec($this->ODBCConnection, $sql) ? true : false;
    }

    public function conexion() {
        $error = "Error";
        $this->ODBCConnection = odbc_connect($this->dsn, $this->user, $this->password);
        if (!$this->ODBCConnection) {
            $error = "Error de conexión: " . odbc_errormsg();
        }        
        return $error;
    }

    public function cerrar_conexion() {
        odbc_close($this->ODBCConnection);
    }
}

try {
    $crypto = new CryptoHelper();
    $crypto->conexion();
    $RecordSet = $crypto->buscar_valores();
    $cla = $crypto->encryption("1234");
    echo "<pre>";print_r($cla);echo "</pre>";
    if($RecordSet!=null) {
        echo "<table border='1'>";
        echo "<tr><th>Identificador</th><th>Código</th><th>Datos</th><th>Empresa</th><th>Estado</th><th>Clave</th><th>OLD</th><th>Encry</th><th>Decry</th><th>sql</th></tr>";
        //echo "<tr><th>Identificador</th></tr>";
        while ($row = odbc_fetch_array($RecordSet)) {
            $clave = "";
            $encri = "";
            $decry = "";
            $sql = "";         
            if(!empty($row['clave'])) {
                $clave = $crypto->desencriptar($row['clave']);
                $encri = $crypto->encriptar($clave);
                $decry = $crypto->desencriptar($encri);
                $sql = "update usuario_timbrado set clave='".$encri."' where identificador=".$row['identificador'];
                //$crypto->update($sql);                
                echo "<tr>";
                echo "<td>" . $row['identificador'] . "</td>";
                echo "<td>" . $row['codigo'] . "</td>";
                echo "<td>" . utf8_encode($row['datos']) . "</td>";
                echo "<td>" . $row['empresa'] . "</td>";
                echo "<td>" . $row['estado'] . "</td>";
                echo "<td>" . $clave . "</td>";
                echo "<td>" . $row['clave'] . "</td>";
                echo "<td>" . $encri . "</td>";
                echo "<td>" . $decry . "</td>";
                echo "<td>" . $sql . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    }
    $crypto->cerrar_conexion();

} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
