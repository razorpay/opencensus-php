import AsyncButton from 'react-async-button';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

//TODO: Make this component generalized as per requirement later. Currently only used for subscriptions details view(invoice list)
export default props => {
  let {
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
    mode,
  } = props;

  let chargeAttemptsFailedText; // Charge attempts failed text
  let retryingInfo; // Whether further retries

  // For invoice in issued state, if it's subscription_status = halted then analyse whether 1st invoice or further invoices
  if (item.status === 'issued') {
    // For subscriptions in pending status
    if (subscriptionStatus === 'pending') {
      if (!item.subscription_status) {
        // Retrying info for pending state subscription
        let timeDiff =
          subscriptionchargeAt - Math.round(new Date().getTime() / 1000);
        timeDiff = Math.ceil(timeDiff / 3600);
        retryingInfo = `Retrying in ${timeDiff} hrs. `;
      }
      if (item.subscription_status !== 'halted') {
        chargeAttemptsFailedText = (
          <span>
            {authAttempts}{' '}
            {authAttempts > 1 ? 'charge attempts' : 'charge attempt'} failed.
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

  // Calculate time Diff to show 'due in' text
  let timeDiff;
  // billing_start in next_due invoice is charge_at of subscription. Check FE creation of next_due invoice. (Not api related)
  if (item.status === 'next_due' && item.billing_start) {
    timeDiff = item.billing_start - Math.round(new Date().getTime() / 1000);
  }

  // Check if row is clickable
  let isRowClickable = goToLink && !loading && activeSecEntityId !== item.id;
  let classNames = ['entity-detail-row'];

  if (item.id && activeSecEntityId === item.id) {
    classNames.push('active');
  }
  if (isRowClickable) {
    classNames.push('clickable');
  }

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
                {timeDiff > 0 && (
                  <span>
                    {' '}
                    (due in {Math.ceil(timeDiff / (3600 * 24))} days)
                  </span>
                )}
              </span>
            ) : (
              'Upcoming Payment'
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
                {index ? `Recurring payment # ${index}` : ''}
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
                <i class="icon icon-info-circle" /> {chargeAttemptsFailedText}
              </span>,
              <span key="retrying-info"> {retryingInfo}</span>,
            ]}
          {
            do {
              if (
                item.status === 'issued' &&
                (['active', 'pending', 'halted', 'completed'].indexOf(
                  subscriptionStatus
                ) > -1 ||
                  (subscriptionStatus === 'cancelled' &&
                    (index > 1 ||
                      (subscriptionType !== 3 && subscriptionType !== 1))))
              ) {
                <AsyncButton
                  class="btn-link no-padding"
                  text=" Attempt Charge"
                  pendingText="Attempting..."
                  onClick={() => onManualAttempt(item.id)}
                />;
              }
            }
          }
        </div>
      </div>

      {
        do {
          if (isRowClickable) {
            <span
              class="row-item icon icon-chevron-right"
              onClick={() => goToLink(item.id, index)}
            />;
          }
        }
      }
    </div>
  );
};
