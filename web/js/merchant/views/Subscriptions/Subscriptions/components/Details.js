import React from 'react';
import moment from 'moment';
import { Link, NavLink } from 'react-router-dom';

import Button from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import Alert from 'common/ui/Forms/Alert';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Tooltip from 'common/ui/Tooltip';
import { titleCase } from 'common/utils/rzp-utils';
import CopyLink from 'merchant/components/CopyLink';
import { DocLink } from 'merchant/components/DocsLink';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import { SubscriptionStatusLabel } from 'merchant/components/StatusLabel';
import { changeData } from 'merchant/views/Subscriptions/SubscriptionLinks/Update/Review';
import EntityDetailList from 'merchant/views/Subscriptions/Subscriptions/components/EntityDetailList/List';
import { trackClickDuplicateSubscription } from 'merchant/views/Subscriptions/Subscriptions/ga';
import analytics from 'merchant/views/Subscriptions/analytics';

export default function SubscriptionDetails(props) {
  const {
    mode,
    plan,
    selectedOffer = {},
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
    onClickPauseAndResume,
    isSubscriptionOffersEnabled,
    removeOffer,
  } = props;

  const showTestChargeBtn =
    !isLoading &&
    onTestChargeAttempt &&
    (['authenticated', 'active', 'halted', 'pending'].indexOf(subscription.status) > -1 ||
      subscription.status === 'created');

  const testModeMsg = getTestModeMessage(subscription) || {};

  const allowUpdateSubscription =
    ['authenticated', 'active'].includes(subscription.status) &&
    subscription.payment_method !== 'upi' &&
    subscription.payment_method !== 'emandate';

  const hideCancelUpdate = ['cancelled', 'completed', 'expired'].includes(subscription.status);

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
    `${subscription.paid_count} of ${subscription.total_count} invoices charged`;

  const showCancelBtn = ['cancelled', 'completed', 'expired'].indexOf(subscription.status) === -1;

  const showPauseAndResumeBtn = ['active', 'paused'].indexOf(subscription.status) !== -1;

  const showOfferCancelBtn =
    ['cancelled', 'completed', 'expired'].indexOf(subscription.status) === -1;

  return (
    <div className="content-wrapper content-sm txn-details SubscriptionLinks--Details">
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            <i className="i i-refresh text-main icon--formal" /> <strong>{subscription.id}</strong>
            {allowUpdateSubscription && (
              <div className="pull-right" style={style}>
                <NavLink
                  className="btn btn-primary"
                  to={`/subscriptions/${subscription.id}/edit`}
                  onClick={() => {
                    analytics.track('subscription.update.initiate');
                  }}
                >
                  Update
                </NavLink>
              </div>
            )}
            <div className="btn-toolbar pull-right">
              <NavLink
                onClick={() => {
                  trackClickDuplicateSubscription();
                  analytics.track('subscription.clone.start');
                }}
                className="btn Button--primary--invert"
                to={`/subscriptions/new?duplicate_id=${subscription.id}`}
              >
                <i className="i i-copy" />
                <Tooltip theme="dark">Duplicate Subscription</Tooltip>
              </NavLink>
            </div>
          </div>

          <div className="SliderPanel__Body">
            <div className="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />

              <EntityDetailRow label="Customer">{getCustomerDetail(customer)}</EntityDetailRow>

              <EntityDetailRow label="Plan">
                <div>
                  <Link to={`/plans/${subscription.plan_id}`}>{subscription.plan_id}</Link>
                  <div style={{ marginTop: '4px' }}>
                    <div className="label--primary">{plan.item.name}</div>
                    <div className="label--secondary">{plan.item.description}</div>
                    <div className="label--secondary">
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
                  <div className="label--primary">
                    <Amount
                      currency={plan.item.currency}
                      value={subscription.quantity * plan.item.unit_amount}
                    />
                  </div>
                  <small className="label--secondary">
                    {subscription.quantity} x{' '}
                    <Amount currency={plan.item.currency} value={plan.item.unit_amount} /> per unit
                  </small>
                </div>
              </EntityDetailRow>

              <EntityDetailRow label="Status">
                <div>
                  <SubscriptionStatusLabel status={subscription.status} />

                  <span>
                    {showPauseAndResumeBtn && (
                      <button
                        className="btn btn-default btn-xs m-l"
                        onClick={onClickPauseAndResume}
                      >
                        {subscription.status === 'paused' ? 'Resume' : 'Pause'}
                      </button>
                    )}

                    {showCancelBtn && (
                      <button
                        className={
                          showPauseAndResumeBtn ? 'm-l btn btn-default btn-xs' : 'btn-link'
                        }
                        onClick={onCancelClick}
                      >
                        Cancel
                      </button>
                    )}
                  </span>
                </div>
              </EntityDetailRow>

              <EntityDetailRow label="Payment Method">
                <div className="payment_method">
                  {titleCase(subscription.payment_method) || '--'}
                </div>
                {(subscription.payment_details || []).map((ele, i) => (
                  <div key={i}>{ele}</div>
                ))}
              </EntityDetailRow>

              {isSubscriptionOffersEnabled && (
                <EntityDetailRow label="Offer">
                  {/* TODO: Add offers full details */}
                  {subscription.offer_id ? (
                    <>
                      {showOfferCancelBtn && (
                        <div>
                          <Link to={`/offers/${subscription.offer_id}`}>
                            {subscription.offer_id}
                          </Link>{' '}
                          <button className="m-l btn btn-default btn-xs" onClick={removeOffer}>
                            Remove
                          </button>
                        </div>
                      )}
                      <div className="label--primary">
                        {selectedOffer ? selectedOffer.name : ''}
                      </div>
                      {subscription.offer && (
                        <div className="label--secondary">
                          Redeemed on {subscription.offer.applied_count}/
                          {subscription.offer.cycle_count} cycles(s)
                        </div>
                      )}
                    </>
                  ) : (
                    '--'
                  )}
                </EntityDetailRow>
              )}

              <EntityDetailRow label="Created At">
                <Time value={subscription.created_at} format="DD MMM YYYY, hh:mm:ss a" />
              </EntityDetailRow>

              <EntityDetailRow label="Next Due on">
                <Time value={subscription.charge_at} />
              </EntityDetailRow>

              {showTestChargeBtn && (
                <div
                  className={`alert alert-warning custom-banner ${
                    subscription.status !== 'halted' ? 'arrow-up' : ''
                  }`}
                >
                  <button
                    className="btn btn-default"
                    onClick={() => onTestChargeAttempt(subscription.id)}
                  >
                    {testModeMsg.btnLabel}
                  </button>
                  <div className="info">
                    <b>Test Mode:</b>
                    {testModeMsg.infoMsg}
                    <ShowWhen
                      additionalCondition={(user) =>
                        user.isOrgAllowedFunctionality('external_links')
                      }
                    >
                      <DocLink href="https://razorpay.com/docs/subscriptions" target="_blank">
                        View docs &gt;
                      </DocLink>{' '}
                    </ShowWhen>
                  </div>
                </div>
              )}

              {scheduledChanges.isLoading && <PlaceholderLoader />}

              {!scheduledChanges.isLoading && scheduledChanges.data && (
                <div
                  className="update-subscription-preview alert alert-warning custom-banner"
                  style={{ width: '100%' }}
                >
                  <div>
                    The subscription will be updated on{' '}
                    {moment.unix(scheduledChanges.data.change_scheduled_at).format('DD MMM, YYYY')}
                    {!hideCancelUpdate && (
                      <Button.Transparent
                        onClick={cancelUpdateSubscription(subscription.id)}
                        className="pull-right"
                      >
                        Cancel Update
                      </Button.Transparent>
                    )}
                  </div>
                  <ContentToggler>
                    <span>View Details</span>
                    <div className="full-width-item">
                      <strong>Update Summary</strong>
                      <UpdatedSubscriptionPreview data={subscriptionChanges} />
                    </div>
                  </ContentToggler>
                </div>
              )}

              <EntityDetailList
                mode={mode}
                moreAfterlimit={3}
                subTitle={subTitle}
                error={invoices.error}
                paymentMethod={subscription.payment_method}
                cardMandateID={subscription.payment_method}
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
}

function getTestModeMessage(subscription) {
  const chargeThisNowBtn = {
    btnLabel: 'Charge this now',
    infoMsg: ' Attempt charge now for next scheduled invoice. ',
  };

  if (
    subscription.payment_method === 'emandate' &&
    subscription.status === 'created' &&
    subscription.pay_now_enabled
  ) {
    return chargeThisNowBtn;
  }

  switch (subscription.status) {
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
      return chargeThisNowBtn;
    }
  }
}

function UpdatedSubscriptionPreview({ data }) {
  return data.map(({ heading, changes }, index) => (
    <div className="changed-values" key={index}>
      <div>
        {changes.map((e) => (
          <div className="current-change" key={e.current}>
            <b>{heading} :</b>
            <span>
              {e.current}
              <i className="i i-arrow-forward" />
              {e.change}
            </span>
          </div>
        ))}
      </div>
    </div>
  ));
}

// Customer component
function getCustomerDetail(customer) {
  return (
    <Definition placeholder="--">
      {customer.name}
      {customer.email && <span>{customer.email}</span>}
      {customer.contact && <span>{customer.contact}</span>}
      {customer.id && <code>{customer.id}</code>}
    </Definition>
  );
}

// Get plan description
function getDescription(interval, period) {
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
}
