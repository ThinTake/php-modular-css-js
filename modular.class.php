<?php

class MODULAR{

    public ?string $baseDirectory = NULL;

    public ?string $modulesDirectory = NULL;

    public bool $useName = FALSE;
        
    /**
     * __construct
     *
     * @param  mixed $directory where the files will be stored
     * @return void
     */
    public function __construct(string $modulesDirectory, string $outputDirectory, bool $useName = FALSE){
        $this->modulesDirectory = $modulesDirectory;
        $this->baseDirectory = $outputDirectory;
        $this->useName = $useName;

        $this->createDirectory($this->baseDirectory);

        if(!is_dir($this->modulesDirectory)){
            throw new Exception('Modules directory does not exist.');
        }
    }

    public function get(string $name, array $toInclude){
        if(empty(trim($name))){
            throw new Exception("Name can't be blank.");
        }
        if(count($toInclude) < 1){
            throw new Exception("Nothing to include.");
        }

        $filesToCombine = NULL;
        $fileNames = [];
        if($this->useName === TRUE){
            $fileNames['css'] = $name.'.css';
            $fileNames['js'] = $name.'.js';
        }
        else{
            $filesToCombine = ['css' => [], 'js' => []];

            foreach ($toInclude as $moduleName) {
                $module = explode('-', $moduleName, 2);
                $baseModule = $module[0].DIRECTORY_SEPARATOR.$module[0];
                
                // for CSS files
                if(file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'.css') && !in_array($baseModule, $filesToCombine['css'])){
                    $filesToCombine['css'][] = $baseModule;
                }
                if(isset($module[1]) && file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'-'.$module[1].'.css') && !in_array($baseModule.'-'.$module[1], $filesToCombine['css'])){
                    $filesToCombine['css'][] = $baseModule.'-'.$module[1];
                }
                // for JavaScript files
                if(file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'.js') && !in_array($baseModule, $filesToCombine['js'])){
                    $filesToCombine['js'][] = $baseModule;
                }
                if(isset($module[1]) && file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'-'.$module[1].'.js') && !in_array($baseModule.'-'.$module[1], $filesToCombine['js'])){
                    $filesToCombine['js'][] = $baseModule.'-'.$module[1];
                }
            }
            
            if(count($filesToCombine['css']) > 0){
                $fileNames['css'] = $this->getFileNameFromArray($filesToCombine['css'], 'css');
            }if(count($filesToCombine['js']) > 0){
                $fileNames['js'] = $this->getFileNameFromArray($filesToCombine['js'], 'js');
            }
        }

        if(isset($fileNames['css']) && file_exists($this->getFilePath($fileNames['css'])) || isset($fileNames['js']) && file_exists($this->getFilePath($fileNames['js']))){
            return $fileNames;
        }
        else{
            return $this->create($name, $fileNames, $filesToCombine, $toInclude);
        }
    }

    public function create(string $name, array $fileNames, ?array $filesToCombine = NULL, array $toInclude){
        $content = ['css' => '', 'js' => ''];

        if($filesToCombine == NULL){
            $filesToCombine = ['css' => [], 'js' => []];
            foreach ($toInclude as $moduleName) {
                $module = explode('-', $moduleName, 2);
                $baseModule = $module[0].DIRECTORY_SEPARATOR.$module[0];
                
                // for CSS files
                if(file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'.css') && !in_array($baseModule, $filesToCombine['css'])){
                    $filesToCombine['css'][] = $baseModule;
                    $content['css'] .= "\n".file_get_contents($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'.css');
                }
                if(isset($module[1]) && file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'-'.$module[1].'.css') && !in_array($baseModule.'-'.$module[1], $filesToCombine['css'])){
                    $filesToCombine['css'][] = $baseModule.'-'.$module[1];
                    $content['css'] .= "\n".file_get_contents($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'-'.$module[1].'.css');
                }
                // for JavaScript files
                if(file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'.js') && !in_array($baseModule, $filesToCombine['js'])){
                    $filesToCombine['js'][] = $baseModule;
                    $content['js'] .= "\n".file_get_contents($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'.js');
                }
                if(isset($module[1]) && file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'-'.$module[1].'.js') && !in_array($baseModule.'-'.$module[1], $filesToCombine['js'])){
                    $filesToCombine['js'][] = $baseModule.'-'.$module[1];
                    $content['js'] .= "\n".file_get_contents($this->modulesDirectory.DIRECTORY_SEPARATOR.$baseModule.'-'.$module[1].'.js');
                }
            }
        }
        else{
            foreach ($filesToCombine as $type => $modules) {
                foreach ($modules as $moduleName) {                
                    // for CSS files
                    if(file_exists($this->modulesDirectory.DIRECTORY_SEPARATOR.$moduleName.'.'.$type)){
                        $content[$type] .= "\n".file_get_contents($this->modulesDirectory.DIRECTORY_SEPARATOR.$moduleName.'.'.$type);
                    }
                }
            }
        }

        if(isset($fileNames['css'])){
            $this->write($fileNames['css'], $content['css']);
        }if(isset($fileNames['js'])){
            $this->write($fileNames['js'], $content['js']);
        }
        return $fileNames;
    }

    /**
     * Create new file
     * @param string $filename Any string that will be used to access the file in future
     * @param string $content Content
     */
    public function write(string $filename, string $content) :void
    {
        $filePath  = $this->getFilePath($filename);
        file_put_contents($filePath, $content);
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

    /**
     * Get full path of file
     * @param string $fileName String that was used while creating file
     * @return string
     */
    public function getFilePath(string $fileName) :string{
        return $this->baseDirectory . DIRECTORY_SEPARATOR . $fileName;
    }

    public function getFileNameFromArray(array $array, string $extension) :string{
        return hash('sha1', implode("_", $array)).'.'.$extension;
    }
}
