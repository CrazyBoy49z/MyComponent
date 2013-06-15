<?php

/**
 * LexiconFile class file for MyComponent extra
 *
 * Copyright 2012-2013 by Bob Ray <http://bobsguides.com>
 * Created on 08-11-2012
 *
 * MyComponent is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * MyComponent is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * MyComponent; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package mycomponent
 */

/**
 * Description
 *
 * Methods for parsing lexicon strings in code files.
 * Handles .php, .js, and chunk, template, and resource files,
 * but not properties files.
 *
 * @package mycomponent
 **/

class LexiconCodeFileFactory {
    public static $type;
    
    public static function getInstance(&$modx, $helpers, $path, $fileName, $lexDir) {
        if (strpos($fileName, '.menus.php') !== false) {
            $type = 'Menu';
        } elseif (strpos($fileName, 'settings.php') !== false) {
            $type = 'Settings';
        } elseif (strpos($fileName, 'properties.') !== false) {
            $type = 'Properties';
        } elseif (strpos($fileName, '.php') !== false) {
            $type = 'Php';
        } elseif (strpos($fileName, '.js') !== false) {
            $type = 'Js';
        } else {
            $type = 'Text';
        }
        $className = $type . 'LexiconCodeFile';
        if ($type == 'Properties' || $type == 'Settings') {
            return new $className($modx, $helpers, $path, $fileName, $lexDir);

        }
            return new LexiconCodeFile($modx, $helpers, $path, $fileName, $lexDir);
    }
}

/**
 * @param $modx modX - $modx object
 * @param $helpers Helpers - $helpers class
 * @param $path string - path to code file
 * @param $fileName string - file name of code file
 * @param $lexDir string - path to lexicon directory (e.g. lexicon/en)
 */

abstract class AbstractLexiconCodeFile {
    /**
     * @var $missing array - array of strings used in code
     * but missing from lex file
     */
    public $missing = array();
    /**

    /**
     * @var $lexdir string - directory of lexicon topic
     * file for this code file
     */
    public $lexDir = '';
    /**
     * @var $lexFileName string - name of lexicon topic file
     */
    public $lexFileName;
    /**
     * @var $lexFiles array - array of lex file strings in the form:
     * fileName => fullPath
     */
    public $lexFiles = array();
    /**
     * @var array
     */
    public $errors = array();
    /** @var array $content - array of lines from this code file */
    public $content = array();

    /** @var int $updateCount - count of strings that have been
     * updated in lexicon file  */
    public $updateCount = 0;
    /** @var $modx modX */

    public $modx = null;
    /** @var helpers Helpers */

    public $helpers = null;
    /** @var $language string - */
    public $language = '';

    /** @var string $code - code from file and all included files */
    public $code = '';

    /** @var array $used - lex strings used in this file */
    public $used = array();

    /** @var array $defined - $_lang array with all strings defined in
     *  all specified lexicon topic files */
    public $defined = array();

    /** @var array $toUpdate - lex entries that don't match those
     * in the lex file and need to be updated */
    public $toUpdate = array();

    /** @var int $squigglesFound - count of squiggles
     * tokens (~~) found */
    public $squigglesFound = 0;

    /** @var $type string - type of file being processed */
    public static $type = '';

    /** @var $pattern string - regex pattern for lex strings
     * in file of this type */
    public $pattern = '';

    /** @var $subPattern string - string identifying lines with lex
     * strings type in files of this type (other lines are skipped) */
    public $subPattern = '';



    function __construct(&$modx, $helpers, $path, $fileName, $lexDir) {

        $this->modx =& $modx;
        $this->helpers = $helpers;
        $this->path = rtrim($path, '/\\');
        $this->fileName = $fileName;
        $this->setLanguage();
        $this->lexDir = rtrim($lexDir, '/\\');
        $this->lexDir = strtolower(str_replace('\\', '/', $this->lexDir));
        $this->init();
    }

    public static function getInstance(&$modx, $helpers, $path, $fileName, $lexDir) {
        $lcf = new LexiconCodeFile($modx, $helpers, $path, $fileName, $lexDir);
        return $lcf;
    }
    /* These must be implemented in child classes */
    abstract public function setLexFiles();

    abstract public function setUsed();


