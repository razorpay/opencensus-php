import { Link } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedDetailRow from 'merchant/components/NestedDetailRow';
import { getIntervalCycle } from 'rzp/utils/rzp-utils';
import { SubscriptionStatusLabel } from 'merchant/components/StatusLabel';

export default ({ subscription, isLoading, statusMsg }) => {
  debugger;
  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="icon icon-account-balance text-success" />
              {' '}
              <strong>{subscription.id}</strong>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <Alert type={statusMsg.type} message={statusMsg.message} />
                <EntityDetailRow
                  label="Plan"
                  value={() => (
                    <Link to={`/plans/${subscription.plan_id}`}>
                      {subscription.plan_id}
                    </Link>
                  )}
                />

                <EntityDetailRow
                  label="Recurring Billing"
                  value={() => (
                    <div>
                      <small class="text-muted">{subscription.quantity}</small>
                    </div>
                  )}
                />

                <EntityDetailRow
                  label="Status"
                  value={() => (
                    <SubscriptionStatusLabel status={subscription.status} />
                  )}
                />

                <EntityDetailRow
                  label="Created At"
                  value={() => (
                    <Time
                      value={subscription.created_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />

                <NestedDetailRow label="Notes" value={subscription.notes} />
              </div>
            </div>
          </div>}
    </div>
  );
};
