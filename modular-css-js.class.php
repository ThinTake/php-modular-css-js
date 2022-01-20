<?php

class ModularCssJs{

    public string $baseDirectory = '';

    public string $modulesDirectory = '';

    public bool $inProduction = FALSE;
        
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

        $fileNames = [];
        if($this->inProduction){
            $fileNames['css'] = $this->getFileName($name, 'css');
            $fileNames['js'] = $this->getFileName($name, 'js');
        }
        else{
            $fileNames['css'] = $this->getFileName($toInclude, 'dev.css');
            $fileNames['js'] = $this->getFileName($toInclude, 'dev.js');
        }

        if(isset($fileNames['css']) && file_exists($this->getFilePath($fileNames['css'])) || isset($fileNames['js']) && file_exists($this->getFilePath($fileNames['js']))){
            return $fileNames;
        }
        else{
            return $this->create($name, $fileNames, $toInclude);
        }
    }

    private function create(string $name, array $fileNames, array $toInclude) :array{
        /**
         * import {{IMPORT imp1,imp2}}
         */

        $content = ['css' => '', 'js' => ''];
        $filesToCombine = ['css' => [], 'js' => []];
        foreach ($toInclude as $moduleName) {
            $module = explode('-', $moduleName, 2);
            $baseModule = $module[0].DIRECTORY_SEPARATOR.$module[0];
            
            // for CSS files
            if($this->fileExists($baseModule.'.css') && !in_array($baseModule, $filesToCombine['css'])){
                $filesToCombine['css'][] = $baseModule;
                $content['css'] .= "\n".$this->getContent($baseModule.'.css');
            }
            if(isset($module[1]) && $this->fileExists($baseModule.'-'.$module[1].'.css') && !in_array($baseModule.'-'.$module[1], $filesToCombine['css'])){
                $filesToCombine['css'][] = $baseModule.'-'.$module[1];
                $content['css'] .= "\n".$this->getContent($baseModule.'-'.$module[1].'.css');
            }
            // for JavaScript files
            if($this->fileExists($baseModule.'.js') && !in_array($baseModule, $filesToCombine['js'])){
                $filesToCombine['js'][] = $baseModule;
                $content['js'] .= "\n".$this->getContent($baseModule.'.js');
            }
            if(isset($module[1]) && $this->fileExists($baseModule.'-'.$module[1].'.js') && !in_array($baseModule.'-'.$module[1], $filesToCombine['js'])){
                $filesToCombine['js'][] = $baseModule.'-'.$module[1];
                $content['js'] .= "\n".$this->getContent($baseModule.'-'.$module[1].'.js');
            }
        }

        if(isset($fileNames['css'])){
            $this->write($fileNames['css'], $this->inProduction? $this->minifyCss($content['css']): $content['css']);
        }if(isset($fileNames['js'])){
            $this->write($fileNames['js'], $this->inProduction? $this->minifyJs($content['js']): $content['js']);
        }
        return $fileNames;
    }

    private function getContent(string $file) :string{
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
