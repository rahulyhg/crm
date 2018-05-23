<?php


namespace Core\Hooks\Common;

use Core\ORM\Entity;
use Core\Core\Utils\Util;

class CurrencyConverted extends \Core\Core\Hooks\Base
{
    public static $order = 1;

    protected function init()
    {
        $this->addDependency('metadata');
        $this->addDependency('config');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    public function beforeSave(Entity $entity, array $options = array())
    {
        $fieldDefs = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields'], []);
        foreach ($fieldDefs as $fieldName => $defs) {
            if (!empty($defs['type']) && $defs['type'] === 'currencyConverted') {
                $currencyFieldName = substr($fieldName, 0, -9);
                $currencyCurrencyFieldName = $currencyFieldName . 'Currency';
                if (!$entity->isAttributeChanged($currencyFieldName) && !$entity->isAttributeChanged($currencyCurrencyFieldName)) {
                    continue;
                }
                if (!empty($fieldDefs[$currencyFieldName])) {
                    if ($entity->get($currencyFieldName) === null) {
                        $entity->set($fieldName, null);
                    } else {
                        $currency = $entity->get($currencyCurrencyFieldName);
                        $value = $entity->get($currencyFieldName);
                        if (!$currency) continue;
                        $rates = $this->getConfig()->get('currencyRates', array());
                        $baseCurrency = $this->getConfig()->get('baseCurrency');
                        $defaultCurrency = $this->getConfig()->get('defaultCurrency');
                        if ($defaultCurrency === $currency) {
                            $targetValue = $value;
                        } else {
                            $targetValue = $value;
                            $targetValue = $targetValue * (isset($rates[$defaultCurrency]) ? $rates[$defaultCurrency] : 1.0);
                            $targetValue = $targetValue / (isset($rates[$currency]) ? $rates[$currency] : 1.0);
                            $targetValue = round($targetValue, 2);
                        }
                        $entity->set($fieldName, $targetValue);
                    }
                }
            }
        }
    }
}
