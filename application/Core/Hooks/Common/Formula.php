<?php


namespace Core\Hooks\Common;

use Core\ORM\Entity;
use Core\Core\Utils\Util;

class Formula extends \Core\Core\Hooks\Base
{
    public static $order = 5;

    protected function init()
    {
        $this->addDependency('metadata');
        $this->addDependency('formulaManager');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getFormulaManager()
    {
        return $this->getInjection('formulaManager');
    }

    public function beforeSave(Entity $entity, array $options = array())
    {
        if (!empty($options['skipFormula'])) return;

        $scriptList = $this->getMetadata()->get(['formula', $entity->getEntityType(), 'beforeSaveScriptList'], []);
        $variables = (object)[];
        foreach ($scriptList as $script) {
            try {
                $this->getFormulaManager()->run($script, $entity, $variables);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('Formula failed: ' . $e->getMessage());
            }
        }

        $customScript = $this->getMetadata()->get(['formula', $entity->getEntityType(), 'beforeSaveCustomScript'], []);
        if ($customScript) {
            try {
                $this->getFormulaManager()->run($customScript, $entity, $variables);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('Formula failed: ' . $e->getMessage());
            }
        }
    }
}
