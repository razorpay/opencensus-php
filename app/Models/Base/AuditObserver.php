<?php

namespace RZP\Models\Base;

use App;
use Config;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\Audit\Core as AuditCore;

class AuditObserver
{
    const AUDIT_ID = 'audit_id';

    public function saving(Entity $entity)
    {
        try
        {
            if ($this->shouldUpdateAuditInfo($entity) === false)
            {
                return;
            }

            $auditId = Config::get(self::AUDIT_ID) ?? null;

            if (empty($auditId) == true)
            {
                $auditInfo = (new AuditCore())->create();

                $auditId = $auditInfo->getId();
            }

            Config::set(self::AUDIT_ID, $auditId);

            $entity->setAttribute(self::AUDIT_ID, $auditId);
        }
        catch (\Exception $e)
        {
            App::getFacadeRoot()['trace']->traceException(
                $e,
                Trace::ERROR,
                TraceCode::AUDIT_DETAILS_SAVE_REQUEST_FAILURE,
                [
                    'data' => $e->getMessage()
                ]);
        }
    }


    protected function shouldUpdateAuditInfo(Entity $entity): bool
    {
        $dirty = $entity->getDirty();

        if ((count($dirty) === 1) and
            (in_array(self::AUDIT_ID, $dirty) === true))
        {
            return false;
        }

        return true;
    }
}
