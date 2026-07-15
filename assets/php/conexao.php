<?php

    class Conexao {

        private $host;
        private $usuario;
        private $senha;
        private $dbname;

        public function __construct(){
            $this->host    = getenv('DB_HOST') ?: 'localhost';
            $this->usuario = getenv('DB_USER') ?: 'root';
            $this->senha   = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
            $this->dbname  = getenv('DB_NAME') ?: 'tcc_receitas';
        }

        public function conexao(){
            return new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->usuario,
                $this->senha
            );
        }

    }
