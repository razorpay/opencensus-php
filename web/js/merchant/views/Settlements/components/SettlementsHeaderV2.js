import React, { useState, useEffect } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from 'react-router-dom';
import TestModeBanner from 'merchant/components/TestModeBanner';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import SettlementScheduleV2 from 'merchant/views/Settlements/components/SettlementScheduleV2';
import { handleAnalytics } from 'merchant/views/Settlements/Settlements/analytics';
import SettlementsBannerV2 from 'merchant/views/Settlements/components/SettlementsBannerV2';
import BalanceCard from 'merchant/views/Settlements/components/BalanceCard';
import SettlementDueTodayCard from 'merchant/views/Settlements/components/SettlementDueTodayCard';
import PreviousSettlementCard from 'merchant/views/Settlements/components/PreviousSettlementCard';
import UpcomingSettlementCard from 'merchant/views/Settlements/components/UpcomingSettlementCard';
import {
  fetchOnDemandBlocked as fnFetchOnDemandBlocked,
  fetchSettlementConfig as fnFetchSettlementConfig,
  fetchPreviousSettlements as fnPreviousFetchSettlements,
} from 'merchant/reducers/settlements/details';
import {
  fetchCurrentBalance as fnFetchCurrentBalance,
  fetchSettlementAmount as fnFetchSettlementAmount,
} from 'merchant/reducers/home';
import { fetchBankAccountChangeStatus as fnFetchBankAccountChangeStatus } from 'merchant/reducers/profile';
import {
  Link,
  RotateCounterClockWiseIcon,
  ExternalLinkIcon,
  ClockIcon,
} from '@razorpay/blade/components';
import {
  SummaryHeader,
  SummaryHeaderSection,
  SettlementSummary,
  TimeSummary,
  SummaryInfoIcon,
  MediumBold,
  SpacerSpan,
  Documentation,
  SettlementCycle,
} from './styledUtils';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import ShowWhen from 'merchant/components/ShowWhen';

const SettlementsHeaderV2 = ({
  user,
  previousSettlementsList,
  settlement_amount,
  settlementConfig,
  openModal,
  settlementExists,
  checkIfFirstEverSettlement,
  esOndemandSettlementEnabled,
  settleNowDisabled,
  fetchCurrentBalance,
  fetchPreviousSettlements,
  fetchSettlementAmount,
  fetchSettlementConfig,
  fetchBankAccountChangeStatus,
  fetchOnDemandBlocked,
  current_balance,
}) => {
  const prevSettlementParams = {
    count: 25,
    skip: 0,
  };

  const [fetchedAt, setFetchedAt] = useState(moment());
  const [timeDiff, setTimeDiff] = useState(0);

  const isNodalAccountBalanceLowBlocked = settleNowDisabled?.data?.blocked;

  const no_settlement = settlement_amount?.data?.no_settlement;

  const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;
  const isOnHold = no_settlement?.on_hold;
  const isSettlementOnHold = isOnTemporaryHold || isOnHold;
  const currency = user.merchant.currency;

  const viewSettlementCycle = () => {
    openModal({
      size: 'medium',
      component: <SettlementScheduleV2 />,
    });

    window.rzpAnalytics?.({
      eventCategory: 'Settlement Revamp',
      eventAction: 'View Settlement Cycle',
      eventLabel: `Settlements`,
    });
    handleAnalytics('settlement cycle', 'clicked');
  };

  useEffect(() => {
    fetchPreviousSettlements(prevSettlementParams);
    fetchOnDemandBlocked();
    const interval = setInterval(() => {
      setTimeDiff(moment().diff(fetchedAt, 'minutes'));
    }, 60000);
    return () => clearInterval(interval);
  }, []);

  const onRefreshClick = () => {
    fetchCurrentBalance();
    fetchPreviousSettlements(prevSettlementParams);
    fetchSettlementAmount();
    fetchSettlementConfig();
    fetchBankAccountChangeStatus();
    fetchOnDemandBlocked();
    setFetchedAt(moment());
    setTimeDiff(0);
  };

  return (
    <>
      <SettlementsBannerV2 />
      <div className="settlements-header">
        <div>
          <TestModeBanner />
        </div>
        <SummaryHeader>
          <SummaryHeaderSection>
            <MediumBold>Overview</MediumBold>
            <SpacerSpan />
            <TimeSummary>
              <SummaryInfoIcon>
                <ClockIcon color="currentColor" size="small" />
              </SummaryInfoIcon>
              {timeDiff} mins ago
            </TimeSummary>
            <SpacerSpan />
            <Link
              variant="button"
              size="small"
              icon={RotateCounterClockWiseIcon}
              iconPosition="left"
              onClick={onRefreshClick}
            >
              Refresh
            </Link>
          </SummaryHeaderSection>
          <SummaryHeaderSection>
            <SettlementCycle>
              <Link
                variant="button"
                size="medium"
                icon={ClockIcon}
                iconPosition="left"
                onClick={viewSettlementCycle}
              >
                My Settlement Cycle
              </Link>
            </SettlementCycle>
            <ShowWhen
              additionalCondition={(user) =>
                !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Documentation)
              }
            >
              <Documentation>
                <Link
                  variant="anchor"
                  size="medium"
                  icon={ExternalLinkIcon}
                  iconPosition="right"
                  href="http://razorpay.com/settlement"
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  Documentation
                </Link>
              </Documentation>
            </ShowWhen>
          </SummaryHeaderSection>
        </SummaryHeader>
        <SettlementSummary>
          <BalanceCard
            current_balance={current_balance}
            isSettlementOnHold={isSettlementOnHold}
            user={user}
            settlementExists={settlementExists}
            esOndemandSettlementEnabled={esOndemandSettlementEnabled}
            checkIfFirstEverSettlement={checkIfFirstEverSettlement}
            isNodalAccountLowBalanceBlocked={isNodalAccountBalanceLowBlocked}
          />
          <SettlementDueTodayCard
            settlementsList={previousSettlementsList}
            settlementConfig={settlementConfig}
            currency={currency}
          />
          <PreviousSettlementCard
            settlementsList={previousSettlementsList}
            settlementConfig={settlementConfig}
            currency={currency}
          />
          <UpcomingSettlementCard
            current_balance={current_balance}
            next_settlement={settlement_amount?.data}
            settlementConfig={settlementConfig}
            currency={currency}
          />
        </SettlementSummary>
      </div>
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state?.session?.user,
    mode: state?.session?.mode,
    settlement_amount: state?.home?.settlement_amount,
    holidayList: state?.settlement?.holidayList,
    ...state?.home,
    settlementConfig: state?.settlement?.config,
    settleNowDisabled: state?.settlement?.settleNowButtonDisabled,
    previousSettlementsList: state?.settlement?.previousSettlements?.data?.items,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: fnOpenModal,
      closeModal: fnCloseModal,
      fetchOnDemandBlocked: fnFetchOnDemandBlocked,
      fetchCurrentBalance: fnFetchCurrentBalance,
      fetchSettlementConfig: fnFetchSettlementConfig,
      fetchBankAccountChangeStatus: fnFetchBankAccountChangeStatus,
      fetchSettlementAmount: fnFetchSettlementAmount,
      fetchPreviousSettlements: fnPreviousFetchSettlements,
    },
    dispatch,
  );
};

export default withRouter(connect(mapStateToProps, mapDispatchToProps)(SettlementsHeaderV2));
