<?php

class ModularCssJs{

    public string $baseDirectory = '';

    public string $modulesDirectory = '';

    public bool $inProduction = FALSE;

    private array $included = ['css' => [], 'js' => []];
        
    /**
     * __construct
     *
     * @param  mixed $directory where the files will be stored
     * @return void
     */
    public function __construct(string $modulesDirectory, string $outputDirectory, bool $inProduction = FALSE){
        $this->modulesDirectory = $modulesDirectory;
        $this->baseDirectory = $outputDirectory;
        $this->inProduction = $inProduction;

        $this->createDirectory($this->baseDirectory);
        $this->createDefaultFiles($this->baseDirectory);

        if(!is_dir($this->modulesDirectory)){
            throw new Exception('Modules directory does not exist.');
        }
    }

    public function get(string $name, array $toInclude) :array{
        if(empty(trim($name))){
            throw new Exception("Name can't be blank.");
        }
        if(count($toInclude) < 1){
            throw new Exception("Nothing to include.");
        }

        $fileNames = [
            'css'   => $this->getFileName($name, $this->inProduction? 'css': 'dev.css'),
            'js'    => $this->getFileName($name, $this->inProduction? 'js': 'dev.js'),
        ];

        if($this->inProduction && (isset($fileNames['css']) && file_exists($this->getFilePath($fileNames['css'])) || isset($fileNames['js']) && file_exists($this->getFilePath($fileNames['js'])))){
            return $fileNames;
        }
        else{
            return $this->create($fileNames, $toInclude);
        }
    }

    private function create(array $fileNames, array $toInclude) :array{
        /*
        $content = ['css' => '', 'js' => ''];
        $filesToCombine = ['css' => [], 'js' => []];
        foreach ($toInclude as $moduleName) {
            $module = explode('-', $moduleName, 2);
            $baseModule = $module[0].DIRECTORY_SEPARATOR.$module[0];
            
            // for CSS files
            if($this->fileExists($baseModule.'.css') && !in_array($baseModule, $filesToCombine['css'])){
                $filesToCombine['css'][] = $baseModule;
                $content['css'] .= "\n".$this->getFileContent($baseModule.'.css');
            }
            if(isset($module[1]) && $this->fileExists($baseModule.'-'.$module[1].'.css') && !in_array($baseModule.'-'.$module[1], $filesToCombine['css'])){
                $filesToCombine['css'][] = $baseModule.'-'.$module[1];
                $content['css'] .= "\n".$this->getFileContent($baseModule.'-'.$module[1].'.css');
            }
            // for JavaScript files
            if($this->fileExists($baseModule.'.js') && !in_array($baseModule, $filesToCombine['js'])){
                $filesToCombine['js'][] = $baseModule;
                $content['js'] .= "\n".$this->getFileContent($baseModule.'.js');
            }
            if(isset($module[1]) && $this->fileExists($baseModule.'-'.$module[1].'.js') && !in_array($baseModule.'-'.$module[1], $filesToCombine['js'])){
                $filesToCombine['js'][] = $baseModule.'-'.$module[1];
                $content['js'] .= "\n".$this->getFileContent($baseModule.'-'.$module[1].'.js');
            }
        }
        */
        $content = $this->genrateContent($toInclude);


        if(isset($fileNames['css'])){
            $this->write($fileNames['css'], $this->inProduction? $this->minifyCss($content['css']): $content['css']);
        }if(isset($fileNames['js'])){
            $this->write($fileNames['js'], $this->inProduction? $this->minifyJs($content['js']): $content['js']);
        }
        return $fileNames;
    }

    private function genrateContent(array $toInclude, $type = 'all') :array{
        $content = ['css' => '', 'js' => ''];
        
        foreach ($toInclude as $moduleName) {
            $module = explode('-', $moduleName, 2);
            $baseModule = $module[0].DIRECTORY_SEPARATOR.$module[0];
            
            // for CSS files
            if($type == 'all' || $type == 'css'){
                if(isset($module[1]) && $this->fileExists($baseModule.'-'.$module[1].'.css') && !in_array($baseModule.'-'.$module[1], $this->included['css'])){
                    $this->included['css'][] = $baseModule.'-'.$module[1];
                    $content['css'] .= "\n/* START: {$module[0]}-{$module[1]} */\n";
                    $content['css'] .= $this->includeImports($this->getFileContent($baseModule.'-'.$module[1].'.css'), 'css');
                    $content['css'] .= "\n/* END: {$module[0]}-{$module[1]} */\n";
                }
                else if($this->fileExists($baseModule.'.css') && !in_array($baseModule, $this->included['css'])){
                    $this->included['css'][] = $baseModule;
                    $content['css'] .= "\n/* START: {$module[0]} */\n";
                    $content['css'] .= $this->includeImports($this->getFileContent($baseModule.'.css'), 'css');
                    $content['css'] .= "\n/* END: {$module[0]} */\n";
                }
            }
            // for JavaScript files
            if($type == 'all' || $type == 'js'){
                if(isset($module[1]) && $this->fileExists($baseModule.'-'.$module[1].'.js') && !in_array($baseModule.'-'.$module[1], $this->included['js'])){
                    $this->included['js'][] = $baseModule.'-'.$module[1];
                    $content['js'] .= "\n/* START: {$module[0]}-{$module[1]} */\n";
                    $content['js'] .= $this->includeImports($this->getFileContent($baseModule.'-'.$module[1].'.js'), 'js');
                    $content['js'] .= "\n/* END: {$module[0]}-{$module[1]} */\n";
                }
                else if($this->fileExists($baseModule.'.js') && !in_array($baseModule, $this->included['js'])){
                    $this->included['js'][] = $baseModule;
                    $content['js'] .= "\n/* START: {$module[0]} */\n";
                    $content['js'] .= $this->includeImports($this->getFileContent($baseModule.'.js'), 'js');
                    $content['js'] .= "\n/* END: {$module[0]} */\n";
                }
            }
        }

        return $content;
    }

