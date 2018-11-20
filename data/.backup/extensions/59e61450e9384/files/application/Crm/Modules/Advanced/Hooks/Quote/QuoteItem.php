<?php
/**LICENE**/

namespace Core\Modules\Advanced\Hooks\Quote;

use Core\ORM\Entity;

class QuoteItem extends \Core\Core\Hooks\Base
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
                $o->listPriceCurrency = $entity->get('amountCurrency');
                $o->unitPriceCurrency = $entity->get('amountCurrency');
                $o->amountCurrency = $entity->get('amountCurrency');
            }
        }

        $accountId = $entity->get('accountId');
        $accountName = $entity->get('accountName');

        foreach ($itemList as $o) {
            if (!property_exists($o, 'unitWeight')) {
                $o->unitWeight = null;
            }

            if ($o->unitWeight === null && $entity->isNew()) {
                if (!empty($o->productId)) {
                    $product = $this->getEntityManager()->getEntity('Product', $o->productId);
                    if ($product) {
                        $o->unitWeight = $product->get('weight');
                    }
                }
            }

            if ($o->unitWeight !== null) {
                $o->weight = $o->unitWeight * $o->quantity;
            } else {
                $o->weight = null;
            }



            $o->accountId = $accountId;
            $o->accountName = $accountName;

            $o->discount = 0.0;
            if (isset($o->unitPrice) && isset($o->listPrice)) {
                if ($o->listPrice) {
                    $o->discount = (($o->listPrice - $o->unitPrice) / $o->listPrice) * 100.0;
                }
            }
        }

        if (count($itemList)) {
            $amount = 0.0;
            $weight = 0.0;
            foreach ($itemList as $o) {
                $amount += $o->amount;
                if (!is_null($o->weight)) {
                    $weight += $o->weight;
                }
            }
            $amount = round($amount, 2);
            $entity->set('amount', $amount);
            $entity->set('weight', $weight);
        }
    }

    public function afterSave(Entity $entity, array $options = array())
    {
        if (!empty($options['skipWorkflow'])) {
            return;
        }

        if (!$entity->has('itemList')) {
            if ($entity->isAttributeChanged('accountId')) {
                $quoteItemList = $this->getEntityManager()->getRepository('QuoteItem')->where(array(
                    'quoteId' => $entity->id
                ))->find();

                foreach ($quoteItemList as $item) {
                    $item->set('accountId', $entity->get('accountId'));
                    $this->getEntityManager()->saveEntity($item);
                }
            }
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
            $prevItemCollection = $this->getEntityManager()->getRepository('QuoteItem')->where(array(
                'quoteId' => $entity->id
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
                        $isChanged = false;
                        foreach (get_object_vars($o) as $k => $v) {
                             if (
                                is_numeric($v) && is_numeric($item->get($k)) && abs($v - $item->get($k)) > 0.00001
                                ||
                                (!is_numeric($v) || !is_numeric($item->get($k))) && $v !== $item->get($k)
                            ) {
                                $isChanged = true;
                                break;
                            }
                        }
                        $exists = true;
                        if (!$isChanged) break;
                        $this->setItemWithData($item, $o);
                        $item->set('order', $order);
                        $item->set('quoteId', $entity->id);
                        $toUpdateList[] = $item;
                        break;
                    }
                }
            }


            if (!$exists) {
                $item = $this->getEntityManager()->getEntity('QuoteItem');
                $this->setItemWithData($item, $o);
                $item->set('order', $order);
                $item->set('quoteId', $entity->id);
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


        $itemCollection = $this->getEntityManager()->getRepository('QuoteItem')->where(array(
            'quoteId' => $entity->id
        ))->order('order')->find();

        $entity->set('itemList', $itemCollection->toArray());
    }

    protected function setItemWithData(Entity $item, \StdClass $o)
    {
        $data = array(
            'id' => isset($o->id) ? $o->id : null,
            'name' => $this->getAttributeFromItemObject($o, 'name'),
            'listPrice' => $this->getAttributeFromItemObject($o, 'listPrice'),
            'listPriceCurrency' => $this->getAttributeFromItemObject($o, 'listPriceCurrency'),
            'unitPrice' => $this->getAttributeFromItemObject($o, 'unitPrice'),
            'unitPriceCurrency' => $this->getAttributeFromItemObject($o, 'unitPriceCurrency'),
            'amount' => $this->getAttributeFromItemObject($o, 'amount'),
            'amountCurrency' => $this->getAttributeFromItemObject($o, 'amountCurrency'),
            'taxRate' => $this->getAttributeFromItemObject($o, 'taxRate'),
            'productId' => $this->getAttributeFromItemObject($o, 'productId'),
            'productName' => $this->getAttributeFromItemObject($o, 'productName'),
            'quantity' => $this->getAttributeFromItemObject($o, 'quantity'),
            'unitWeight' => $this->getAttributeFromItemObject($o, 'unitWeight'),
            'weight' => $this->getAttributeFromItemObject($o, 'weight'),
            'description' => $this->getAttributeFromItemObject($o, 'description'),
            'discount' => $this->getAttributeFromItemObject($o, 'discount'),
            'accountId' => $this->getAttributeFromItemObject($o, 'accountId'),
            'accountName' => $this->getAttributeFromItemObject($o, 'accountName')
        );

        $ignoreAttributeList = [
            'id', 'name', 'createdAt', 'modifiedAt', 'createdById', 'createdByName', 'modifiedById', 'modifiedByName',
            'quoteId','listPriceConverted', 'unitPriceConverted', 'amountConverted', 'deleted'
        ];

        $productAttributeList = $this->getEntityManager()->getEntity('Product')->getAttributeList();

        foreach ($productAttributeList as $attribute) {
            if (in_array($attribute, $ignoreAttributeList) || array_key_exists($attribute, $data)) continue;
            if (!$item->hasAttribute($attribute)) continue;
            $item->set($attribute, $this->getAttributeFromItemObject($o, $attribute));
        }
        $item->set($data);
    }

    protected function getAttributeFromItemObject($o, $attribute)
    {
        return isset($o->$attribute) ? $o->$attribute : null;
    }

    public function afterRemove(Entity $entity)
    {
        $quoteItemList = $this->getEntityManager()->getRepository('QuoteItem')->where(array(
            'quoteId' => $entity->id
        ))->find();

        foreach ($quoteItemList as $item) {
            $this->getEntityManager()->removeEntity($item);
        }
    }

}
