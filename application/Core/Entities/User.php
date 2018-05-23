<?php


namespace Core\Entities;

class User extends \Core\Core\Entities\Person
{
    public function isAdmin($k)
    {
        if($k == 'import'){
            return TRUE;
        } else {
            return $this->get('isAdmin');
        }
        
    }

    public function isActive()
    {
        return $this->get('isActive');
    }

    public function isPortalUser()
    {
        return $this->get('isPortalUser');
    }

    public function getTeamIdList()
    {
        if (!$this->has('teamsIds')) {
            $this->loadLinkMultipleField('teams');
        }
        return $this->get('teamsIds');
    }

    public function loadAccountField()
    {
        if ($this->get('contactId')) {
            $contact = $this->getEntityManager()->getEntity('Contact', $this->get('contactId'));
            if ($contact && $contact->get('accountId')) {
                $this->set('accountId', $contact->get('accountId'));
                $this->set('accountName', $contact->get('accountName'));
            }
        }
    }
}
