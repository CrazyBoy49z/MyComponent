<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Bob Ray
 * Date: 8/16/12
 * Time: 11:29 PM
 * To change this template use File | Settings | File Templates.
 */

ob_start();
/**
 * Test class for MyComponentProject Bootstrap.
 * Generated by PHPUnit on 2012-03-02 at 23:02:19.
 * @outputBuffering disabled
 */

class MyComponentProjectTest extends PHPUnit_Framework_TestCase
{
    /* @var $mc MyComponentProject */
    public $mc;
    /* @var $modx modX */
    public $modx;
    /* @var $categoryName string */
    public $categoryName;
    /* @var $utHelpers UtHelpers */
    public $utHelpers;
    /* @var $category modCategory */
    public $category;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before each test is executed.
     */
    protected function setUp()
    {
        // echo "\n---------------- SETUP --------------------";
        require_once dirname(__FILE__) . '/build.config.php';
        require_once dirname(__FILE__) . '/uthelpers.class.php';
        require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
        $this->utHelpers = new UtHelpers();

        $modx = new modX();
        $modx->initialize('mgr');
        $modx->getService('error', 'error.modError', '', '');
        $modx->getService('lexicon', 'modLexicon');
        $modx->getRequest();
        $homeId = $modx->getOption('site_start');
        $homeResource = $modx->getObject('modResource', $homeId);

        if ($homeResource instanceof modResource) {
            $modx->resource = $homeResource;
        } else {
            echo "\nNo Resource\n";
        }

        $modx->setLogLevel(modX::LOG_LEVEL_ERROR);
        $modx->setLogTarget('ECHO');

        require_once MODX_ASSETS_PATH . 'mycomponents/mycomponent/core/components/mycomponent/model/mycomponent/mycomponentproject.class.php';

        /* @var $categoryObj modCategory */
        $this->mc = new MyComponentProject($modx);
        $this->mc->init(array(), 'unittest');
        $this->modx =& $modx;
        $this->category = key($this->mc->props['categories']);
        $this->packageNameLower = $this->mc->packageNameLower;
        if ($this->category != 'UnitTest') {
            session_write_close();
            die('wrong config - NEVER run unit test on a real project!');
        }
        $category = $this->modx->getCollection('modCategory', array('category' => 'UnitTest'));
        foreach ($category as $categoryObj) {
            $categoryObj->remove();
        }
        $namespace = $this->modx->getObject('modNamespace', array('name' => 'unittest'));
        if ($namespace) $namespace->remove();
        $this->utHelpers->rrmdir($this->mc->targetRoot);

        $this->utHelpers->removeElements($this->modx, $this->mc);
        $this->utHelpers->removeResources($this->modx, $this->mc);

        //$this->mc->createCategory();
        //$this->mc->createNamespace();
    }


    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after each test is executed.
     */
    protected function tearDown()
    {
        // echo "\n---------------- TEARDOWN --------------------";
        /* @var $categoryObj modCategory */
        /* @var $namespace modNamespace */
        $this->utHelpers->rrmdir($this->mc->targetRoot);
        $category = $this->modx->getCollection('modCategory', array('category' => 'UnitTest'));
        foreach($category as $categoryObj) {
            $categoryObj->remove();
        }
        $namespace = $this->modx->getObject('modNamespace', array('name'=> 'unittest'));
        if ($namespace) $namespace->remove();
        $this->utHelpers->rrmdir($this->mc->targetRoot);
        $this->utHelpers->removeElements($this->modx, $this->mc);
        $this->utHelpers->removeResources($this->modx, $this->mc);
        $this->utHelpers->removeSystemSettings($this->modx, $this->mc);
        $this->utHelpers->removeNamespaces($this->modx, $this->mc);
        $this->utHelpers->removeCategories($this->modx, $this->mc);
        $this->mc = null;
        $this->modx = null;
    }

