import { Link } from 'react-router-dom';
import { findProviderDetails } from 'merchant/views/Transactions/Payments/components/PaymentOptimizerProvider';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';

function SettlementOverview({ payment, terminalProviders, user }) {
  let provider = null;
  if (payment.transaction.settlement?.settled_by !== 'razorpay') {
    provider = findProviderDetails(
      terminalProviders,
      payment.transaction.settlement?.provider,
      payment.transaction.settlement?.settled_by,
    );
  }
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
        <div className="row settlement-detail-row">
          <span className="col-xs-4">UTR</span>
          <span className="col-xs-8">{payment.transaction.settlement.utr}</span>
        </div>
      ) : null}
      {user?.isSingleReconEnabled && user?.isOptimizerEnabled && provider ? (
        <div className="row settlement-detail-row">
          <span className="col-xs-4">Settled by</span>
          <span className="col-xs-8">
            <img src={gatewayLogos[provider.Gateway]} />
            {provider.Provider_name}
          </span>
        </div>
      ) : null}
    </div>
  );
}

export default SettlementOverview;
