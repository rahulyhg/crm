<?php


namespace Core\Core\Loaders;

class FormulaManager extends Base
{
    public function load()
    {
        $formulaManager = new \Core\Core\Formula\Manager(
            $this->getContainer(),
            $this->getContainer()->get('metadata')
        );

        return $formulaManager;
    }
}
