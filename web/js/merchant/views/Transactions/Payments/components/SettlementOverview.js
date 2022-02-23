import { Link } from 'react-router-dom';

function SettlementOverview({ payment }) {
  return (
    <div style={{ marginTop: '5px' }}>
      <div>
        <Link
          to={`/settlements/${payment.transaction.settlement.id}`}
          onClick={() => {
            window.rzpAnalytics?.({
              eventCategory: 'Settlement Revamp',
              eventAction: 'Click - Settlement ID',
              eventLabel: `Payments`,
            });
          }}
        >
          <code>{payment.transaction.settlement.id}</code>
        </Link>
      </div>
      {payment.transaction.settlement.utr ? (
        <div class="settlement-detail-row">
          <span>UTR</span>
          <span>{payment.transaction.settlement.utr}</span>
        </div>
      ) : null}
    </div>
  );
}

export default SettlementOverview;