    /**
     * @covers MyComponentProject::init
     */
    public function testinit()
    {
        /* make sure $modx and $props are set */
        $this->assertTrue(isset($this->mc->modx));
        $this->assertInstanceOf('modX', $this->mc->modx);
        $this->assertTrue(isset($this->mc->props));
        $this->assertTrue(is_array($this->mc->props));

        /* make sure basic member variables are not empty */
        $this->assertNotEmpty($this->mc->packageNameLower);
        $this->assertNotEmpty($this->mc->targetRoot);
        //$this->assertNotEmpty($this->mc->targetAssets);
        $this->assertNotEmpty($this->mc->myPaths['targetCore']);
        $this->assertNotEmpty($this->mc->dirPermission);
        $this->assertNotEmpty($this->mc->props['filePermission']);
        /* make sure helpers class was loaded  */
        $this->assertTrue(method_exists($this->mc->helpers,'replaceTags'));
    }

    public function testCreateCategories() {
        /* @var $category modCategory */
        $this->mc->createBasics();
        $this->mc->createCategories();
        $categories = $this->mc->props['categories'];
        $this->assertNotEmpty($categories);
        foreach($categories as $category => $fields){
            $category = $this->modx->getObject('modCategory', array('category' => $category));
            $this->assertInstanceOf('modCategory', $category);
            $p = $category->get('parent');
            if (! empty($p) ) {
                $pObj = $this->modx->GetObject('modCategory', $p);
                $this->assertEquals($fields['parent'], $pObj->get('category'));
            }
        }


    }
    public function testCreateNamespaces() {
        /* @var $namespace modNamespace */
        $this->mc->createNamespaces();
        $namespace = $this->modx->getObject('modNamespace', array('name' => 'unittest'));
        $this->assertInstanceOf('modNamespace', $namespace);
        $this->assertNotEmpty($namespace->get('path'));
        $this->assertTrue(strstr($namespace->get('path'),'{core_path}') !== false);
        $this->assertTrue(strstr($namespace->get('assets_path'), '{assets_path}') !== false);
        $namespace->remove();

    }

