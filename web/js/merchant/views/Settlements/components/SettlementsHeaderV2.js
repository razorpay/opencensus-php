import React, { useState, useEffect } from 'react';
import {
  Link,
  RotateCounterClockWiseIcon,
  ExternalLinkIcon,
  ClockIcon,
} from '@razorpay/blade/components';
import moment from 'moment';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { useI18Service } from 'common/i18';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import ShowWhen from 'merchant/components/ShowWhen';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import {
  fetchCurrentBalance as fnFetchCurrentBalance,
  fetchSettlementAmount as fnFetchSettlementAmount,
} from 'merchant/reducers/home';
import { fetchBankAccountChangeStatus as fnFetchBankAccountChangeStatus } from 'merchant/reducers/profile';
import {
  fetchSettlementConfig as fnFetchSettlementConfig,
  fetchPreviousSettlements as fnPreviousFetchSettlements,
} from 'merchant/reducers/settlements/details';
import { handleAnalytics } from 'merchant/views/Settlements/Settlements/analytics';
import BalanceCard from 'merchant/views/Settlements/components/BalanceCard';
import PreviousSettlementCard from 'merchant/views/Settlements/components/PreviousSettlementCard';
import SettlementDueTodayCard from 'merchant/views/Settlements/components/SettlementDueTodayCard';
import SettlementScheduleV2 from 'merchant/views/Settlements/components/SettlementScheduleV2';
import SettlementsBannerV2 from 'merchant/views/Settlements/components/SettlementsBannerV2';
import UpcomingSettlementCard from 'merchant/views/Settlements/components/UpcomingSettlementCard';
import {
  openModal as fnOpenModal,
  closeModal as fnCloseModal,
} from 'merchant_common/reducers/modals';
import { useODSConfig } from 'merchant/views/Settlements/InstantSettlements/query-hooks/useODSConfig';
import { OdsBanners } from 'merchant/views/Settlements/InstantSettlements/InstantSettlements/SettlementMessage/banners/OdsBanners';

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

const SETTLEMENT_DOC_LINK = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: 'http://razorpay.com/settlement',
  [ORG_CUSTOM_CODE_MAP.CURLEC]: 'https://curlec.com/docs/payments/settlements',
};

const SettlementsHeaderV2 = ({
  user,
  previousSettlementsList,
  settlement_amount,
  settlementConfig,
  openModal,
  settlementExists,
  checkIfFirstEverSettlement,
  esOndemandSettlementEnabled,
  fetchCurrentBalance,
  fetchPreviousSettlements,
  fetchSettlementAmount,
  fetchSettlementConfig,
  fetchBankAccountChangeStatus,
  current_balance,
  org,
}) => {
  const prevSettlementParams = {
    count: 25,
    skip: 0,
  };

  const [fetchedAt, setFetchedAt] = useState(moment());
  const [timeDiff, setTimeDiff] = useState(0);

  const odsQuery = useODSConfig();

  const isNodalAccountBalanceLowBlocked = odsQuery.data?.blocked;
  const {
    settlement_currency: settlementCurrency,
    balance_currency: balanceCurrency,
    no_settlement,
  } = settlement_amount?.data;

  const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;
  const isOnHold = no_settlement?.on_hold;
  const isSettlementOnHold = isOnTemporaryHold || isOnHold;
  const orgCode = org.custom_code;
  const docHref = SETTLEMENT_DOC_LINK[orgCode] || SETTLEMENT_DOC_LINK.rzp;
  const { isConfigTagEnabled } = useI18Service();

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
    odsQuery.refetch();
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
      <OdsBanners />
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
              additionalCondition={() => !isConfigTagEnabled('documentation.documentation')}
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
            balanceCurrency={balanceCurrency}
          />
          <SettlementDueTodayCard
            settlementsList={previousSettlementsList}
            settlementConfig={settlementConfig}
            currency={settlementCurrency}
          />
          <PreviousSettlementCard
            settlementsList={previousSettlementsList}
            settlementConfig={settlementConfig}
            currency={settlementCurrency}
          />
          <UpcomingSettlementCard
            current_balance={current_balance}
            next_settlement={settlement_amount?.data}
            settlementConfig={settlementConfig}
            currency={settlementCurrency}
          />
        </SettlementSummary>
      </div>
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state?.session?.user,
    org: state.session.org,
    mode: state?.session?.mode,
    settlement_amount: state?.home?.settlement_amount,
    holidayList: state?.settlement?.holidayList,
    ...state?.home,
    settlementConfig: state?.settlement?.config,
    previousSettlementsList: state?.settlement?.previousSettlements?.data?.items,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: fnOpenModal,
      closeModal: fnCloseModal,
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
