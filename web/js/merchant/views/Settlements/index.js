import { AsyncBtn } from 'common/new-ui/Button';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import Loader from 'common/ui/Loader';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { handleNegativeBalanceLimit } from 'common/utils/rzp-utils';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import EarlySettlementsAnnouncement from 'merchant/components/Announcements/EarlySettlements';
import UltraCampaignBanner from 'merchant/components/Announcements/UltraCampaignBanner';
import UltraP2CashAdvanceBanner from 'merchant/components/Announcements/UltraP2CashAdvanceBanner';
import CashAdvanceOrNitroBanner from 'merchant/components/CashAdvanceOrNitroBanner';
import EasterEgg from 'merchant/components/EasterEgg';
import ShowWhen, { RouteGuard } from 'merchant/components/ShowWhen';
import { fetchCurrentBalance as fnFetchCurrentBalance } from 'merchant/reducers/home';
import { fetchBankAccountChangeStatus as fnFetchBankAccountChangeStatus } from 'merchant/reducers/profile';
import { fetchSettlementConfig as fnFetchSettlementConfig } from 'merchant/reducers/settlements/details';
import { merchantFetch } from 'merchant/utils/ajax';
import { getSettlementStatus } from 'merchant/views/Capital/utils';
import ScheduledModal from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal';
import {
  POST_ENABLE_TYPES,
  SAMEDAY_MODAL_LOCATIONS,
} from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/constants';
import { getNoOfDaysAfterEsPartialEnable } from 'merchant/views/Settlements/Settlements/components/Modals/ScheduledModal/utils';
import EmptySettlementState from 'merchant/views/Settlements/v3/components/EmptyState';
import { StyledEmptySettlementsContainer } from 'merchant/views/Settlements/v3/components/EmptyState/styled';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import PropTypes from 'prop-types';
import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import { Link, NavLink, Route, Routes, Outlet } from 'react-router-dom';
import SettlementsHeader from './components/SettlementsHeader';
import SettlementsHeaderV2 from './components/SettlementsHeaderV2';
import InstantSettlements from './InstantSettlements/InstantSettlements';
import SettlementsListContainer from './Settlements/List';
import { trackOnDemandTabClick } from './trackEvents';
import { hideEmptyState, isEmptyStateVisible } from './v3/utils/common';
import { matchByRoute } from 'common/utils/matchByRoute';