    public function init() {
        $this->setContent();
        $this->setLexFiles();
        $this->setUsed();
        $this->setDefined();
        $this->setMissing();
        if (strpos($this->fileName, '.menus.php') !== false) {
            $this->type = 'menu';
            $this->pattern = '#[\'\"]description[\'\"]\s*=>\s*(\'|\")(.*)\1#';
            $this->subPattern = 'description';

        } elseif (strpos($this->fileName, '.php') !== false) {
            $this->type = 'php';
            $this->pattern = '#modx->lexicon\s*\(\s*(\'|\")(.*)\1\)#';
            $this->subPattern = 'modx->lexicon';
        } elseif (strpos($this->fileName, '.js') !== false) {
            $this->type = 'js';
            $this->pattern = '#_\(\s*(\'|\")(.*)\1\)#';
            $this->subPattern = '_(';
        } else {
            $this->type = 'text';
            $this->pattern = '#(\[\[)!*%([^\?&\]]*)#';
        }

    }

    /* Getters */

    /**
     * Return the full name of this file
     *
     * @return string
     */
    public function  getFileName() {
        return $this->fileName;

    }

    /**
     * Return array of all lexicon strings used in this code file in the form:
     * key => value
     *
     * @return array
     */
    public function getUsed() {
        return $this->used;
    }

    /**
     * Return the array of lex keys used in this file that are
     * missing from any specified lexicon topic files
     * @return array
     */
    public function getMissing() {
        return $this->missing;
    }


    /**
     * Return the array of lexicon strings found in this file in the form:
     * key => value
     *
     * @return array
     */
    public function getDefined() {
        return $this->defined;
    }

    /**
     * Return the array of all lexicon topic files specified for this file in the form:
     * fileName => fullPath
     *
     * This should never be empty
     *
     * @return array
     */
    public function getLexFiles() {
        return $this->lexFiles;
    }

    /**
     * Return true if an error is set, false if not
     *
     * @return bool
     */
    public function hasError() {
        return !empty($this->errors);
    }

    /**
     * Return the array of error messages set here
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Return array of lex strings where the value != the
     * value in the lexicon topic file
     *
     * @return array
     */
    public function getToUpdate() {
        return $this->toUpdate;
    }

    /**
     * Return the number of ~~ tokens found in the code file
     *
     * @return int
     */
    public function getSquiggleCount() {
        return $this->squigglesFound;
    }

    /* Setters */
    /**
     * Return the two-letter primary language code extracted
     * from the languages array in the project config file
     *
     * @param string $language
     */
    public function setLanguage($language = '') {
        if (!empty ($language)) {
            $this->language = $language;
        } else {
            $languages = $this->modx->getOption('languages', $this->helpers->props, array());
            $language = key($languages);
            $this->language = empty($language)
                ? 'en'
                : $language;
        }

    }