    public function testCreateNewSystemSettings() {

        $this->mc->createNamespaces();
        $this->mc->createNewSystemSettings();
        $settings = $this->mc->props['newSystemSettings'];
        $this->assertNotEmpty($settings);
        // @var $object modSystemSetting
        foreach ($settings as $settingKey => $fields) {

            $object = $this->modx->getObject('modSystemSetting', array('key' => $settingKey));
            $this->assertInstanceOf('modSystemSetting', $object);
            $this->assertEquals($this->mc->props['packageNameLower'], $object->get('namespace'));
            $object->remove();
        }

    }
    public function testCreateNewSystemEvents() {
        $this->mc->createNewSystemEvents();
        $events = $this->mc->props['newSystemEvents'];
        $this->assertNotEmpty($events);
        foreach($events as $key => $fields) {
            $object = $this->modx->getObject('modEvent', array('name' => $key));
            $this->assertInstanceOf('modEvent', $object);
            $object->remove();
        }
    }
    public function testCreateElements() {
        /* @var $obj modElement */
        $this->mc->createCategories();
        /* should not create elements */
        $this->mc->props['createElementObjects'] = false;
        $this->mc->props['createElementFiles'] = false;
        $this->mc->createElements();
        $elements = $this->mc->props['elements'];
        $this->assertNotEmpty($elements);
        foreach ($elements as $elementType => $elementNames) {
            $elementType = 'mod' . ucfirst(substr($elementType, 0, -1));
            if (!empty($elementNames)) {
                $alias = $this->mc->helpers->getNameAlias($elementType);
                    foreach ($elementNames as $elementName => $fields) {
                        $obj = $this->modx->getObject($elementType, array($alias => $elementName));
                        //$this->assertNotInstanceOf($elementType,$obj);
                        $fileName = $this->mc->helpers->getFileName($elementName, $elementType);
                        $codeDir = $this->mc->helpers->getCodeDir
                            ($this->mc->myPaths['targetCore'], $elementType);
                        // $this->assertFalse(file_exists($codeDir . '/' . $fileName));
                    }
                }
        }
        /* create files and objects */
        $this->mc->props['createElementObjects'] = true;
        $this->mc->props['createElementFiles'] = true;
        $this->mc->createElements();
        $elements = $this->mc->props['elements'];
        $this->assertNotEmpty($elements);
        foreach ($elements as $elementType => $elementNames) {
            $elementType = 'mod' . ucfirst(substr($elementType, 0, -1));
            if (!empty($elementNames)) {
                $alias = $this->mc->helpers->getNameAlias($elementType);
                foreach ($elementNames as $elementName => $fields) {
                    $obj = $this->modx->getObject($elementType, array($alias => $elementName));
                    $this->assertInstanceOf($elementType, $obj);
                    $fileName = $this->mc->helpers->getFileName($elementName, $elementType);
                    $codeDir = $this->mc->helpers->getCodeDir
                        ($this->mc->myPaths['targetCore'], $elementType);

                    if ($elementType != 'modTemplateVar' && $elementType != 'modPropertySet') {
                        $this->assertTrue(file_exists($codeDir . '/' . $fileName), $fileName . '/' . $fileName);
                    }
                }
            }
        }
        
        $this->utHelpers->RemoveElements($this->modx, $this->mc);
    }
    public function testCreateResources() {
        /* @var $r modResource */
        $this->mc->createCategories();
        $this->mc->createNamespaces();
        $this->mc->createElements();
        $this->mc->createResources();
        $resources = $this->mc->props['resources'];
        $this->assertNotEmpty($resources);
        $id = null;
        foreach ($resources as $resource => $fields) {
            $r = $this->modx->getObject('modResource', array('pagetitle' => $resource));
            $this->assertInstanceOf('modResource', $r);
            $pagetitle = $r->get('pagetitle');
            $this->assertNotEmpty($pagetitle);
            /* see if parent, template, and TV Values are set properly */
            if ($pagetitle == 'utResource1') {
                $this->assertEquals($this->modx->getOption('default_template'), $r->get('template'));
                $this->assertEquals(0, $r->get('parent'));
                $id = $r->get('id');
            } elseif ($pagetitle == 'utResource2') {
                $templateObj = $this->modx->getObject('modTemplate',
                    array('templatename' => 'utTemplate1'));
                $templateId = $templateObj->get('id');
                $this->assertEquals($templateId, $r->get('template'));
                $this->assertEquals($id, $r->get('parent'));
                $this->assertEquals('SomeValue', $r->getTVValue('utTv1'));
                $this->assertEquals('SomeOtherValue', $r->getTVValue('utTv2'));

            } else {
                /* make sure pagetitles are set properly */
                assertTrue(false, 'BAD RESOURCE pagetitle');
            }
        }
    }
    public function testCreateBasics() {
        $this->mc->createBasics();
        $this->assertTrue(file_exists($this->mc->myPaths['targetRoot'] . '_build/build.transport.php'));
        $this->assertTrue(file_exists($this->mc->myPaths['targetRoot'] . '_build/build.config.php'));
        $this->assertTrue(is_dir($this->mc->myPaths['targetRoot'] . '_build/utilities'));
        $this->assertFileExists(($this->mc->myPaths['targetRoot'] .
            '_build/utilities/jsmin.class.php'));
        $this->assertFileExists(($this->mc->myPaths['targetRoot'] .
            '_build/utilities/jsminplus.class.php'));
        $this->assertFalse(file_exists($this->mc->myPaths['targetRoot'] . '_build/utilities/config/' . $this->mc->packageNameLower . 'config.php'));
        $this->utHelpers->rrmdir($this->mc->myPaths['targetRoot']);
        $this->assertFalse(is_dir($this->mc->myPaths['targetRoot'] . '_build'));

        $this->mc->props['defaultStuff']['utilities'] = false;
        $this->mc->createBasics();
        $this->assertTrue(file_exists($this->mc->myPaths['targetRoot'] . '_build/build.transport.php'));
        $this->assertNotEmpty(file_get_contents($this->mc->myPaths['targetRoot'] . '_build/build.transport.php'));
        $this->assertTrue(file_exists($this->mc->myPaths['targetRoot'] . '_build/build.config.php'));
        $this->assertNotEmpty(file_get_contents($this->mc->myPaths['targetRoot'] . '_build/build.config.php'));

        $docs = array('readme.txt', 'tutorial.html', 'license.txt', 'changelog.txt' );
        foreach($docs as $doc) {
            $this->assertTrue(file_exists($this->mc->myPaths['targetCore'] . 'docs/' . $doc));
            $this->assertNotEmpty(file_get_contents($this->mc->myPaths['targetCore'] . 'docs/' . $doc));
        }
        $this->assertTrue(file_exists($this->mc->myPaths['targetRoot'] . 'readme.md'));
        $this->assertNotEmpty(file_get_contents($this->mc->myPaths['targetRoot'] . 'readme.md'));

        $this->assertNotEmpty($this->mc->props['languages']);
        $languages = $this->mc->props['languages'];
        $this->assertNotEmpty($languages);
        foreach ($languages as $dir => $language) {
            $this->assertNotEmpty($language);
            foreach ($language as $k => $file){

                $this->assertFileExists($this->mc->myPaths['targetCore'] . 'lexicon/' . $dir
                    . '/' . $file . '.inc.php', 'LANGUAGE: ' . $language .
                    '  FILE: ' . $file . '.inc.php');
                $this->assertNotEmpty(file_get_contents($this->mc->myPaths['targetCore'] .
                    'lexicon/' . $dir . '/' . $file . '.inc.php', 'LANGUAGE: ' .
                    $language . '  FILE: ' . $file . '.inc.php'));

            }
        }
        $file = $this->mc->myPaths['targetBuild'] . 'utilities/' . 'jsmin.class.php';
        $this->assertTrue(file_exists($file), $file);
    }
    public function testCreateAssetsDirs() {
        $this->mc->props['createCmpFiles'] = false;
        $this->mc->createAssetsDirs();
        $this->assertFileExists($this->mc->myPaths['targetAssets'] . 'css/' . $this->mc->packageNameLower . '.css');
        $this->assertnotEmpty(file_get_contents($this->mc->myPaths['targetAssets'] . 'css/' . $this->mc->packageNameLower . '.css'));
        $this->assertFileExists($this->mc->myPaths['targetAssets'] . 'js/' . $this->mc->packageNameLower . '.js');
        $this->assertnotEmpty(file_get_contents($this->mc->myPaths['targetAssets'] . 'js/' . $this->mc->packageNameLower . '.js'));
    }
    public function testCreateResolvers() {
        $this->mc->createCategories();
        $this->mc->createResources();
        $this->mc->createElements();
        $this->mc->createResolvers();
        // $this->mc->createExtraResolvers();
        $fileNames = array('plugin','tv','resource','propertyset','category','addusers','unittest');
        foreach($fileNames as $k => $name) {
            $fileName = $this->mc->myPaths['targetRoot'] . '_build/resolvers/' . $name . '.resolver.php';
            $this->assertFileExists($fileName);
            $content = file_get_contents($fileName);
            $this->assertNotEmpty($content);

            /* make sure all placeholders got replaced */
            /* allow this one */
            $content = str_replace('/* [[+code]] */' , '', $content);
            $this->assertEmpty(strstr($content, '[[+'));
            $this->assertNotEmpty(strstr($content, 'License'));

            if ($name == 'plugin') {
                $names = $this->mc->props['newSystemEvents'];
                $this->assertNotEmpty($names);
                foreach($names as $key => $fields) {
                    $this->assertNotEmpty(strstr($content, $fields['name']));
                }
            }
        }


    }

