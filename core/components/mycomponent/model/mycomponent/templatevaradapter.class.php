<?php


class TemplateVarAdapter extends ElementAdapter
{
    protected $dbClass = 'modTemplateVar';
    protected $dbClassIDKey = 'name';
    protected $dbClassNameKey = 'name';
    protected $dbClassParentKey = 'category';
    protected $createProcessor = 'element/tv/create';
    protected $updateProcessor = 'element/tv/update';
    

    protected $fields;
    protected $name;

    final public function __construct(&$modx, &$helpers, $fields, $mode=MODE_BOOTSTRAP, $object = null) {
        $this->name = $fields['name'];
        if (is_array($fields)) {
            if (isset($fields['templates'])) {
                $this->setTvResolver($fields['templates']);
                unset($fields['templates']);
            }
            $this->myFields = $fields;

        }
        parent::__construct($modx, $helpers, $fields, $mode, $object);

    }
    public function setTvResolver($fields) {
        foreach($fields as $templateName => $rank) {
            $resolverFields = array();
            $resolverFields['templateid'] = $templateName;
            $resolverFields['tmplvarid'] = $this->getName();
            $resolverFields['rank'] = isset($rank) && !empty($rank) ? $rank : '0';
            ObjectAdapter::$myObjects['tvResolver'][] = $resolverFields;
        }

    }

    public function addToMODx($overwrite = false) {
        $fields = $this->myFields;
        parent::addToModx($overwrite);

    }

    public static function createResolver($dir, $intersects, $helpers, $mode = MODE_BOOTSTRAP) {
        /* ToDo: Export mode */
        /* Create tv.resolver.php resolver */
        /* @var $helpers Helpers */
        if (!empty($dir) && !empty($intersects)) {
            $helpers->sendLog(MODX::LOG_LEVEL_INFO, 'Creating TV resolver');
            $tpl = $helpers->getTpl('tvresolver.php');
            $tpl = $helpers->replaceTags($tpl);
            if (empty($tpl)) {
                $helpers->sendLog(MODX::LOG_LEVEL_ERROR, 'tvresolver tpl is empty');
                return false;
            }

            $fileName = 'tv.resolver.php';

            if (!file_exists($dir . '/' . $fileName) || $mode == MODE_EXPORT) {
                $intersectArray = $helpers->beautify($intersects);
                $tpl = str_replace("'[[+intersects]]'", $intersectArray, $tpl);

                $helpers->writeFile($dir, $fileName, $tpl);
            }
            else {
                $helpers->sendLog(MODX::LOG_LEVEL_INFO, '    ' . $fileName . ' already exists');
            }
        }
        return true;
    }
}