import { Link } from 'react-router-dom';
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

  let retryingText;

  if (item.status === 'issued') {
    if (subscriptionStatus === 'halted') {
      retryingText = 'No retrying automatically.';
    } else if (subscriptionStatus === 'pending') {
      retryingText = 'Retrying in 23 hrs.';
    }
  }

  let timeDiff;
  // Issued at in next_due invoice is charge_at of subscription
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
                  Recurring payment #{index}
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
          {retryingText && [
            <span class="icon icon-info-circle" />,
            <span>
              {retryingText}
            </span>,
          ]}
          {item.status === 'issued' &&
            <a
              class="btn-link"
              onClick={() => {
                console.log('HELLO CHARGING... Make it < AsyncButton>!');
              }}
            >
              Manually Charge?
            </a>}
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