const Settlements = ({
  user,
  merchantBalanceConfigs,
  current_balance,
  location,
  fetchCurrentBalance,
  fetchSettlementConfig,
  fetchBankAccountChangeStatus,
  openModal,
  children,
}) => {
  const [settlementExists, setSettlementExists] = useState(true);
  const [showEmptyState, setShowEmptyState] = useState(false);
  const [isSettlement, setIsSettlement] = useState({
    loading: true,
    empty: true,
  });
  const isOndemandSettlementEnabled = user.isOndemandSettlementEnabled;
  const isOndemandSettlementsRestricted = user.isOndemandSettlementsRestricted;
  const isPartialOndemandSettlementEnabled =
    isOndemandSettlementEnabled && isOndemandSettlementsRestricted;

  useEffect(() => {
    analyticsTrackWithUserInfo({
      screen: 'Settlements',
      objectName: 'Settlements Page',
      actionName: 'Rendered',
      properties: {
        page: 'Home Screen',
        settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : ' v1',
        state: user.isTransacted ? 'Complete' : 'Empty',
        activation_status: user.activation_status,
        sessionId: window?.session_id ? window.session_id : undefined,
        isL2Completed: user.isActivated && true,
        international_payments_enabled: user.international,
      },
    });
  }, []);

  const checkIfFirstEverSettlement = (callbackSettlementStatus) => {
    const settlementStatus = getSettlementStatus(user.current, callbackSettlementStatus);
    const isDisabled =
      settlementStatus === 'disableAnimation' || settlementStatus === 'disableAnimationOnReload';
    setSettlementExists(isDisabled || settlementStatus);
  };

  const onInstantSettlementsClick = () => {
    checkIfFirstEverSettlement();
    trackOnDemandTabClick(user);
    analyticsTrackWithUserInfo({
      screen: 'Settlements',
      objectName: 'Ondemand Settlement Tab',
      actionName: 'Clicked v2',
      properties: {
        page: 'Home Screen',
        settlements_experiment_name: user.isSettlementV3RevampEnabled ? 'v2' : ' v1',
        state: user.isTransacted ? 'Complete' : 'Empty',
        activation_status: user.activation_status,
        sessionId: window?.session_id ? window.session_id : undefined,
        isL2Completed: user.isActivated && true,
        international_payments_enabled: user.international,
      },
    });
  };

  const openEsAutomaticModalIfRoute = () => {
    // checks if merchant is not live with any of es_automatic and partial es_automatic product
    // and opens scheduled modal if pathname is /settlements/enable_automatic
    const showModal =
      user &&
      user.isOrgRZP &&
      isOndemandSettlementEnabled &&
      !user.isAutomaticSettlementEnabled &&
      !user.isAutomaticSettlementRestricted &&
      location.pathname === '/settlements/enable_automatic';

    if (showModal) {
      openModal({
        component: <ScheduledModal from={SAMEDAY_MODAL_LOCATIONS.ENABLE_AUTOMATIC_ROUTE} />,
        size: 'small',
        disableClose: true,
      });
    }
  };

  const getIsSettlement = () => {
    merchantFetch({ url: 'settlements?count=1' })
      .then((response) => {
        setIsSettlement({
          loading: false,
          empty: !(response?.data?.items?.length > 0),
        });
      })
      .catch(() => {
        setIsSettlement({
          loading: false,
          empty: false,
        });
      });
  };

  const checkEmptyStateValidity = () => {
    const isVisible = isEmptyStateVisible();
    if (isVisible) {
      setShowEmptyState(true);
    }
  };

  const handleAction = () => {
    setShowEmptyState(false);
    hideEmptyState();
  };

  useEffect(() => {
    checkIfFirstEverSettlement();
    fetchCurrentBalance();
    fetchSettlementConfig();
    fetchBankAccountChangeStatus(user.id);

    // opens scheduled modal if pathname is /settlements/enable_automatic
    openEsAutomaticModalIfRoute();
    checkEmptyStateValidity();
    getIsSettlement();
  }, []);

  const handleSettlementUnlockStatusClick = () => {
    const diff = getNoOfDaysAfterEsPartialEnable();
    const partialModalType = diff
      ? diff <= 30
        ? POST_ENABLE_TYPES.SAMEDAY_FULL_UNLOCK_STATUS
        : POST_ENABLE_TYPES.SAMEDAY_FULL_FAILURE
      : POST_ENABLE_TYPES.SAMEDAY_FULL_UNLOCK_STATUS;

    openModal({
      component: <ScheduledModal enabled postModalType={partialModalType} />,
      size: 'small',
      disableClose: true,
    });
  };

  // View settlements unlock status button is only shown
  // when merchant is live on partial es & partial es_automatic
  const showViewUnlockStatus =
    isPartialOndemandSettlementEnabled &&
    !user.isAutomaticSettlementEnabled &&
    user.isAutomaticSettlementRestricted;

  const showEmptyStateBanner = user.isSettlementV3RevampEnabled && showEmptyState;
  const showSettlementsEmptyState = isSettlement.empty && showEmptyStateBanner;

  if (showEmptyStateBanner && isSettlement.loading) {
    return <Loader />;
  }

  return showSettlementsEmptyState ? (
    <StyledEmptySettlementsContainer>
      <EmptySettlementState handleAction={handleAction} />
    </StyledEmptySettlementsContainer>
  ) : (
    <>
      {/* instant settlements banner */}
      <div className="settlements-banner-container">
        {user.isISBannerEnabled && <EarlySettlementsAnnouncement userId={user.current} />}
        {current_balance.data.balance < 0 && (
          <AnnouncementBanner
            title="Add Funds"
            theme="warning"
            canBeClosed={true}
            card_id="negative-balance-add-funds-banner"
          >
            Your balance went into negative value. Add funds to avoid the transaction failures.{' '}
            <Link to="/addfunds" target="_blank" rel="noreferrer noopener">
              {' '}
              Add Funds
            </Link>
          </AnnouncementBanner>
        )}

        {handleNegativeBalanceLimit(merchantBalanceConfigs, current_balance.data.balance) && (
          <AnnouncementBanner
            title="On Hold!"
            theme="danger"
            canBeClosed={true}
            card_id="on-hold-add-funds-banner"
          >
            Your current balance had reached the maximum negative limit. Transactions will start to
            fail now. Please add funds to avoid transaction failures.{' '}
            <Link to="/addfunds" target="_blank" rel="noreferrer noopener">
              {' '}
              Add Funds
            </Link>
          </AnnouncementBanner>
        )}

        <CashAdvanceOrNitroBanner productName="Settlements" />

        <DashboardBanner />
        <ShowWhen additionalCondition={(usr) => usr.isUltraCampaignBannerEnabled}>
          <UltraCampaignBanner productName="Settlements" />
        </ShowWhen>
        <ShowWhen additionalCondition={(usr) => usr.isUltraP2CashAdvanceCampaignBannerEnabled}>
          <UltraP2CashAdvanceBanner productName="Settlements" />
        </ShowWhen>
      </div>

      {user?.isSettlementDashboardVisibilityEnabled ? (
        <SettlementsHeaderV2
          settlementExists={settlementExists}
          esOndemandSettlementEnabled={isPartialOndemandSettlementEnabled}
          checkIfFirstEverSettlement={checkIfFirstEverSettlement}
        />
      ) : (
        <SettlementsHeader
          settlementExists={settlementExists}
          esOndemandSettlementEnabled={isPartialOndemandSettlementEnabled}
          checkIfFirstEverSettlement={checkIfFirstEverSettlement}
        />
      )}

      <tabbed-container>
        <header>
          <NavLink to="/settlements" onClick={checkIfFirstEverSettlement}>
            Settlements
          </NavLink>
          {user.isOndemandSettlementEnabled && (
            <NavLink onClick={onInstantSettlementsClick} to="/instantsettlements" end>
              <i className="i i-early-settlement settle-icon mr-5" />
              Ondemand Settlements
            </NavLink>
          )}
          {user.isOndemandRouteSettlementsEnabled && (
            <NavLink to="/routeinstantsettlements">
              <i className="i i-early-settlement settle-icon mr-5" /> Ondemand Route Settlements
            </NavLink>
          )}
          {showViewUnlockStatus && (
            <AsyncBtn.Transparent
              style={{
                position: 'absolute',
                right: 20,
              }}
              onClick={handleSettlementUnlockStatusClick}
            >
              <i className="i i-info-outline" /> View Settlement Unlock Status
            </AsyncBtn.Transparent>
          )}
        </header>
        <content>
          <ErrorBoundary resetOnProps>
            <Outlet />
            {children}
            <Routes>
              <Route
                path={matchByRoute(location.pathname, '/instantsettlements')}
                element={
                  <RouteGuard>
                    <InstantSettlements
                      settlementExists={settlementExists}
                      esOndemandSettlementEnabled={isPartialOndemandSettlementEnabled}
                      checkIfFirstEverSettlement={checkIfFirstEverSettlement}
                    />
                  </RouteGuard>
                }
              />
              <Route
                path={matchByRoute(location.pathname, '/settlements')}
                element={
                  <RouteGuard>
                    <SettlementsListContainer
                      settlementExists={settlementExists}
                      esOndemandSettlementEnabled={isPartialOndemandSettlementEnabled}
                      checkIfFirstEverSettlement={checkIfFirstEverSettlement}
                    />
                  </RouteGuard>
                }
              />
            </Routes>
          </ErrorBoundary>
        </content>
      </tabbed-container>
      <EasterEgg extraClass="ftx-settlements-page" page="Settlements" />
    </>
  );
};

Settlements.propTypes = {
  user: PropTypes.object,
};

export default withRouter(
  connect(
    (state) => ({
      user: state.session.user,
      current_balance: state.home.current_balance,
      merchantBalanceConfigs: state.home.merchantBalanceConfigs,
    }),
    {
      fetchCurrentBalance: fnFetchCurrentBalance,
      fetchSettlementConfig: fnFetchSettlementConfig,
      fetchBankAccountChangeStatus: fnFetchBankAccountChangeStatus,
      openModal: fnOpenModal,
    },
  )(Settlements),
);
