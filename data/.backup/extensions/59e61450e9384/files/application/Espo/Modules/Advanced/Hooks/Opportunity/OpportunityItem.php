<?php
/**LICENE**/

namespace Core\Modules\Advanced\Hooks\Opportunity;

use Core\ORM\Entity;

class OpportunityItem extends \Core\Core\Hooks\Base
{

    public function beforeSave(Entity $entity)
    {
        if (!$entity->has('itemList')) {
            return;
        }

        $itemList = $entity->get('itemList');

        if (!is_array($itemList)) {
            return;
        }

        if ($entity->has('amountCurrency')) {
            foreach ($itemList as $o) {
                $o->unitPriceCurrency = $entity->get('amountCurrency');
                $o->amountCurrency = $entity->get('amountCurrency');
            }
        }

        if (count($itemList)) {
            $amount = 0.0;
            foreach ($itemList as $o) {
                $amount += $o->amount;
            }
            $amount = round($amount, 2);
            $entity->set('amount', $amount);
        }
    }

    public function afterSave(Entity $entity, array $options = array())
    {
        if (!empty($options['skipWorkflow'])) {
            return;
        }

        if (!$entity->has('itemList')) {
            return;
        }

        $itemList = $entity->get('itemList');

        if (!is_array($itemList)) {
            return;
        }

        $toCreateList = [];
        $toUpdateList = [];
        $toRemoveList = [];

        if (!$entity->isNew()) {
            $prevItemCollection = $this->getEntityManager()->getRepository('OpportunityItem')->where(array(
                'opportunityId' => $entity->id
            ))->order('order')->find();
            foreach ($prevItemCollection as $item) {
                $exists = false;
                foreach ($itemList as $data) {
                    if ($item->id === $data->id) {
                        $exists = true;
                    }
                }
                if (!$exists) {
                    $toRemoveList[] = $item;
                }
            }
        }

        $order = 0;
        foreach ($itemList as $o) {
            $order++;
            $exists = false;
            if (!$entity->isNew()) {
                foreach ($prevItemCollection as $item) {
                    if ($o->id === $item->id) {
                        $this->setItemWithData($item, $o);
                        $item->set('order', $order);
                        $item->set('opportunityId', $entity->id);
                        $exists = true;
                        $toUpdateList[] = $item;
                        break;
                    }
                }
            }

            if (!$exists) {
                $item = $this->getEntityManager()->getEntity('OpportunityItem');
                $this->setItemWithData($item, $o);
                $item->set('order', $order);
                $item->set('opportunityId', $entity->id);
                $item->id = null;
                $toCreateList[] = $item;
            }
        }

        if ($entity->isNew()) {
            foreach ($toUpdateList as $item) {
                $item->id = null;
                $toCreateList[] = $item;
            }
            $toUpdateList = [];
        }

        foreach ($toRemoveList as $item) {
            $this->getEntityManager()->removeEntity($item);
        }

        foreach ($toUpdateList as $item) {
            $this->getEntityManager()->saveEntity($item);
        }

        foreach ($toCreateList as $item) {
            $this->getEntityManager()->saveEntity($item);
        }


        $itemCollection = $this->getEntityManager()->getRepository('OpportunityItem')->where(array(
            'opportunityId' => $entity->id
        ))->order('order')->find();

        $entity->set('itemList', $itemCollection->toArray());
    }

    protected function setItemWithData(Entity $item, \StdClass $o)
    {
        $item->set(array(
            'id' => $o->id,
            'name' => $this->getAttributeFromItemObject($o, 'name'),
            'unitPrice' => $this->getAttributeFromItemObject($o, 'unitPrice'),
            'unitPriceCurrency' => $this->getAttributeFromItemObject($o, 'unitPriceCurrency'),
            'amount' => $this->getAttributeFromItemObject($o, 'amount'),
            'amountCurrency' => $this->getAttributeFromItemObject($o, 'amountCurrency'),
            'productId' => $this->getAttributeFromItemObject($o, 'productId'),
            'productName' => $this->getAttributeFromItemObject($o, 'productName'),
            'quantity' => $this->getAttributeFromItemObject($o, 'quantity'),
            'description' => $this->getAttributeFromItemObject($o, 'description'),
        ));
    }

    protected function getAttributeFromItemObject($o, $attribute)
    {
        return isset($o->$attribute) ? $o->$attribute : null;
    }

}

