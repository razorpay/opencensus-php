import { Link } from 'react-router-dom';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { findProviderDetails } from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';
import { gatewayLogos } from 'merchant/views/Navigator/components/util';
import { trackEvents as trackEventsAction } from 'merchant/reducers/trackEvents';
import ShowWhen from 'merchant/components/ShowWhen';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import LoaderDots from 'common/ui/LoaderDots';
// styles
import './Payments.styl';
import { getInitiatePointAndPageAndScreenName } from 'merchant/views/Transactions/v1/utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { SelfServeActionPages } from 'common/constant/enums';

function SettlementOverview({
  payment,
  terminalProviders,
  user,
  page = '',
  trackEventsAction,
  customSettlementLoading,
  adminAsMerchant,
  showCustomSettlDetails,
  bankSettleStatus,
  openedFrom,
  settlement_id,
}) {
  const { id, utr, settled_by, provider: settlement_provider } = payment?.transaction?.settlement;
  let provider = null;
  if (settled_by !== 'razorpay') {
    provider = findProviderDetails(terminalProviders, settlement_provider, settled_by);
  }

  const { initiatePage, screen, page: _page } = getInitiatePointAndPageAndScreenName();

  let INIT_POINT = 'payment-details';

  if (page === 'Refund Detail') {
    INIT_POINT = 'refund-details';
  } else if (page === 'Reversal Detail') {
    INIT_POINT = 'reversal-details';
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
      const selfServeInitiateData = {
        selfServeAction: 'Settlement Details Fetched',
        page: _page,
        screen,
        props: {
          initiatePoint: INIT_POINT,
        },
      };
      if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;
      if (
        id !== settlement_id &&
        openedFrom !== 'settlement-details' &&
        initiatePage !== SelfServeActionPages.SettlementsReversals
      ) {
        selfServeTrackInitiate(selfServeInitiateData);
      }
    }
  };
  return (
    <div className="settlement-overview-container">
      <div>
        {user?.hideForNIASupportRole ? (
          <Link
            to={`/settlements/${id}?init_point=${INIT_POINT}&init_page=${initiatePage}`}
            onClick={trackEvent}
          >
            <code>{id}</code>
          </Link>
        ) : (
          <span>{id}</span>
        )}
      </div>
      <ShowWhen additionalCondition={() => utr && (!showCustomSettlDetails || adminAsMerchant)}>
        <div className="row settlement-detail-row">
          <span className="col-xs-4">UTR</span>
          <span className="col-xs-8">{utr}</span>
        </div>
      </ShowWhen>
      <ShowWhen additionalCondition={() => customSettlementLoading}>
        <LoaderDots />
      </ShowWhen>
      <ShowWhen additionalCondition={() => showCustomSettlDetails}>
        <div className="row settlement-detail-row">
          <span className="col-xs-4">Final Settlement Reference no.:</span>
          <span className="col-xs-8">{id}</span>
        </div>
        <div className="row settlement-detail-row">
          <span className="col-xs-4">Bank Settlement Status:</span>
          <span className="col-xs-8">
            <SettlementStatusLabel status={bankSettleStatus} />
          </span>
        </div>
      </ShowWhen>
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