    public function testCreateIntersects() {
        $this->mc->createCategories();
        $this->mc->createNewSystemEvents();
        $this->mc->createResources();
        $this->mc->createElements();
        $this->mc->createIntersects();
        /* pluginEvents */
        $pes = ObjectAdapter::$myObjects['pluginResolver'];
        $this->assertNotEmpty($pes);
        foreach($pes as $k => $fields) {
            $plugin = $this->modx->getObject('modPlugin', array('name' => $fields['pluginid']));
            $this->assertNotEmpty($plugin);
            $fields['pluginid'] = $plugin->get('id');
            if (!empty($fields['propertyset'])) {
                $pSet = $this->modx->getObject('modPropertySet', array('name' => $fields['propertyset']));
                $fields['propertyset'] = $pSet->get('id');
            }
            $pe = $this->modx->getObject('modPluginEvent', $fields);
            $this->assertInstanceOf('modPluginEvent', $pe);
        }
        /* templateVarTemplate */
        $tvts = ObjectAdapter::$myObjects['tvResolver'];
        $this->assertNotEmpty($tvts);
        foreach ($tvts as $k => $fields) {
            if ($fields['templateid'] == 'default') {
                $fields['templateid'] = $this->modx->getOption('default_template');
            } else {
                $template = $this->modx->getObject('modTemplate', array('templatename' => $fields['templateid']));
                $this -> assertNotEmpty($template);
                $fields['templateid'] = $template->get('id');
            }
            $tv = $this->modx->getObject('modTemplateVar', array('name' => $fields['tmplvarid']));
            $this->assertNotEmpty($tv);
            $fields['tmplvarid'] = $tv->get('id');
            $tvt = $this->modx->getObject('modTemplateVarTemplate', $fields);
            $this->assertInstanceOf('modTemplateVarTemplate', $tvt);
        }
        /* elementPropertySet */
        $eps = ObjectAdapter::$myObjects['propertySetResolver'];
        $this->assertNotEmpty($eps);

        foreach($eps as $k => $fields) {
            $name = $this->utHelpers->getNameAlias($fields['element_class']);
            $element = $this->modx->getObject($fields['element_class'], array($name => $fields['element']));
            $this->assertNotEmpty($element);
            $fields['element'] = $element->get('id');
            $pSet = $this->modx->getObject('modPropertySet', array('name' => $fields['property_set']));
            $this->assertNotEmpty($pSet);
            $fields['property_set'] = $pSet->get('id');
            $ep = $this->modx->getObject('modElementPropertySet', $fields);
            $this->assertInstanceOf('modElementPropertySet', $ep);
        }
    }

