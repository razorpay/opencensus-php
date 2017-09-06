import { Link } from 'react-router-dom';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

export default props => {
  let { goToLink, item, index, loading, activeSecEntityId } = props;
  console.log(
    'ITEM...',
    loading,
    item.issued_at,
    item.currency,
    item.amount / 100,
    item.status,
    index
  );

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
              : <span class="label--primary">
                  <Time value={item.issued_at} format="MMM DD, YYYY" />
                </span>}
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