    /**
     * Create the array of lines from the code file in $this->content
     *
     * @param string $content - (optional) array of content lines
     */
    public function setContent($content = '') {
        if (empty($content)) {
            $fullPath = $this->path . '/' . $this->fileName;
            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);
            } else {
                $this->setError($this->modx->lexicon('mc_file_not_found' . ' ' . $fullPath));
            }
        }
        $this->content = explode("\n", $content);

    }

    /**
     * Add an error message to the $this->errors array
     *
     * @param $message string - message to add
     */
    public function setError($message) {
        $this->errors[] = $message;
    }

    /**
     * Find lex strings in code file that are not in the Lexicon file
     * and add them to $this->missing array.
     *
     * @param array $missing - (optional) array of missing strings.
     */
    public function setMissing($missing = array()) {
        if (empty($missing)) {
            foreach ($this->used as $key => $value) {
                if (!array_key_exists($key, $this->defined)) {
                    /* missing keys */
                    $this->missing[$key] = $value;
                } elseif (($this->defined[$key] !== $value)
                    && (!empty($value))
                ) {
                    /* Updated keys */
                    $this->toUpdate[$key] = $value;
                }
            }
        } else {
            $this->missing = $missing;
        }
    }

    /**
     * Add a lexicon topic file to $this->lexFiles in the form:
     * fileName => fullPath
     *
     * @param $topic string - can be a topic or a fully qualified lex file spec.
     */
    public function addLexFile($topic) {
        $fqn = $this->getLexFqn($topic);
        $val = explode(':', $fqn);
        $fileName = $val[2] . '.inc.php';
        $fullPath = $this->lexDir . '/' . $val[0] . '/' . $fileName;

        if (!array_key_exists($fileName, $this->lexFiles)) {
            $this->lexFiles[$fileName] = $fullPath;
        }
    }

    /**
     * Return a fully qualified lexicon spec (e.g. 'example:en:default.inc.php')
     * @param $lexFileSpec (partial or full lexicon spec. (e.g., default, en:default)
     * @return string - fully qualified lex spec. (e.g. en:example:default)
     */
    public function getLexFqn($lexFileSpec) {
        $nspos = strpos($lexFileSpec, ':');
        $language = $this->language;


        $namespace = $this->helpers->getProp('packageNameLower');
        if ($nspos === false) {
            $topic_parsed = $lexFileSpec;

        } else { /* if namespace, search specified lexicon */
            $params = explode(':', $lexFileSpec);
            if (count($params) <= 2) {
                $namespace = $params[0];
                $topic_parsed = $params[1];
            } else {
                $language = $params[0];
                $namespace = $params[1];
                $topic_parsed = $params[2];
            }
        }
        return $language . ':' . $namespace . ':' . $topic_parsed;
    }

    /**
     * Create the array of lexicon strings in lexicon files used by this code file in the form:
     * key => value
     *
     * @param array $defined
     */
    public function setDefined($defined = array()) {
        if (!empty($defined)) {
            $this->$defined = $this->defined + $defined;
        } else {
            foreach ($this->lexFiles as $fileName => $fullPath) {
                if (file_exists($fullPath)) {
                    include $fullPath;
                }
            }
            /* @var $_lang array */
            if (isset($_lang)) {
                $this->defined = $this->defined + $_lang;
            }
        }
    }

    /**
     * Update a code file by removing the ~~* part of the lexicon strings.
     * @return int number of ~~ strings removed
     */
    public function updateCodeFile() {
        if (empty($this->squigglesFound)) {
            return 0;
        }
        $fileName = $this->fileName;
        $fullPath = $this->path . '/' . $fileName;
        $content = file_get_contents($fullPath);

        $type = (strpos($fileName, '.php') !== false) || (strpos($fileName, '.js') !== false)
            ? 'modScript'
            : 'text';

        /* Need to handle trailing quote in scripts.
           Files with tags have no trailing quote */
        if (strpos($content, '~~') !== false) {
            /* .php and .js files */
            if ($type == 'modScript') {
                $pattern = '/~~.*([\'\"][\),])/';
                $replace = '$1';
            } else {
                /* text files */
                $pattern = '/~~[^\]\?&]+/';
                $replace = '';
            }

            $content = preg_replace($pattern, $replace, $content);

            if (!empty($content)) {
                $fp = fopen($fullPath, 'w');
                if ($fp) {
                    fwrite($fp, $content);
                    fclose($fp);
                }

            }
        }

        return $this->squigglesFound;
    }

    /**
     *  Create/Update the strings in the lexicon file topic file
     *  specified in this file
     */
    public function updateLexiconFile() {
        if (empty($this->missing) && empty($this->toUpdate)) {
            /* Nothing to do */
            return;
        }
        /* This should never happen */
        if (count($this->lexFiles) !== 1) {
            $this->setError('multiple lexfiles');
            return;
        }

        $path = reset($this->lexFiles);
        // $path = key($this->lexFiles);
        if (!file_exists($path)) {
            $this->setError('LexFile not found');
            return;
        }
        $content = file_get_contents($path);

        /* Add new strings */
        if (!empty($this->missing)) {
            $code = '';
            foreach ($this->missing as $key => $value) {
                $key = var_export($key, true);
                $value = var_export($value, true);
                $value = str_replace("\\\\\\", '\\', $value);
                $value = str_replace("\\\\", '', $value);
                $code .= "\n\$_lang[$key] = " . $value . ';';
            }
            $success = false;
            $comment = $comment = '/* Used in ' . $this->fileName . ' */';
            if (stristr($content, $comment)) {
                $content = str_replace($comment, $comment . $code, $content);
                $fp = fopen($path, 'w');
                if ($fp) {
                    fwrite($fp, $content);
                    fclose($fp);
                    $success = true;
                }
            } else {
                $fp = fopen($path, 'a');
                if ($fp) {
                    fwrite($fp, "\n\n" . $comment . $code);
                    fclose($fp);
                    $success = true;
                } else {
                    $this->helpers->sendLog(MODX::LOG_LEVEL_ERROR,
                        $this->modx->lexicon('mc_could_not_open_lex_file')
                        . ': ' . $path);

                }
            }
            if (!$success) {
                $this->setError($this->modx->lexicon('mc_error_writing_lexicon_file') .
                ': ' . $path);
            }

        }

        /* Update Changed strings */
        if (!empty($this->toUpdate)) {
            /* This may have changed */
            $content = file_get_contents($path);

            foreach ($this->toUpdate as $key => $value) {
                if (empty($value)) {
                    continue;
                }
                $pattern = '#\$_lang\[[\"\']' . $key . '[^=]+=\s*([^;]+);#';
                preg_match($pattern, $content, $matches);

                if (isset($matches[1])) {
                    $value = var_export($value, true);
                    $value = str_replace('\\\\\\', '\\', $value);
                    $value = str_replace("\\\\", '', $value);
                    $replace = str_replace($matches[1], $value,
                        $matches[0]);
                    $content = str_replace($matches[0], $replace, $content);
                }
            }
            $fp = fopen($path, 'w');
            if ($fp) {
                fwrite($fp, $content);
                fclose($fp);
            }
        }
    }
}


