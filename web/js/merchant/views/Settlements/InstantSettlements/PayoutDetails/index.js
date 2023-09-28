import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import Amount from 'common/ui/Amount';
import Spinner from 'common/ui/Spinner';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Time from 'common/ui/Time';
import { SettlementStatusLabel } from 'merchant/components/StatusLabel';
import BreakupList from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/BreakupList';
import DetailsListContainer from 'merchant/views/Settlements/InstantSettlements/PayoutDetails/DetailsListContainer';
import SettlementGuideText from 'merchant_common/components/SettlementGuideText';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import * as InstantSettlementActions from 'merchant/reducers/instantSettlements/details';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import {
  trackOnDemandPayoutDeductionsHover,
  trackOnDemandPayoutDetailsFetched,
} from 'merchant/views/Settlements/trackEvents';

const InstantSettlementPayoutDetails = ({
  match,
  instantSettlement,
  loading,
  loadingTotalSettledAmount,
  fetchItem,
  fetchTotalSettlementAmount,
}) => {
  const [trackedPayoutDetails, setTrackedPayoutDetails] = useState(false);

  useEffect(() => {
    fetchItem(match.params.id);
  }, [match.params.id]);

  useEffect(() => {
    let timer = null;
    if (!loading && !loadingTotalSettledAmount && instantSettlement.amount_settled === 0) {
      timer = setInterval(() => {
        fetchTotalSettlementAmount(instantSettlement.id);
      }, 1000);
    }
    return () => clearInterval(timer);
  });

  useEffect(() => {
    trackIS.visitPayoutDetails();
  }, []);

  useEffect(() => {
    if (instantSettlement && Object.keys(instantSettlement).length > 0 && !trackedPayoutDetails) {
      trackOnDemandPayoutDetailsFetched(instantSettlement);
      setTrackedPayoutDetails(true);
    }
  }, [instantSettlement, trackedPayoutDetails]);

  const handleInstantSettlementsClick = () => {
    trackIS.clickCTAISPayoutDetails();
  };

  return loading ? (
    <div className="page-spinner-container">
      <Spinner />
    </div>
  ) : (
    <div className="instant-settlement-payout-detail">
      <div className="instant-settlement-payout-detail--breadcrumb">
        <Link to="/instantsettlements" onClick={handleInstantSettlementsClick}>
          <div className="instant-settlement-payout-detail--breadcrumb-settlement mr-5">
            <b>
              <i className="i i-arrow-back mr-10" />
            </b>
            <div>Ondemand Settlements</div>
          </div>
        </Link>
        <div className="instant-settlement-payout-detail--breadcrumb-settlement">
          <i className="i i-chevron-right mr-10" />
          <div className="instant-settlement-payout-detail--breadcrumb-payout-text">Details</div>
        </div>
      </div>
      <div className="flex mb-20">
        <div className="instant-settlement-payout-detail--section instant-settlement-payout-detail--total-amount">
          <div className="instant-settlement-payout-detail--section-heading mb-5">
            Total Settled Amount
          </div>
          <div className="instant-settlement-payout-detail--amount flex">
            {instantSettlement.amount_settled === 0 &&
            (instantSettlement.status === 'created' || instantSettlement.status === 'initiated') ? (
              <>
                <PlaceholderLoader />
                <i
                  className="i i-info-outline total-settlement-info-icon ml-8"
                  data-testid="settlement-info"
                  onMouseEnter={() => trackIS.hoverLoadingTotalSettledAmountIconPayoutDetails()}
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
              <Amount value={instantSettlement.amount_settled} currency="INR" />
            )}
          </div>
          <div className="flex">
            <div className="instant-settlement-payout-detail--requested-amount">
              Requested Amount <Amount currency="INR" value={instantSettlement.amount_requested} />
            </div>
            <div className="instant-settlement-payout-detail--deductions">
              Deductions
              <i
                className="i i-info-outline mr-10"
                data-testid="settlement-deduction"
                onMouseEnter={() => {
                  trackIS.hoverDeductionIconPayoutDetails();
                  trackOnDemandPayoutDeductionsHover();
                }}
              >
                <PopoverComponent align="bottom" theme="dark">
                  <PopoverBody>
                    <>
                      <div className="instant-settlement-payout-detail--deductions-row">
                        <div>Ondemand Fee</div>
                        <div>
                          <Amount
                            currency="INR"
                            value={instantSettlement.fees - instantSettlement.tax}
                          />
                        </div>
                      </div>
                      <div className="instant-settlement-payout-detail--deductions-row mb-6">
                        <div>Tax</div>
                        <div>
                          <Amount currency="INR" value={instantSettlement.tax} />
                        </div>
                      </div>
                      <div className="instant-settlement-payout-detail--deductions-row instant-settlement-payout-detail--deductions-row-deductions">
                        <div>Deductions</div>
                        <div>
                          <Amount currency="INR" value={instantSettlement.fees} />
                        </div>
                      </div>
                    </>
                  </PopoverBody>
                </PopoverComponent>
              </i>{' '}
              <Amount currency="INR" value={instantSettlement.fees} />
            </div>
          </div>
          <div className="instant-settlement-payout-detail--meta">
            <EntityDetailRow label="Status">
              <SettlementStatusLabel status={instantSettlement.status} />
            </EntityDetailRow>
            <EntityDetailRow label="Settlement Id">{instantSettlement.id}</EntityDetailRow>
            <EntityDetailRow label="Requested at">
              <Time value={instantSettlement.created_at} format="DD MMM YYYY, hh:mm:ss a" />
            </EntityDetailRow>
          </div>
        </div>
        <div className="instant-settlement-payout-detail--section instant-settlement-payout-detail--breakup">
          <div className="instant-settlement-payout-detail--section-heading mb-8">
            Ondemand Settlement Breakup
          </div>
          <BreakupList instantSettlement={instantSettlement} />
        </div>
      </div>
      <div className="instant-settlement-payout-detail--section">
        <DetailsListContainer instantSettlement={instantSettlement} />
        <SettlementGuideText />
      </div>
    </div>
  );
};

InstantSettlementPayoutDetails.propTypes = {
  instantSettlement: PropTypes.object,
  loading: PropTypes.bool,
  match: PropTypes.object,
  fetchItem: PropTypes.func,
  fetchTotalSettlementAmount: PropTypes.func,
  loadingTotalSettledAmount: PropTypes.bool,
};

export default withRouter(
  connect(
    (state) => state.instantSettlement,
    InstantSettlementActions,
  )(InstantSettlementPayoutDetails),
);
