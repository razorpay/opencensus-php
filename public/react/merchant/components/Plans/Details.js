import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedDetailRow from 'merchant/components/NestedDetailRow';
import { getIntervalCycle } from 'rzp/utils/rzp-utils';

export default ({ plan, isLoading, statusMsg }) => {
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="icon icon-plan text-info" />
              {' '}
              <strong>{plan.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <Alert type={statusMsg.type} message={statusMsg.message} />
                <EntityDetailRow label="Plan Name" value={plan.item.name} />

                <EntityDetailRow
                  label="Plan Description"
                  value={plan.item.description}
                />

                <EntityDetailRow
                  label="Billing Amount"
                  value={() => (
                    <Amount
                      currency={plan.item.currency}
                      value={plan.item.amount}
                    />
                  )}
                />

                <EntityDetailRow
                  label="Billing Frequency"
                  value={() => (
                    <span>{getIntervalCycle(plan.interval, plan.period)}</span>
                  )}
                />

                <EntityDetailRow
                  label="Created At"
                  value={() => (
                    <Time
                      value={plan.created_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />

                <NestedDetailRow label="Notes" value={plan.notes} />
              </div>
            </div>
          </div>}
    </div>
  );
};
