<?php

  require_once __DIR__ . "/vendor/autoload.php";
  require_once __DIR__ . "/Compiler.php";

  $compiler = new Compiler('2.0');

  $compiler->compile();