class LexiconCodeFile extends AbstractLexiconCodeFile {

    /**
     * Set the lexicon topic for the file and add it to the $this->lexFiles array
     *
     * @param string $topic - (optional) specific lexicon topic
     */
    public function setLexFiles($topic = '') {
        $default = $topic;
        $isPropertiesFile = strpos($this->fileName, 'properties.') !== false;
        $isMenuFile = strpos($this->fileName, 'menus.php') !== false;


        /* set default $pattern and $subPattern */
        $subPattern = 'lexicon->load';
        $pattern = '#lexicon->load\s*\s*\(\s*\'(.*)\'#';

        if (empty($topic)) {
            $default = 'default';
            /* find lexicon->load lines in file or other lex file specification */
            $lines = $this->content;

            /* These have lex topic specified in their fields */
            if ($isMenuFile) {
                $subPattern = 'lang_topics';
                $pattern = '#^\s*[\"\']lang_topics[\'\"]\s*=>\s*[\"\'](.*)[\'\"]#';
            }

            /* iterate over lines to find lexicon topic specification */
            foreach($lines as $line) {
                /* skip lines without subPattern */
                if (strstr($line, $subPattern)) {
                    $matches = array();
                    preg_match($pattern, $line, $matches);
                    if (isset($matches[1]) && !empty($matches[1])) {

                        /* skip dynamic lex loads */
                        if (strpos($matches[1], '$') !== false) {
                            continue;
                        }

                        if ($isMenuFile) {
                            if ($matches[1] == $this->helpers->props['packageNameLower']) {
                                /* Correct if just the package name */
                                $matches[1] = $matches[1] . ':' . $default;
                            }
                            $this->addLexFile($matches[1]);
                            /* bail out at the first non-empty lexicon specification */
                            break;
                        }
                        $this->addLexFile($matches[1]);
                    }
                }
            }
        } else {
            /* use explicit topic sent as argument */
            $this->lexFileName = $topic;
            $this->addLexfile($topic);
        }

        /* assume 'default' topic if no topic specified */
        if (empty($this->lexFiles)) {
            $this->addLexFile($default);
        }
    }



