import React, { useEffect } from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Alert from 'common/ui/Forms/Alert';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Button from 'common/new-ui/Button';
import List from 'merchant/views/Settlements/InstantSettlements/InstantSettlementDetails/List';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import {
  trackOnDemandHoverInfo,
  trackOnDemandIdDetails,
  trackOnDemandViewMoreClick,
} from '../../trackEvents';

const Details = ({
  settlement,
  isLoading,
  closeModal,
  statusMsg,
  fetchTotalSettlementAmount,
  loadingTotalSettledAmount,
}) => {
  const [trackedSettlementDetails, setTrackedSettlementDetails] = React.useState(false);

  function handlePayoutDetailClick() {
    trackIS.clickCTAViewMoreDetails();
    trackOnDemandViewMoreClick();
    closeModal();
  }

  useEffect(() => {
    let timer = null;
    if (!isLoading && !loadingTotalSettledAmount && settlement.amount_settled === 0) {
      timer = setInterval(() => {
        fetchTotalSettlementAmount(settlement.id);
      }, 1000);
    }
    return () => clearInterval(timer);
  });

  useEffect(() => {
    if (settlement && Object.keys(settlement).length > 0 && !trackedSettlementDetails) {
      trackOnDemandIdDetails(settlement);
      setTrackedSettlementDetails(true);
    }
  }, [settlement, trackedSettlementDetails]);

  return (
    <div className="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading instant-settlement--panel-heading">
            <span className="instant-settlement--panel-heading">Settlement Id:</span>{' '}
            <span className="instant-settlement--panel-heading-id">{settlement.id}</span>
          </div>
          <div className="instant-settlement--modal-section">
            <Alert type={statusMsg.type} message={statusMsg.message} />
            <div
              className="instant-settlement--modal-section-heading"
              style={{ marginTop: '60px' }}
            >
              Settlement Details
            </div>
            <EntityDetailRow label="Status">
              <SettlementStatusLabel status={settlement.status} />
            </EntityDetailRow>
            {settlement.ondemand_payouts.count === 1 && (
              <EntityDetailRow label="UTR">
                {settlement.ondemand_payouts.items[0].utr}
              </EntityDetailRow>
            )}
            <EntityDetailRow label="Total Settled Amount">
              {settlement.amount_settled === 0 &&
              (settlement.status === 'created' || settlement.status === 'initiated') ? (
                <>
                  <PlaceholderLoader />
                  <i
                    className="i i-info-outline total-settlement-info-icon ml-8"
                    onMouseEnter={() => {
                      trackIS.hoverLoadingTotalSettledAmountIconSettlementDetails();
                      trackOnDemandHoverInfo();
                    }}
                  >
                    <PopoverComponent align="bottom" theme="dark">
                      <PopoverBody>
                        We are fetching Total Settled Amount, and it seems that some of the
                        settlements are taking longer than expected.
                      </PopoverBody>
                    </PopoverComponent>
                  </i>
                </>
              ) : (
                <Amount value={settlement.amount_settled} currency="INR" />
              )}
            </EntityDetailRow>
            {settlement.amount_pending !== 0 && (
              <EntityDetailRow label="Pending Amount">
                <Amount value={settlement.amount_pending} currency="INR" />
              </EntityDetailRow>
            )}
            <EntityDetailRow label="Transaction Fee">
              <Amount value={settlement.fees - settlement.tax} currency="INR" />
            </EntityDetailRow>
            {settlement.tax !== 0 && (
              <EntityDetailRow label="GST Charge">
                <Amount value={settlement.tax} currency="INR" />
              </EntityDetailRow>
            )}
            <EntityDetailRow label="Type">
              {settlement?.scheduled ? 'Same day' : 'Instant'}
            </EntityDetailRow>
          </div>
          <div className="instant-settlement--modal-section">
            <div className="instant-settlement--modal-section-heading">
              Requested Settlement Details
            </div>
            <EntityDetailRow label="Requested Amount">
              <Amount value={settlement.amount_requested} currency="INR" />
            </EntityDetailRow>
            <EntityDetailRow label="Requested at">
              <Time value={settlement.created_at} format="DD MMM YYYY, hh:mm:ss a" />
            </EntityDetailRow>
          </div>
          {settlement.ondemand_payouts.count > 1 && (
            <div className="instant-settlement--modal-section">
              <div className="instant-settlement--modal-section-heading">Payout Details</div>

              <List settlement={settlement} />
              <Button.Secondary className="payout-details-cta">
                <Link
                  to={`/instantsettlement_details/${settlement.id}`}
                  onClick={handlePayoutDetailClick}
                >
                  <b>
                    View More Details
                    <i className="i i-arrow-forward ml-8" />
                  </b>
                </Link>
              </Button.Secondary>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

Details.propTypes = {
  settlement: PropTypes.object,
  isLoading: PropTypes.bool,
  closeModal: PropTypes.func,
  statusMsg: PropTypes.object,
  fetchTotalSettlementAmount: PropTypes.func,
  loadingTotalSettledAmount: PropTypes.bool,
};

export default Details;
