<?php

  class Compiler {

    function __construct($version)
    {
      $this->rootPath = __DIR__;
      $this->version = $version;
      $this->docsPath = $this->rootPath . "/../docs/" . $version . "/";
      $this->title = "";
      $this->description = "";
      $this->menus = [];
      $this->pages = [];
    }

    function getTemplate($file) {
      return file_get_contents($this->rootPath . "/" . "templates/" . $file);
    }

    function getHeadTag() {
      $headTagHtml = $this->getTemplate("head.tag.html");
      $headTagHtml = str_replace("{{--title--}}", $this->title, $headTagHtml);
      $headTagHtml = str_replace("{{--description--}}", $this->description, $headTagHtml);
      return $headTagHtml;
    }

    function getNavbar() {
      return $this->getTemplate("navbar.html");
    }

    function getSidebar() {
      $menus = "";
      foreach ($this->menus as $menu) {
        $menus .= '
          <a class="gotoblock" href="/#/'.$menu['url'].'">
            <ion-icon name="'.$menu['icon'].'"></ion-icon> 
            '.$menu['title'].'
        </a>';
      }
      $sidebarHtml = $this->getTemplate("sidebar.html");
      return str_replace("{{--sidebar--}}", $menus, $sidebarHtml);
    }

    function getContent() {
      $dom = "";
      foreach ($this->pages as $key => $page) {
        $dom .= '
          <div class="intro" id="block_'.$page["metadata"]["url"].'">
            '.$page["html"].'
            <div class="editlink" 
              file="'.$page["fileName"].'" 
              version="'.$this->version.'">
            </div>
          </div>
        ';
      }
      return $dom;
    }

    function setTitle($title) {
      $this->title = $title;
      return $this;
    }

    function setDescription($description) {
      $this->description = $description;
      return $this;
    }

    function getMetaDataPattern() {
      return "/<!--METADATA(\D*)!METADATA-->/i";
    }

    function getMetaData($page) {
      preg_match($this->getMetaDataPattern(), $page, $matches);
      return @json_decode($matches[1], true);
    }

    function removeMetaData($data) {
      return preg_replace($this->getMetaDataPattern(), '', $data, 1);
    }
  
    function getMarkdownFiles() {
      return array_diff(
        scandir($this->docsPath, SCANDIR_SORT_ASCENDING),
        array('..', '.')
      );
    }
  
    function getRenderedPagesAndMenuItems() {
      $menus = [];
      $pages = [];
      foreach ($this->getMarkdownFiles() as $file) {
        $fileData = file_get_contents($this->docsPath . $file);
        $metadata = $this->getMetaData($fileData);
        $menus[] = $metadata;
        $pages[] = [
          "html" => (new Parsedown())->text($this->removeMetaData($fileData)),
          "metadata" => $metadata,
          "fileName" => $file
        ];
      }
      $this->menus = $menus;
      $this->pages = $pages;
    }

    function getRenderedSite() {
      return '
        <!DOCTYPE html>
        <html lang="en">
          '.$this->getHeadTag().'
        <body>
        
        <div class="container">
          <div class="header">
            '.$this->getNavbar().'
          </div>
          <div class="app">
            <div class="left">
              '.$this->getSidebar().'
            </div>
            <div class="right">
              '.$this->getContent().'
            </div>
          </div>
        </div>
        
        <script src="/assets/app.js"></script>
        </body>
        </html>
      ';
    }

    function copyAssets() {
      $src = $this->rootPath . "/assets";
      $dest = $this->rootPath . "/../dist/assets/";
      system("cp -r $src $dest");
    }

    function removeDist() {
      $dest = $this->rootPath . "/../dist/";
      system("rm -R $dest");
    }
  
    function compile() {
      $this->removeDist();
      $this->getRenderedPagesAndMenuItems();
      $siteHtml = $this->getRenderedSite();
      mkdir($this->rootPath . "/../dist/");
      file_put_contents("dist/index.html", $siteHtml);
      $this->copyAssets();
    }
  }