    /**
     * Find all lexicon strings and their values (if any) in the code file
     * and add them to $this->used array.
     *
     * @param array $used
     */
    public function setUsed($used = array()) {
        $coreEntries = $this->modx->lexicon->getFileTopic($this->language);

        /* skip minified JS files */
        if (strstr($this->fileName, 'min.js')) {
            return;
        }
        $type = 'text';
        $subPattern = '';
        if (!empty($used)) {
            $this->used = $used;
        } else {
            $this->used = array();
            if (strpos($this->fileName, '.menus.php') !== false) {
                $type = 'menu';
                $pattern = '#[\'\"]description[\'\"]\s*=>\s*(\'|\")(.*)\1#';
                $subPattern = 'description';
            } elseif (strpos($this->fileName, '.php') !== false) {
                $type = 'php';
                $pattern = '#modx->lexicon\s*\(\s*(\'|\")(.*)\1\)#';
                $subPattern = 'modx->lexicon';
            } elseif (strpos($this->fileName, '.js') !== false) {
                $type = 'js';
                $pattern = '#_\(\s*(\'|\")(.*)\1\)#';
                $subPattern = '_(';
            }  else {
                $type = 'text';
                $pattern = '#(\[\[)!*%([^\?&\]]*)#';
            }

            /* Iterate over lines to find lexicon strings
               in code file */

            $lines = $this->content;
            foreach ($lines as $line) {
                if ($type == 'text') {
                    if ((strpos($line, '[[%') === false) && (strpos($line, '[[!%') === false)) {
                        continue;
                    }
                } elseif (strpos($line, $subPattern) === false) {
                    continue;
                }

                $matches = array();
                preg_match($pattern, $line, $matches);
                if (isset($matches[2]) && !empty($matches[2])) {
                    if (strstr($matches[2], '~~')) {
                        $this->squigglesFound++;
                        $s = explode('~~', $matches[2]);
                        $lexString = $s[0];
                        $value = $s[1];
                    } else {
                        $lexString = $matches[2];
                        $value = '';
                    }
                   /* skip entries that are in the MODX lexicon and not re-defined */
                   if (array_key_exists($lexString, $coreEntries) && empty($value)){
                       continue;
                   }
                   /* Don't update an existing entry with an empty value */
                   if (array_key_exists($lexString, $this->used) && (empty($value))) {
                       continue;
                   }
                   $this->used[$lexString] = $value;
                }
            }
        }
    }

}

class PropertiesLexiconCodeFile extends LexiconCodeFile {
    public function setLexFiles($topic = ''){
        $fullPath = $this->path . '/' . $this->fileName;
        if (file_exists($fullPath)) {
            $objects = include $fullPath;
            if (!is_array($objects)) {
                $this->setError('mc_properties_not_an_array~~Properties not an array in' . $this->fileName);
            } else {
                foreach($objects as $object) {
                    if (isset($object['lexicon'])) {
                        if ($object['lexicon'] == $this->helpers->props['packageNameLower']) {
                            /* Correct if just the package name */
                            $object['lexicon'] = $object['lexicon'] . ':' . 'properties';
                        }
                        $this->addLexFile($object['lexicon']);
                        /* bail out at the first non-empty lexicon specification */
                        break;

                    }
                }
            }
            /* assume 'properties' topic if no topic specified */
            if (empty($this->lexFiles)) {
                $this->addLexFile('properties');
            }

        } else {
            $this->setError($this->modx->lexicon('mc_file_not_found' . ' ' . $fullPath));
        }
        return;

    }

    public function setUsed(){
        $fullPath = $this->path . '/' . $this->fileName;
        if (file_exists($fullPath)) {
            $modx =& $this->modx;
            $objects = include $fullPath;
            if (! is_array($objects)) {
                $this->setError('Not an array');
                return;
            }
            $_lang = $this->defined;
            /** @var $setting modSystemSetting */
            foreach ($objects as $object) {
                if (isset($object['desc']) && ( ! empty($object['desc']))) {
                    if (strstr($object['desc'], '~~')) {
                        $this->squigglesFound++;
                        $s = explode('~~', $object['desc']);
                        $lexString = $s[0];
                        $value = $s[1];
                    } else {
                        $lexString = $object['desc'];
                        $value = '';
                    }
                    $this->used[$lexString] = $value;
                }
            }
        } else {
            $this->setError($this->modx->lexicon('mc_file_not_found' . ' ' . $fullPath));
        }

    }
}

class SettingsLexiconCodeFile extends LexiconCodeFile {

    public function setLexFiles($topic = '') {
        $this->addLexFile('default');
    }

    public function setUsed() {
        $fullPath = $this->path . '/' . $this->fileName;
        if (file_exists($fullPath)) {
            $modx =& $this->modx;
            $objects = include $fullPath;
            $_lang = $this->defined;
            /** @var $setting modSystemSetting */
            foreach ($objects as $setting) {
                $key = $setting->get('key');
                $name = $setting->get('name');
                if (empty($name)) {
                    $name = $key;
                }
                $description = $setting->get('description');
                if (empty($description)) {
                    $description = '';
                }
                $lexNameKey = 'setting_' . $key;
                $lexDescKey = 'setting_' . $key . '_desc';
                $this->used[$lexNameKey] = $name;
                $this->used[$lexDescKey] = $description;
            }
        } else {
            $this->setError($this->modx->lexicon('mc_file_not_found' . ' ' . $fullPath));
        }
    }

}
