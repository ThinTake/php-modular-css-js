<?php
/**
 * ModularCssJs - Include only required CSS and JS for each page.
 * @version     0.9
 *
 * @author      Hardik Choudhary (ThinTake)
 * @copyright   2022 Hardik Choudhary
 * @license     MIT License
 * @note        This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 * GitHub       https://github.com/ThinTake/php-modular-css-js
 */

class ModularCssJs{
    /**
     * @var string where the generated files are to be saved
     */
    public string $baseDirectory = '';

    /**
     * @var string modules directory
     */
    public string $modulesDirectory = '';

    /**-------
     * @var bool If this will be true, then the files will be generated only once.
     */
    public bool $inProduction = FALSE;

    /**
     * @var bool Minify generated files
     */
    public bool $minify = FALSE;

    /**
     * @var array Modules to be included in files
     */
    private array $toInclude = [];

    /**
     * @var array|array[] Modules that are included in the files
     */
    private array $included = ['css' => [], 'js' => []];

    private array $toIgnore = [];

    /**
     * @param string $modulesDirectory modules directory
     * @param string $outputDirectory where the generated files are to be saved
     * @param bool $inProduction Set 'true' if the page is completed and don't need to add more modules, If you still need to add more modules set it to 'false'.
     * @throws Exception
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

    /**
     * add new modules to import
     * @param array $toInclude an array of modules to add
     * @return void
     */
    public function add(array $toInclude) :void{
        $this->toInclude = array_unique(array_merge($this->toInclude, $toInclude));
    }

    /**
     * get file's name of included modules
     * @param string $name Name of the file, to access it without regenerating again in the future.
     * @param ?array $toInclude an array of modules to add
     * @return string[] An array containing two file's names, CSS and JS
     * @throws Exception
     */
    public function get(string $name, ?array $toInclude = NULL) :array{
        if(isset($toInclude)){
            $this->toInclude = array_unique(array_merge($this->toInclude, $toInclude));
        }
        if(empty(trim($name))){
            throw new Exception("Name can't be blank.");
        }
        if(count($this->toInclude) < 1){
            throw new Exception("Nothing to include.");
        }

        $fileNames = [
            'css'   => $this->getFileName($name, $this->inProduction? 'css': 'dev.css'),
            'js'    => $this->getFileName($name, $this->inProduction? 'js': 'dev.js'),
        ];

        if($this->inProduction && (file_exists($this->getFilePath($fileNames['css'])) || file_exists($this->getFilePath($fileNames['js'])))){
            if(!file_exists($this->getFilePath($fileNames['css']))){
                unset($fileNames['css']);
            }
            if (!file_exists($this->getFilePath($fileNames['js']))) {
                unset($fileNames['js']);
            }
            return $fileNames;
        }
        else{
            $content = $this->generateContent($this->toInclude);
            
            if(!empty(trim($content['css']))){
                if($this->inProduction){
                    $content['css'] = self::removeComments($content['css']);
                }
                $this->write($fileNames['css'], $this->minify? self::minifyCss($content['css']): $content['css']);
            }
            else{
                unset($fileNames['css']);
            }
            
            if(!empty(trim($content['js']))){
                if($this->inProduction){
                    $content['js'] = self::removeComments($content['js']);
                }
                $this->write($fileNames['js'], $this->minify? self::minifyJs($content['js']): $content['js']);
            }
            else{
                unset($fileNames['js']);
            }

            return $fileNames;
        }
    }
  
    /**
     * getLoaded Get added modules
     *
     * @return void
     */
    public function getAdded(){
        return $this->toInclude;
    }
    
    /**
     * getLoaded Get loaded/imported modules
     *
     * @return void
     */
    public function getLoaded(){
        return $this->included;
    }
    
    /**
     * ignore Modules that need to be skipped (For example: loaded already in another instance)
     *
     * @param array $modules Array of modules. Like: ['button', 'grid']
     * @return void
     */
    public function ignore(array $modules) :void{
        foreach ($modules as $key => $value) {
            $modules[$key] = trim($value);
        }
        $this->toIgnore = array_unique(array_merge($this->toIgnore, $modules));
        return;
    }

    /**
     * generate content for included modules
     * @param array $toInclude an array of modules to add
     * @param $type Type of files to generate content. Expected values: all, css, js
     * @return string[] array of content for css and js files
     */
    private function generateContent(array $toInclude, $type = 'all') :array{
        $content = ['css' => '', 'js' => ''];

        foreach ($toInclude as $module) {
            $module = trim($module);

            if(in_array($module, $this->toIgnore)){
                continue;
            }

            $module = preg_replace('/\//', DIRECTORY_SEPARATOR, $module);

            // for CSS files
            if($type == 'all' || $type == 'css'){
                if( $this->fileExists($module.'.css') && !in_array($module, $this->included['css'])){
                    $this->included['css'][] = $module;
                    $content['css'] .= $this->getModuleContent($module, 'css');
                }
                else if($this->fileExists($module.DIRECTORY_SEPARATOR.$module.'.css') && !in_array($module.DIRECTORY_SEPARATOR.$module, $this->included['css'])){
                    $this->included['css'][] = $module.DIRECTORY_SEPARATOR.$module;
                    $content['css'] .= $this->getModuleContent($module.DIRECTORY_SEPARATOR.$module, 'css');
                }
            }
            // for JavaScript files
            if($type == 'all' || $type == 'js'){
                if( $this->fileExists($module.'.js') && !in_array($module, $this->included['js'])){
                    $this->included['js'][] = $module;
                    $content['js'] .= $this->getModuleContent($module, 'js');
                }
                else if($this->fileExists($module.DIRECTORY_SEPARATOR.$module.'.js') && !in_array($module.DIRECTORY_SEPARATOR.$module, $this->included['js'])){
                    $this->included['js'][] = $module.DIRECTORY_SEPARATOR.$module;
                    $content['js'] .= $this->getModuleContent($module.DIRECTORY_SEPARATOR.$module, 'js');
                }
            }
        }

        return $content;
    }

