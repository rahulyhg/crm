<?php
/*********************************************************************************
 * The contents of this file are subject to the CoreCRM Advanced
 * Agreement ("License") which can be viewed at
 * http://www.espocrm.com/advanced-pack-agreement.
 * By installing or using this file, You have unconditionally agreed to the
 * terms and conditions of the License, and You may not use this file except in
 * compliance with the License.  Under the terms of the license, You shall not,
 * sublicense, resell, rent, lease, distribute, or otherwise  transfer rights
 * or usage to the software.
 * 
 * License ID: bcac485dee9efd0f36cf6842ad5b69b4
 ***********************************************************************************/

namespace Core\Modules\Advanced\Core\Google\Items;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\Forbidden;
use \Core\Core\Exceptions\NotFound;

class XMLFeedBase
{

    const LINK_REL_ALTERNATE = "alternate";
    const LINK_REL_FEED = "http://schemas.google.com/g/2005#feed";
    const LINK_REL_POST = "http://schemas.google.com/g/2005#post";
    const LINK_REL_BATCH = "http://schemas.google.com/g/2005#batch";
    const LINK_REL_SELF = "self";
    const LINK_REL_NEXT = "next";
    const LINK_REL_PREVIOUS = "previous";
    const LINK_REL_EDIT = "edit";

    protected $item;

    public function __construct($xml = '')
    {
        if (!empty($xml)) {
            $this->init($xml);
        }
    }

    public function init($xml)
    {
        if ($xml instanceof \DOMDocument) {
            $this->item = $xml;
        } else if ($xml instanceof \SimpleXMLElement) {
            $this->item = dom_import_simplexml($xml);
        } else {
            $this->item = \DOMDocument::loadXML($xml);
            if (empty($this->item)){
                throw new Error("Xml parse error");
            }
        }
    }

    public function getId()
    {
        return $this->getChildNodeValue('id');
    }

    protected function getChildNodeValue($nodeName)
    {
        $children = $this->item->getElementsByTagName($nodeName);
        return ($children->length > 0) ? $children->item(0)->nodeValue : '';
    }

    public function getTitle()
    {
        return $this->getChildNodeValue('title');
    }

    public function getEntries()
    {
        return $this->item->getElementsByTagName('entry');
    }

    protected function getLinkHref($rel)
    {
        $links = $this->item->getElementsByTagName('link');
        foreach ($links as $link) {
            if ($rel == $link->getAttribute('rel')) {
                return $link->getAttribute('href');
            }
        }
        return false;
    }

    public function getFeedLink()
    {
        return $this->getLinkHref(self::LINK_REL_FEED);
    }

    public function getPostLink()
    {
        return $this->getLinkHref(self::LINK_REL_POST);
    }

    public function getBatchLink()
    {
        return $this->getLinkHref(self::LINK_REL_BATCH);
    }

    public function getNextLink()
    {
        return $this->getLinkHref(self::LINK_REL_NEXT);
    }

    public function getPreviousLink()
    {
        return $this->getLinkHref(self::LINK_REL_PREVIOUS);
    }

    public function getEditLink()
    {
        return $this->getLinkHref(self::LINK_REL_EDIT);
    }

    public function asXML()
    {
        if ($this->item instanceof \DOMElement) {
            $xml = $this->item->ownerDocument->saveXML($this->item);
        } else {
            $xml = $this->item->saveXML();
        }
        return $xml;
    }
}
