import React, { useState, useEffect, useMemo } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { withRouter } from 'common/deprecated/withRouter';
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
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { getCustomURL } from 'merchant/components/DocsLink';

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
  const docHref = useMemo(() => getCustomURL('http://razorpay.com/settlement'), []);

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

    handleAnalytics('Settlement Cycle', 'Clicked', {
      settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : 'v1',
      sessionId: window?.session_id ? window.session_id : undefined,
      international_payments_enabled: user.international,
      state: user.isTransacted ? 'Complete' : 'Empty',
      activation_status: user.activation_status,
      isL2Completed: user.isActivated && true,
      page: 'Home Screen',
    });
  };

  useEffect(() => {
    fetchPreviousSettlements(prevSettlementParams);
    fetchOnDemandBlocked();
    const interval = setInterval(() => {
      setTimeDiff(moment().diff(fetchedAt, 'minutes'));
    }, 60000);
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    analyticsTrackWithUserInfo({
      objectName: 'Settlement help Banner',
      actionName: 'Displayed',
      screen: 'Settlements',
      properties: {
        page: 'Home Screen',
        settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : 'v1',
        state: user.isTransacted ? 'Complete' : 'Empty',
        activation_status: user.activation_status,
        sessionId: window?.session_id ? window.session_id : undefined,
        isL2Completed: user.isActivated && true,
        international_payments_enabled: user.international,
      },
    });
  }, []);

  const onRefreshClick = () => {
    fetchCurrentBalance();
    fetchPreviousSettlements(prevSettlementParams);
    fetchSettlementAmount();
    fetchSettlementConfig();
    fetchBankAccountChangeStatus(user?.id);
    fetchOnDemandBlocked();
    setFetchedAt(moment());
    setTimeDiff(0);

    // instrumentation
    analyticsTrackWithUserInfo({
      objectName: 'Settlements overview',
      actionName: 'Refresh Requested',
      screen: 'Settlements',
      properties: {
        page: 'Home Screen',
        settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : 'v1',
        sessionId: window?.session_id ? window.session_id : undefined,
      },
    });
  };

  const instrumentDocumentationLinkClick = () => {
    analyticsTrackWithUserInfo({
      objectName: 'Settlements',
      actionName: 'Documentation Clicked',
      screen: 'Settlements',
      properties: {
        page: 'Home Screen',
        settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : 'v1',
        state: user.isTransacted ? 'Complete' : 'Empty',
        activation_status: user.activation_status,
        sessionId: window?.session_id ? window.session_id : undefined,
      },
    });
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
                  href={docHref}
                  target="_blank"
                  rel="noreferrer noopener"
                  onClick={instrumentDocumentationLinkClick}
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
