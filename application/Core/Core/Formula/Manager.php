<?php


namespace Core\Core\Formula;

use \Core\Core\Exceptions\Error;

class Manager
{
    public function __construct(\Core\Core\Container $container, \Core\Core\Utils\Metadata $metadata)
    {
        $functionClassNameMap = $metadata->get(['app', 'formula', 'functionClassNameMap'], array());

        $this->evaluator = new \Core\Core\Formula\Evaluator($container, $functionClassNameMap);
    }

    public function run($script, $entity = null, $variables = null)
    {
        return $this->evaluator->process($script, $entity, $variables);
    }
}