    private function includeImports(string $input, string $type){
        if(trim($input) === "") return $input;
        // "\s*" for multiline and space
        $pattern = "/\/\*\s*{{\s*IMPORT \s*(.*?)\s*}}\s*\*\//"; // old "/\/\*{{IMPORT (.*?)}}*\*\//"
        return preg_replace_callback($pattern, function($m) use($type) {
            return $this->genrateContent(explode(',', rtrim($m[1],",")), $type)[$type];
        }, $input);
    }

    private function getFileContent(string $file) :string{
        return file_get_contents($this->modulesDirectory.DIRECTORY_SEPARATOR.$file);
    }

    private function fileExists(string $file) :bool{
        return file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$file);
    }

    /**
     * Create new file
     * @param string $filename Any string that will be used to access the file in future
     * @param string $content Content
     */
    private function write(string $filename, string $content) :void{
        $filePath  = $this->getFilePath($filename);
        file_put_contents($filePath, $content);
    }

    
    private function getFilePath(string $fileName) :string{
        return $this->baseDirectory . DIRECTORY_SEPARATOR . $fileName;
    }

    private function getFileName(mixed $name, string $extension) :string{
        if(is_array($name)){
            $name = hash('sha1', implode("", $name));
        }
        return "{$name}.{$extension}";
    }

    /**
     * Create directory if doesn't exists
     * @param string $directory
     */
    private function createDirectory(string $directory) :void{
        if (!is_dir($directory)) {
            $oldmask = umask(0);
            @mkdir($directory, 0777, true);
            @umask($oldmask);
        }
    }

    private function createDefaultFiles(string $directory) :void{
        if (!file_exists($directory . DIRECTORY_SEPARATOR . "index.html")) {
            $f = @fopen($directory . DIRECTORY_SEPARATOR . "index.html", "a+");
            @fclose($f);
        }
    }

    public function minifyCss(string $input) :string{
        if(trim($input) === "") return $input;
        $find = array(
            // Remove comment(s)
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
            // Remove unused white-space(s)
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/))|\s*+;\s*+(})\s*+|\s*+([*$~^|]?+=|[{};,>~]|\s(?![0-9\.])|!important\b)\s*+|([[(:])\s++|\s++([])])|\s++(:)\s*+(?!(?>[^{}"\']++|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')*+{)|^\s++|\s++\z|(\s)\s+#si',
            // Replace `0(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)` with `0`
            '#(?<=[\s:])(0)(cm|em|ex|in|mm|pc|pt|px|vh|vw|%)#si',
            // Replace `:0 0 0 0` with `:0`
            '#:(0\s+0|0\s+0\s+0\s+0)(?=[;\}]|\!important)#i',
            // Replace `background-position:0` with `background-position:0 0`
            '#(background-position):0(?=[;\}])#si',
            // Replace `0.6` with `.6`, but only when preceded by `:`, `,`, `-` or a white-space
            '#(?<=[\s:,\-])0+\.(\d+)#s',
            // Minify string value
            '#(\/\*(?>.*?\*\/))|(?<!content\:)([\'"])([a-z_][a-z0-9\-_]*?)\2(?=[\s\{\}\];,])#si',
            '#(\/\*(?>.*?\*\/))|(\burl\()([\'"])([^\s]+?)\3(\))#si',
            // Minify HEX color code
            '#(?<=[\s:,\-]\#)([a-f0-6]+)\1([a-f0-6]+)\2([a-f0-6]+)\3#i',
            // Replace `(border|outline):none` with `(border|outline):0`
            '#(?<=[\{;])(border|outline):none(?=[;\}\!])#',
            // Remove empty selector(s)
            '#(\/\*(?>.*?\*\/))|(^|[\{\}])(?:[^\s\{\}]+)\{\}#s'
        );
        $replace = array(
            '$1',
            '$1$2$3$4$5$6$7',
            '$1',
            ':0',
            '$1:0 0',
            '.$1',
            '$1$3',
            '$1$2$4$5',
            '$1$2$3',
            '$1:0',
            '$1$2'
        );
        return preg_replace($find, $replace, $input);
    }

    public function minifyJs(string $input) :string{
        if(trim($input) === "") return $input;
        $find = array(
            // Remove comment(s)
            '#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
            // Remove white-space(s) outside the string and regex
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
            // Remove the last semicolon
            '#;+\}#',
            // Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`
            '#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
            // --ibid. From `foo['bar']` to `foo.bar`
            '#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
        );
        $replace = array(
            '$1',
            '$1$2',
            '}',
            '$1$3',
            '$1.$3'
        );
        return preg_replace($find, $replace, $input);
    }
}
