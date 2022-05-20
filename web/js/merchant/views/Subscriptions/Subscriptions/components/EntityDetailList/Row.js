import AsyncButton from 'react-async-button';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import DocsLink from 'merchant/components/DocsLink';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

//TODO: Make this component generalized as per requirement later. Currently only used for subscriptions details view(invoice list)
export default function EntityDetailRow(props) {
  const {
    goToLink,
    item,
    index,
    loading,
    activeSecEntityId,
    subscriptionStatus,
    subscriptionType,
    authAttempts,
    isInvoiceWithAttemptsFailed,
    subscriptionchargeAt,
    onManualAttempt,
    isUpfront,
    subscriptionId,
    paymentMethod,
  } = props;

  /*
   * chargeAttemptsFailedText - Charge attempts failed text
   * retryingInfo             - Whether further retries
   * timeDiff                 - Calculate time Diff to show 'due in' text
   */
  let chargeAttemptsFailedText, retryingInfo, timeDiff;

  const isEmandatePayment = paymentMethod === 'emandate';
  const isIssued = item.status === 'issued';

  // For invoice in issued state, if it's subscription_status = halted then analyse whether 1st invoice or further invoices
  if (item.status === 'issued') {
    // For subscriptions in pending status
    if (subscriptionStatus === 'pending') {
      if (!item.subscription_status) {
        // Retrying info for pending state subscription
        let timeDiffVal = subscriptionchargeAt - Math.round(new Date().getTime() / 1000);
        timeDiffVal = Math.ceil(timeDiffVal / 3600);
        if (timeDiffVal > 0) retryingInfo = `Retrying in ${timeDiffVal} hrs. `;
      }
      if (item.subscription_status !== 'halted') {
        chargeAttemptsFailedText = (
          <span>
            {authAttempts} {authAttempts > 1 ? 'charge attempts' : 'charge attempt'} failed.
          </span>
        );
      }
    }

    if (item.subscription_status === 'halted') {
      if (isInvoiceWithAttemptsFailed === 2) {
        // To handle situation where such invoices were created in halted state but now invoice is pending (,active, complete, etc)
        /*
          chargeAttemptsFailedText = 'No auto-charge attempted. ';
        */

        // Temp change until api supports properly
        chargeAttemptsFailedText = <span>Invoice unpaid. </span>;
      } else if (isInvoiceWithAttemptsFailed === 1) {
        // retryingInfo = 'Not retrying automatically';
        // To handle situation where such invoices were created in halted state but now invoice is pending (,active, complete, etc)
        /*
          chargeAttemptsFailedText = (
            <span>All auto-charge attempts failed. </span>
          );
        */
        // Temp change until api supports properly
        chargeAttemptsFailedText = <span>Invoice unpaid. </span>;
      }
    }
  }

  // billing_start in next_due invoice is charge_at of subscription. Check FE creation of next_due invoice. (Not api related)
  if (item.status === 'next_due' && item.billing_start) {
    timeDiff = item.billing_start - Math.round(new Date().getTime() / 1000);
  }

  // Check if row is clickable
  const isRowClickable = goToLink && !loading && activeSecEntityId !== item.id;
  const classNames = ['entity-detail-row'];

  if (item.id && activeSecEntityId === item.id) {
    classNames.push('active');
  }
  if (isRowClickable) {
    classNames.push('clickable');
  }

  const isChargedInvoice = item.notes && item.notes.type && item.notes.type == 'upgrade';
  const showAttemptChargeCTA =
    item.status === 'issued' &&
    (['active', 'pending', 'halted', 'completed'].indexOf(subscriptionStatus) > -1 ||
      (subscriptionStatus === 'cancelled' &&
        (index > 1 || (subscriptionType !== 3 && subscriptionType !== 1))));

  return (
    <div
      class={classNames.join(' ')}
      onClick={() => {
        if (isRowClickable) {
          goToLink(item.id, index);
        }
      }}
    >
      <div class="row-item content">
        <div class="detail-row">
          <div class="row-element left">
            {loading ? (
              <PlaceholderLoader style={{ width: '70%' }} />
            ) : item.billing_start ? (
              <span class="label--primary">
                <Time value={item.billing_start} format="MMM DD, YYYY" />
                {timeDiff > 0 && <span> (due in {Math.ceil(timeDiff / (3600 * 24))} days)</span>}
              </span>
            ) : isChargedInvoice ? (
              'Updated Invoice'
            ) : (
              'Upcoming Invoice'
            )}
          </div>
          <div class="row-element right">
            {loading ? (
              <PlaceholderLoader style={{ width: '45%' }} />
            ) : (
              <Amount currency={item.currency} value={item.amount} />
            )}
          </div>
        </div>

        <div class="detail-row">
          <div class="row-element left">
            {loading ? (
              <PlaceholderLoader style={{ width: '60%', height: '10px' }} />
            ) : (
              <span class="label--secondary">
                {index
                  ? isChargedInvoice
                    ? 'Charged due to subscription update'
                    : `Recurring payment # ${index}`
                  : ''}
                {index && isUpfront ? ', ' : ''}
                {isUpfront ? 'Upfront Amount' : ''}
              </span>
            )}
          </div>
          <div class="row-element right">
            {loading ? (
              <PlaceholderLoader style={{ width: '30%' }} />
            ) : (
              <span class="tag">
                <InvoiceStatusLabel status={item.status} />
              </span>
            )}
          </div>
        </div>

        <div class="detail-row">
          {item.id &&
            (chargeAttemptsFailedText || retryingInfo) && [
              <span key="retrying-attempts" class="text-danger">
                <i class="i i-info-circle" /> {chargeAttemptsFailedText}
              </span>,
              <span key="retrying-info"> {retryingInfo}</span>,
            ]}
          {showAttemptChargeCTA && (
            <AsyncButton
              class="btn-link no-padding"
              text=" Attempt Charge"
              pendingText="Attempting..."
              onClick={() => onManualAttempt(item.id, subscriptionId)}
            />
          )}
        </div>

        {isEmandatePayment && isIssued && (
          <div class="details-row eMandate-status">
            <i class="i i-info-outline m-r" /> eMandate payment status can take 24 - 48 hours to
            confirm.
            <DocsLink
              title="Learn more"
              url="https://razorpay.com/docs/payments/subscriptions/payment-retries/#retry-model-for-emandate"
            />
          </div>
        )}
      </div>

      {isRowClickable && (
        <span class="row-item i i-chevron-right" onClick={() => goToLink(item.id, index)} />
      )}
    </div>
  );
}
