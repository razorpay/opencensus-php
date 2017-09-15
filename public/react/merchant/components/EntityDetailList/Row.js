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
    onManualAttempt,
    isUpfront,
  } = props;
  console.log(
    'ITEM...',
    loading,
    subscriptionStatus,
    item.issued_at,
    item.currency,
    item.amount / 100,
    item.status,
    index
  );

  //TODO: Get from api
  item.attempts = 1;
  item.next_try = 4;

  let retryingText;

  if (true || item.status === 'issued') {
    if (subscriptionStatus === 'halted') {
      retryingText = 'No retrying automatically. ';
    } else if (true || subscriptionStatus === 'pending') {
      retryingText = `Retrying in ${item.next_try} Hours. `; // TODO: Calculate the time remaining
    }
  }

  let timeDiff;
  // issued_at in next_due invoice is charge_at of subscription. Check FE creation of next_due invoice. (Not api related)
  if (item.status === 'next_due' && item.issued_at) {
    timeDiff = item.issued_at - Math.round(new Date().getTime() / 1000);
  }

  return (
    <div
      class={`entity-detail-row ${item.id && activeSecEntityId === item.id
        ? 'active'
        : ''}`}
    >
      <div class="row-item content">
        <div class="detail-row">
          <div class="row-element left">
            {loading
              ? <PlaceholderLoader style={{ width: '70%' }} />
              : item.issued_at
                ? <span class="label--primary">
                    <Time value={item.issued_at} format="MMM DD, YYYY" />
                    {timeDiff > 0 &&
                      <span>
                        {' '}(due in {Math.ceil(timeDiff / (3600 * 24))} days)
                      </span>}
                  </span>
                : 'Upcoming Payment'}
          </div>
          <div class="row-element right">
            {loading
              ? <PlaceholderLoader style={{ width: '45%' }} />
              : <Amount currency={item.currency} value={item.amount} />}
          </div>
        </div>

        <div class="detail-row">
          <div class="row-element left">
            {loading
              ? <PlaceholderLoader style={{ width: '60%', height: '10px' }} />
              : <span class="label--secondary">
                  {index ? `Recurring payment # ${index}` : ''}
                  {index && isUpfront ? ', ' : ''}
                  {isUpfront ? 'Upfront Amount' : ''}
                </span>}
          </div>
          <div class="row-element right">
            {loading
              ? <PlaceholderLoader style={{ width: '30%' }} />
              : <span class="tag">
                  <InvoiceStatusLabel status={item.status} />
                </span>}
          </div>
        </div>

        <div class="detail-row">
          {item.id &&
          retryingText && [
            <span key="info" class="text-danger">
              <i class="icon icon-info-circle" />{' '}
              {item.attempts > 1
                ? item.attempts + ' charge attempts '
                : item.attempts + ' charge attempt'}{' '}
              failed.
            </span>,
            <span key="info-notice">
              {' '}{retryingText}
            </span>,
          ]}
          {
            do {
              if (
                item.status === 'issued' &&
                ['active', 'pending', 'authenticated', 'halted'].indexOf(
                  subscriptionStatus
                ) > -1
              ) {
                <AsyncButton
                  class="btn-link no-padding"
                  text=" Manually Charge?"
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
          if (goToLink && !loading && activeSecEntityId !== item.id) {
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
