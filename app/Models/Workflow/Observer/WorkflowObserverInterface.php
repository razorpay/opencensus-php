<?php


namespace RZP\Models\Workflow\Observer;

interface WorkflowObserverInterface
{
    public function onApprove(array $observerData);

    public function onClose(array $observerData);

    public function onReject(array $observerData);

    public function onCreate(array $observerData);

    public function onExecute(array $observerData);
}
