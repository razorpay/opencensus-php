import React from 'react';
import { Link, NavLink } from 'react-router-dom';

import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import Definition from 'rzp/ui/Definition';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';

import Button from 'component/Button';

import ShowWhen from 'merchant/components/ShowWhen';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import EntityDetailList from 'merchant/components/EntityDetailList/List';
import { SubscriptionStatusLabel } from 'merchant/components/StatusLabel';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { changeData } from 'merchant/containers/Subscriptions/SubscriptionLinks/Update/Review';
import Tooltip from 'rzp/ui/Tooltip';

import { trackClickDuplicateSubscription } from 'merchant/containers/Subscriptions/ga';

export default props => {
  const {
    mode,
    plan,
    customer,
    invoices,
    goToLink,
    isLoading,
    statusMsg,
    isSideView,
    creditNotes,
    subscription,
    onCancelClick,
    onManualAttempt,
    scheduledChanges,
    activeSecEntityId,
    onTestChargeAttempt,
    cancelUpdateSubscription,
  } = props;

  let showTestChargeBtn =
    !isLoading &&
    onTestChargeAttempt &&
    (['authenticated', 'active', 'halted', 'pending'].indexOf(
      subscription.status
    ) > -1 ||
      subscription.status === 'created');

  const testModeMsg = getTestModeMessage(subscription.status) || {};

  const allowUpdateSubscription = ['authenticated', 'active'].includes(
    subscription.status
  );

  const hideCancelUpdate = ['cancelled', 'completed', 'expired'].includes(
    subscription.status
  );

  const style = {
    marginRight: isSideView ? 30 : 0,
  };

  const subscriptionChanges =
    !scheduledChanges.isLoading &&
    scheduledChanges.data &&
    changeData({
      prevPlan: plan,
      fields: scheduledChanges.data,
      prevSubscription: subscription,
      updatedPlan: scheduledChanges.plan,
    });

  const subTitle =
    subscription.total_count &&
    `${subscription.paid_count} of ${
      subscription.total_count
    } invoices charged`;

  return (
    <div class="content-wrapper content-sm txn-details SubscriptionLinks--Details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-refresh text-main icon--formal" />{' '}
            <strong>{subscription.id}</strong>
            {allowUpdateSubscription && (
              <div class="pull-right" style={style}>
                <NavLink
                  class="btn btn-primary"
                  to={`/subscriptions/${subscription.id}/edit`}
                >
                  Update
                </NavLink>
              </div>
            )}
            <div className="btn-toolbar pull-right">
              <NavLink
                onClick={trackClickDuplicateSubscription}
                class="btn Button--primary--invert"
                to={`/subscriptions/new?duplicate_id=${subscription.id}`}
              >
                <i className="i i-copy" />
                <Tooltip theme="dark">Duplicate Subscription</Tooltip>
              </NavLink>
            </div>
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />

              <EntityDetailRow label="Customer">
                {getCustomerDetail(customer)}
              </EntityDetailRow>

              <EntityDetailRow label="Plan">
                <div>
                  <Link to={`/plans/${subscription.plan_id}`}>
                    {subscription.plan_id}
                  </Link>
                  <div style={{ marginTop: '4px' }}>
                    <div class="label--primary">{plan.item.name}</div>
                    <div class="label--secondary">{plan.item.description}</div>
                    <div class="label--secondary">
                      {getDescription(plan.interval, plan.period)}
                    </div>
                  </div>
                </div>
              </EntityDetailRow>

              <EntityDetailRow label="Link">
                <CopyLink url={subscription.short_url} />
              </EntityDetailRow>

              <EntityDetailRow label="Recurring Billing">
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
              </EntityDetailRow>

              <EntityDetailRow label="Status">
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
              </EntityDetailRow>

              <EntityDetailRow label="Created At">
                <Time
                  value={subscription.created_at}
                  format="DD MMM YYYY, hh:mm:ss a"
                />
              </EntityDetailRow>

              <EntityDetailRow label="Next Due on">
                <Time value={subscription.charge_at} />
              </EntityDetailRow>

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
                    <ShowWhen
                      additionalCondition={user =>
                        user.isOrgAllowedFunctionality('external_links')
                      }
                    >
                      <a
                        href="https://razorpay.com/docs/subscriptions"
                        target="_blank"
                      >
                        View docs >
                      </a>{' '}
                    </ShowWhen>
                  </div>
                </div>
              )}

              {scheduledChanges.isLoading && <PlaceholderLoader />}

              {!scheduledChanges.isLoading &&
                scheduledChanges.data && (
                  <div
                    class="update-subscription-preview alert alert-warning custom-banner"
                    style={{ width: '100%' }}
                  >
                    <div>
                      The subscription will be updated on{' '}
                      {moment
                        .unix(subscription.start_at)
                        .format('DD MMM, YYYY')}
                      {!hideCancelUpdate && (
                        <Button.Transparent
                          onClick={cancelUpdateSubscription(subscription.id)}
                          class="pull-right"
                        >
                          Cancel Update
                        </Button.Transparent>
                      )}
                    </div>
                    <ContentToggler>
                      <span>View Details</span>
                      <div className="full-width-item">
                        <strong>Update Summary</strong>
                        <UpdatedSubscriptionPreview
                          data={subscriptionChanges}
                        />
                      </div>
                    </ContentToggler>
                  </div>
                )}

              <EntityDetailList
                mode={mode}
                moreAfterlimit={3}
                subTitle={subTitle}
                error={invoices.error}
                items={invoices.items}
                title="Invoices detail"
                creditNotes={creditNotes}
                loading={invoices.loading}
                goToLink={goToLink('invoice')}
                subscriptionId={subscription.id}
                onManualAttempt={onManualAttempt}
                subscriptionType={subscription.type}
                activeSecEntityId={activeSecEntityId}
                authAttempts={subscription.auth_attempts}
                subscriptionStatus={subscription.status}
                subscriptionchargeAt={subscription.charge_at}
                creditNotesGoToLink={goToLink('credit_note')}
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

const getTestModeMessage = status => {
  switch (status) {
    case 'halted': {
      return {
        btnLabel: 'Issue invoice',
        infoMsg: ' Issue next scheduled invoice now. ',
      };
    }

    case 'pending': {
      return {
        btnLabel: 'Attempt Retry',
        infoMsg: ' Attempt scheduled retry now for last issued invoice. ',
      };
    }

    case 'created': {
      return {
        btnLabel: 'Start Subscription',
        infoMsg: ' Make the first payment to start the subscription. ',
      };
    }

    default: {
      return {
        btnLabel: 'Charge this now',
        infoMsg: ' Attempt charge now for next scheduled invoice. ',
      };
    }
  }
};

const UpdatedSubscriptionPreview = ({ data }) => {
  return data.map(({ heading, changes }) => (
    <div class="changed-values">
      <div>
        {changes.map(e => (
          <div class="current-change" key={e.current}>
            <b>{heading} :</b>
            <span>
              {e.current}
              <i class="i i-arrow-forward" />
              {e.change}
            </span>
          </div>
        ))}
      </div>
    </div>
  ));
};

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