    private function getModuleContent(string $name, string $type) :string{
        $content = null;
        if(!$this->inProduction && !$this->minify){
            $content .= "\n/* START: {$name} */\n";
        }
        
        $content .= $this->includeImports($this->getFileContent("{$name}.{$type}"), $type);
        
        if(!$this->inProduction && !$this->minify){
            $content .= "\n/* END: {$name} */\n";
        }
        return $content;
    }

    /**
     * find and include imported modules
     * @param string $input content to search for imports
     * @param string $type type of content, css OR js
     * @return string
     */
    private function includeImports(string $input, string $type) :string{
        if(trim($input) === "") return $input;
        
        // "\s*" for multiline and space
        
        // First v: "/\/\*{{IMPORT (.*?)}}*\*\//"
        // Second v: "/\/\*\s*{{\s*IMPORT \s*(.*?)\s*}}\s*\*\//"
        
        // /*! {{ IMPORT: toImport }} */
        $pattern = "#\/\*!?\s*{{\s*IMPORT:\s*(.*?)\s*}}\s*\*\/#s";
        return preg_replace_callback($pattern, function($m) use($type) {
            return $this->generateContent(explode(',', rtrim($m[1],",")), $type)[$type];
        }, $input);
    }

    /**
     * get file's content as string
     * @param string $file file path
     * @return string
     */
    private function getFileContent(string $file) :string{
        return file_get_contents($this->modulesDirectory.DIRECTORY_SEPARATOR.$file);
    }

    /**
     * check if files exists or not
     * @param string $file file path
     * @return bool
     */
    private function fileExists(string $file) :bool{
        return file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$file);
    }

    /**
     * Create new file
     * @param string $filename File name. Any string that will be used to access the file in future
     * @param string $content Content
     */
    private function write(string $filename, string $content) :void{
        $filePath  = $this->getFilePath($filename);
        file_put_contents($filePath, $content);
    }

    /**
     * get file path by name
     * @param string $fileName
     * @return string
     */
    private function getFilePath(string $fileName) :string{
        return $this->baseDirectory . DIRECTORY_SEPARATOR . $fileName;
    }

    /**
     * generate file name from array of strings or string
     * @param mixed $name array or string
     * @param string $extension
     * @return string
     */
    private function getFileName(mixed $name, string $extension) :string{
        if(is_array($name)){
            $name = implode("-", $name);
        }
        // $name = preg_replace('/[^a-z0-9\_\-\.]/i', '_', $name);
        $name = md5($name); // Temporary fix for file name bug
        return "{$name}.{$extension}";
    }

    /**
     * Create directory if it doesn't exist
     * @param string $directory
     */
    private function createDirectory(string $directory) :void{
        if (!is_dir($directory)) {
            $oldmask = umask(0);
            @mkdir($directory, 0777, true);
            @umask($oldmask);
        }
    }

    /**
     * Crete index.html file
     * @param string $directory
     * @return void
     */
    private function createDefaultFiles(string $directory) :void{
        if (!file_exists($directory . DIRECTORY_SEPARATOR . "index.html")) {
            $f = @fopen($directory . DIRECTORY_SEPARATOR . "index.html", "a+");
            @fclose($f);
        }
    }

    /**
     * Enable to disable minification
     * @param bool $enabled
     * @return void
     */
    public function setMinify(bool $enabled = TRUE) :void{
        $this->minify = $enabled;
    }

    /**
     * remove comments from css or js code
     * @param string $input
     * @return string
     */
    public static function removeComments(string $input) :string{
        if(trim($input) === "") return $input;

        /*
        $regex = '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s';
        return preg_replace($regex, '$1', $input );
        */
        
        return $input;
    }

    /**
     * minify css content
     * @param string $input
     * @return string
     */
    public static function minifyCss(string $input) :string{
        if(trim($input) === "") return $input;
        $find = array(
            // Remove comment(s)
            /*
            '#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')|\/\*(?!\!)(?>.*?\*\/)|^\s*|\s*$#s',
            */
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
            /*
            '$1',
            */
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

    /**
     * minify js content
     * @param string $input
     * @return string
     */
    public static function minifyJs(string $input) :string{
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