    /* Tests setting of resource template, parent, and TvValues */
    public function testResourceValues() {
        $this->mc->createCategories();
        $this->mc->createElements();
        $this->mc->createResources();

        $ResourceTemplates = $this->modx->getOption('resourceResolver', ObjectAdapter::$myObjects, '');
        $this->assertNotEmpty($ResourceTemplates);
        foreach ($ResourceTemplates as $k => $fields) {
            $this->assertNotEmpty($fields['pagetitle']);
            $this->assertNotEmpty($fields['parent']);
            $this->assertNotEmpty($fields['template']);
            $resource = $this->modx->getObject('modResource', array('pagetitle' => $fields['pagetitle']));
            $this->assertInstanceOf('modResource', $resource);

            if ($fields['template'] == 'default') {
                $fields['template'] = $this->modx->getOption('default_template');
            } else {
                $templateObj = $this->modx->getObject('modTemplate',
                    array('templatename' => $fields['template']));
                $fields['template'] = $templateObj->get('id');
                $this->assertInstanceOf('modTemplate', $templateObj);
                $this->assertEquals($fields['template'], $resource->get('template'));
            }
            if ($fields['parent'] == 'default') {
                $fields['parent'] = 0;
            } else {
                $parent = $this->modx->getObject('modResource', array('pagetitle' => $fields['parent']));
                $this->assertInstanceOf('modResource', $parent);
                $fields['parent'] = $parent->get('id');
                $this->assertEquals($fields['parent'], $resource->get('parent'));
            }
            if (isset($fields['tvValues'])) {
                foreach($fields['tvValues'] as $tv => $value){
                    //$this->assertEquals($value, $resource->getTVValue($tv));
                }
            }
        }
    }
    public function testCreateValidators() {
        $this->mc->createValidators();
        $validators = $this->mc->props['validators'];
        $this->assertNotEmpty($validators);

        foreach ($validators as $validator) {
            $validator = $validator == 'default'? $this->mc->packageNameLower : $validator;
            $this->assertFileExists($this->mc->myPaths['targetRoot'] . '_build/validators/' . $validator . '.validator.php');
            $this->assertNotEmpty(file_get_contents($this->mc->myPaths['targetRoot'] . '_build/validators/' . $validator . '.validator.php'));

        }
    }


