import { Link } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedDetailRow from 'merchant/components/NestedDetailRow';
import { getFixedINRAmount, getIntervalCycle } from 'rzp/utils/rzp-utils';
import { SubscriptionStatusLabel } from 'merchant/components/StatusLabel';

export default ({ subscription, plan, customer, isLoading, statusMsg }) => {
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
                  label="Customer"
                  value={customer.displayName}
                />

                <EntityDetailRow
                  label="Plan"
                  value={() => (
                    <div>
                      <Link to={`/plans/${subscription.plan_id}`}>
                        {plan.item.name}
                      </Link>
                      <div class="text-muted">
                        <small>{plan.item.description}</small>
                      </div>
                    </div>
                  )}
                />

                <EntityDetailRow
                  label="Recurring Billing"
                  value={() => (
                    <div>
                      <div>
                        <Amount
                          currency={plan.item.currency}
                          value={subscription.quantity * plan.item.unit_amount}
                        />
                      </div>
                      <small class="text-muted">
                        {subscription.quantity}
                        {' '}
                        x
                        {' '}
                        <Amount
                          currency={plan.item.currency}
                          value={plan.item.unit_amount}
                        />
                        {' '}
                        per unit
                      </small>
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
