<?php
 

namespace Core\Modules\Crm\Controllers;

use \Core\Core\Exceptions\Error;
use \Core\Core\Exceptions\BadRequest;
    
class Target extends \Core\Core\Controllers\Record
{
    
    public function actionConvert($params, $data)
    {    
        
        if (empty($data['id'])) {
            throw new BadRequest();
        }
        $entity = $this->getRecordService()->convert($data['id']);
        
        if (!empty($entity)) {
            return $entity->toArray();
        }
        throw new Error();        
    }

}
