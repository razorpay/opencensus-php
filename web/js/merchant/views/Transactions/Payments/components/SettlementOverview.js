import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { findProviderDetails } from 'merchant/views/Transactions/Payments/components/PaymentOptimizerProvider';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';
import { trackEvents as trackEventsAction } from 'merchant/reducers/trackEvents';

function SettlementOverview({ payment, terminalProviders, user, page = '', trackEventsAction }) {
  const { id, utr, settled_by, provider: settlement_provider } = payment?.transaction?.settlement;
  let provider = null;
  if (settled_by !== 'razorpay') {
    provider = findProviderDetails(terminalProviders, settlement_provider, settled_by);
  }
  const trackEvent = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'Click - Settlement ID',
      eventLabel: `Payments`,
    });
    if (page && page !== '') {
      trackEventsAction({
        objectName: 'settlement id',
        actionName: 'click',
        properties: {
          settlement_id: id,
        },
        screen: page,
        toLumberjack: true,
      });
    }
  };
  return (
    <div style={{ marginTop: '5px' }}>
      <div>
        <Link to={`/settlements/${id}`} onClick={trackEvent}>
          <code>{id}</code>
        </Link>
      </div>
      {utr ? (
        <div className="row settlement-detail-row">
          <span className="col-xs-4">UTR</span>
          <span className="col-xs-8">{utr}</span>
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

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      trackEventsAction,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(SettlementOverview);