    public function testCreateInstallOption () {
        $this->mc->createInstallOptions();
        $this->assertFileExists($this->mc->myPaths['targetRoot'] . '_build/install.options/user.input.php');
        $this->assertNotEmpty(file_get_contents($this->mc->myPaths['targetRoot'] . '_build/install.options/user.input.php'));
    }

    public function testCreateCmpFiles() {
        $baseDir = $this->mc->myPaths['targetCore'];
        $modelDir =  $baseDir . 'model';
        $processorsDir = $baseDir . 'processors/';
        $controllersDir = $baseDir . 'controllers/';
        $connectorsDir = $this->mc->myPaths['targetAssets'];
        $jsDir = $this->mc->myPaths['targetJs'];

        $this->utHelpers->rrmdir($modelDir);
        $modelDir = $modelDir . '/';
        $packageNameLower = $this->mc->packageNameLower;
        $this->assertNotEmpty($packageNameLower);
        // $this->mc->createBasics();
        $this->mc->createCmpFiles();
        $actionFile = $this->mc->props['actionFile'];
        $this->assertNotEmpty($actionFile);
        $file = $baseDir . $actionFile;
        $this->assertFileExists($file);
        $content = file_get_contents($file);
        $this->assertNotEmpty($content);
        $this->assertContains('License', $content);
        $this->assertContains('controller', $content);

        /* Processors */
        $this->assertTrue (is_dir($processorsDir));
        $processors = $this->mc->props['processors'];
        $this->assertNotEmpty($processors);
        foreach ($processors as $processor) {
            $p = explode(':', $processor);
            $dir = $p[0];
            $fileName = $p[1];
            $fullPath = $processorsDir . $dir . '/' . $fileName . '.class.php';
            $this->assertFileExists($fullPath);
            $content = file_get_contents($fullPath);
            $this->assertContains('License', $content);
            $this->assertContains('Processor', $content);
            $this->assertNotContains('[[+', $content, 'Unprocessed tag');
            $this->assertNotContains('mc_element', $content, 'Unprocessed tag', true);
        }

        /* Controllers */
        $this->assertNotEmpty($controllersDir);
        $this->assertTrue(is_dir($controllersDir));
        $controllers = $this->mc->props['controllers'];
        $this->assertNotEmpty($controllers);
        foreach ($controllers as $controller) {
            $p = explode(':', $controller);
            $dir = $p[0];
            $dir = !empty($dir)? $dir . '/' : '';
            $fileName = $p[1];
            $fullPath = $controllersDir . $dir . $fileName;
            $this->assertFileExists($fullPath);
            $content = file_get_contents($fullPath);
            $this->assertContains('License', $content);
            $this->assertContains('Controller', $content);
            $this->assertNotContains('[[+', $content, 'Unprocessed tag');
            $this->assertNotContains('mc_element', $content, 'Unprocessed tag', true);
        }

        /* Connectors */
        $this->assertNotEmpty($connectorsDir);
        $this->assertTrue(is_dir($connectorsDir));
        $connectors = $this->mc->props['connectors'];
        $this->assertNotEmpty($connectors);
        foreach ($connectors as $connector) {
            $fileName = $connector;
            $fullPath = $connectorsDir . $fileName;
            $this->assertFileExists($fullPath);
            $content = file_get_contents($fullPath);
            $this->assertContains('License', $content);
            $this->assertContains('Connector', $content);
            $this->assertContains('core_path', $content);
            $this->assertNotContains('[[+', $content, 'Unprocessed tag');
            $this->assertNotContains('mc_element', $content, 'Unprocessed tag', true);
        }
        
        /* JS Files */
        $this->assertNotEmpty($jsDir);
        $this->assertTrue(is_dir($jsDir));
        $jsfiles = $this->mc->props['cmpJsFiles'];
        $this->assertNotEmpty($jsfiles);
        foreach ($jsfiles as $jsfile) {
            $p = explode(':', $jsfile);
            $dir = $p[0];
            $dir = !empty($dir)
                ? $dir . '/'
                : '';
            $fileName = $p[1];
            $fullPath = $jsDir . $dir . $fileName;
            $this->assertFileExists($fullPath);
            $content = file_get_contents($fullPath);
            $this->assertContains('License', $content);
            $this->assertContains('extend', $content, $fullPath);
            $this->assertNotContains('[[+', $content, 'Unprocessed tag');
            $this->assertNotContains('mc_element', $content, 'Unprocessed tag', true);
        }

    }

