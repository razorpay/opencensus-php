import { Link } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import EntityDetailList from 'merchant/components/EntityDetailList/List';
import { getFixedINRAmount, getIntervalCycle } from 'rzp/utils/rzp-utils';
import { SubscriptionStatusLabel } from 'merchant/components/StatusLabel';
import Definition from 'rzp/ui/Definition';

// Customer component
const getCustomerDetail = customer => (
  <Definition placeholder="--">
    {customer.name}
    {customer.email && <span>{customer.email}</span>}
    {customer.contact && <span>{customer.contact}</span>}
    {customer.id && <code>{customer.id}</code>}
  </Definition>
);

// Get plan description
const getDescription = (interval, period) => {
  switch (period) {
    case 'monthly':
      return `Billed Every ${interval} month`;
    case 'yearly':
      return `Billed Every ${interval} year`;
    case 'weekly':
      return `Billed Every ${interval} week`;
    default:
      return period;
  }
};

export default ({
  mode,
  subscription,
  plan,
  customer,
  isLoading,
  statusMsg,
  invoices,
  goToLink,
  activeSecEntityId,
  onCancelClick,
  onManualAttempt,
  onTestChargeAttempt,
}) => {
  let showTestChargeBtn =
    !isLoading &&
    onTestChargeAttempt &&
    (['authenticated', 'active', 'halted', 'pending'].indexOf(
      subscription.status
    ) > -1 ||
      (subscription.status === 'created' &&
        (subscription.type === 0 || subscription.type === 2)));

  const testModeMsg = {};
  if (showTestChargeBtn) {
    switch (subscription.status) {
      case 'halted':
        testModeMsg.btnLabel = 'Issue invoice';
        testModeMsg.infoMsg = ' Issue next scheduled invoice now. ';
        break;
      case 'pending':
        testModeMsg.btnLabel = 'Attempt Retry';
        testModeMsg.infoMsg =
          ' Attempt scheduled retry now for last issued invoice. ';
        break;
      case 'created':
        testModeMsg.btnLabel = 'Something new';
        testModeMsg.infoMsg = 'Something New';
        break;

      default:
        testModeMsg.btnLabel = 'Charge this now';
        testModeMsg.infoMsg =
          ' Attempt charge now for next scheduled invoice. ';
    }
  }

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="icon icon-refresh text-main icon--formal" />{' '}
            <strong>{subscription.id}</strong>
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />

              <EntityDetailRow label="Customer">
                {getCustomerDetail(customer)}
              </EntityDetailRow>

              <EntityDetailRow
                label="Plan"
                value={() => (
                  <div>
                    <Link to={`/plans/${subscription.plan_id}`}>
                      {subscription.plan_id}
                    </Link>
                    <div style={{ marginTop: '4px' }}>
                      <div class="label--primary">{plan.item.name}</div>
                      <div class="label--secondary">
                        {plan.item.description}
                      </div>
                      <div class="label--secondary">
                        {getDescription(plan.interval, plan.period)}
                      </div>
                    </div>
                  </div>
                )}
              />

              <EntityDetailRow
                label="Recurring Billing"
                value={() => (
                  <div>
                    <div class="label--primary">
                      <Amount
                        currency={plan.item.currency}
                        value={subscription.quantity * plan.item.unit_amount}
                      />
                    </div>
                    <small class="label--secondary">
                      {subscription.quantity} x{' '}
                      <Amount
                        currency={plan.item.currency}
                        value={plan.item.unit_amount}
                      />{' '}
                      per unit
                    </small>
                  </div>
                )}
              />

              <EntityDetailRow
                label="Status"
                value={() => (
                  <div>
                    <SubscriptionStatusLabel status={subscription.status} />

                    <span>
                      {['cancelled', 'completed', 'expired'].indexOf(
                        subscription.status
                      ) === -1 ? (
                        <button class="btn-link" onClick={onCancelClick}>
                          Cancel Subscription
                        </button>
                      ) : null}
                    </span>
                  </div>
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
              <EntityDetailRow
                label="Next Due on"
                value={() => <Time value={subscription.charge_at} />}
              />

              {showTestChargeBtn && (
                <div
                  class={`alert alert-warning custom-banner ${
                    subscription.status !== 'halted' ? 'arrow-up' : ''
                  }`}
                >
                  <button
                    class="btn btn-default"
                    onClick={() => onTestChargeAttempt(subscription.id)}
                  >
                    {testModeMsg.btnLabel}
                  </button>
                  <div class="info">
                    <b>Test Mode:</b>
                    {testModeMsg.infoMsg}
                    <a
                      href="https://razorpay.com/docs/subscriptions/routes/#subscription"
                      target="_blank"
                    >
                      View docs >
                    </a>{' '}
                  </div>
                </div>
              )}

              <EntityDetailList
                mode={mode}
                title="Invoices detail"
                goToLink={goToLink}
                subTitle={
                  subscription.total_count &&
                  `${subscription.paid_count} of ${
                    subscription.total_count
                  } invoices charged`
                }
                moreAfterlimit={3}
                error={invoices.error}
                items={invoices.items}
                activeSecEntityId={activeSecEntityId}
                loading={invoices.loading}
                onManualAttempt={onManualAttempt}
                subscriptionStatus={subscription.status}
                subscriptionType={subscription.type}
                subscriptionchargeAt={subscription.charge_at}
                authAttempts={subscription.auth_attempts}
                subscriptionId={subscription.id}
              />

              <NestedEntityDetailRow label="Notes" value={subscription.notes} />

              <hr />
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