        public function testCreateClassFiles() {
        $this->utHelpers->rrmdir($this->mc->myPaths['targetCore'] . '/model');
        $packageNameLower = $this->mc->packageNameLower;
        $this->assertNotEmpty($packageNameLower);
        $this->mc->createCmpFiles();
        $this->mc->createClassFiles();
        $classes = $this->mc->props['classes'];
        $this->assertNotEmpty($classes);
        $baseDir = $this->mc->myPaths['targetCore'] . 'model';
        foreach ($classes as $className => $data) {
            $data = explode(':', $data);
            if (!empty($data[1])) {
                $dir = $baseDir . '/' . $data[0];
                $fileName = $data[1];
            } else { /* no directory */
                $dir = $baseDir . '/' . $packageNameLower;
                $fileName = $data[0];
            }
            $fileName = strtolower($fileName) . '.class.php';
            $this->assertTrue(is_dir($dir), 'Not a Dir: ' . $fileName);
            $this->assertFileExists($dir . '/' . $fileName);
            $this->assertNotEmpty(file_get_contents($dir . '/' . $fileName));
            $content = file_get_contents($dir . '/' . $fileName);
            /* check for constructor */
            $this->assertNotEmpty(strstr($content, '__construct'));
            /* check for class name */
            $this->assertNotEmpty(strstr($content,
                'class ' . $className), 'No ' . $className . ' class');
            /* check for license */
            $this->assertNotEmpty(strstr($content, 'License'));
            /* make sure all placeholders got replaced */
            $this->assertEmpty(strstr($content, '[[+'));
        }

    }
    public function testHelpers() {
        $dir = $this->mc->myPaths['targetCore'] . '/dummy/dummy';
        $fileName = 'dummy.php';
        $this->assertFileNotExists($dir .'/' . $fileName);
        $this->mc->helpers->writeFile($dir, $fileName, 'Something');
        $this->assertFileExists($dir . '/' . $fileName);
        $this->assertEquals(file_get_contents($dir . '/' . $fileName), 'Something');
        $this->utHelpers->rrmdir($this->mc->myPaths['targetRoot']);
        $this->assertFileNotExists($dir . '/' . $fileName);

        /* test removeElements */
        $this->utHelpers->removeElements($this->modx, $this->mc);
        // $this->mc->createElements();
        $this->utHelpers->removeElements($this->modx, $this->mc);
        $elements = $this->mc->props['elements'];
        $this->assertNotEmpty($elements);
        foreach ($elements as $elementType => $elementNames) {
            $elementType = 'mod' . ucFirst(substr($elementType,0, -1));
            $alias = $this->mc->helpers->getNameAlias($elementType);
            // $elementNames = empty($elementNames)? array() : explode(',', $elementNames);
            $this->assertNotEmpty($elementNames);
            foreach($elementNames as $elementName => $fields) {
                $obj = $this->modx->getObject($elementType, array($alias => $elementName));
                $this->assertNull($obj);
            }
        }
        /* test removeResources */
        $this->mc->createResources();
        $this->utHelpers->removeResources($this->modx, $this->mc);
        $resources = $this->mc->props['resources'];
        $this->assertNotEmpty($resources);
        foreach ($resources as $pagetitle => $fields) {
            $r = $this->modx->getObject('modResource', array('pagetitle' => $pagetitle));
            $this->assertNull($r);
        }

    }
}