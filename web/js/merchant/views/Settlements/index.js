import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link } from 'react-router-dom';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import SettlementsListContainer from './Settlements/List';
import InstantSettlements from './InstantSettlements/InstantSettlements';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import CashAdvanceOrNitroBanner from 'merchant/components/CashAdvanceOrNitroBanner';
import EarlySettlementsAnnouncement from 'merchant/components/Announcements/EarlySettlements';
import UltraCampaignBanner from 'merchant/components/Announcements/UltraCampaignBanner';
import UltraP2CashAdvanceBanner from 'merchant/components/Announcements/UltraP2CashAdvanceBanner';
import { handleNegativeBalanceLimit } from 'common/utils/rzp-utils';
import { getSettlementStatus } from 'merchant/views/Capital/utils';
import { trackOnDemandTabClick } from './trackEvents';
import ShowWhen from 'merchant/components/ShowWhen';
import EasterEgg from 'merchant/components/EasterEgg';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';

const Settlements = ({ user, merchantBalanceConfigs, current_balance }) => {
  const [settlementExists, setSettlementExists] = useState(true);
  const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');

  const checkIfFirstEverSettlement = (callbackSettlementStatus) => {
    const settlementStatus = getSettlementStatus(user.current, callbackSettlementStatus);
    const isDisabled =
      settlementStatus === 'disableAnimation' || settlementStatus === 'disableAnimationOnReload';
    setSettlementExists(isDisabled || settlementStatus);
  };

  const onInstantSettlementsClick = () => {
    checkIfFirstEverSettlement();
    trackOnDemandTabClick();
    trackIS.goToTabIS();
  };

  useEffect(() => {
    checkIfFirstEverSettlement();
  }, []);

  return (
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
            <Link to="/addfunds" target="_blank">
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
            <Link to="/addfunds" target="_blank">
              {' '}
              Add Funds
            </Link>
          </AnnouncementBanner>
        )}

        <CashAdvanceOrNitroBanner productName="Settlements" />
        <ShowWhen additionalCondition={(usr) => usr.isUltraCampaignBannerEnabled}>
          <UltraCampaignBanner productName="Settlements" />
        </ShowWhen>
        <ShowWhen additionalCondition={(usr) => usr.isUltraP2CashAdvanceCampaignBannerEnabled}>
          <UltraP2CashAdvanceBanner productName="Settlements" />
        </ShowWhen>
      </div>

      <tabbed-container>
        <header>
          <NavLink to="/settlements" onClick={() => checkIfFirstEverSettlement()}>
            Settlements
          </NavLink>
          {user.isOndemandSettlementEnabled && (
            <NavLink onClick={onInstantSettlementsClick} to="/instantsettlements" exact>
              <i className="i i-early-settlement settle-icon mr-5" />
              Ondemand Settlements
            </NavLink>
          )}
        </header>
        <content>
          <ErrorBoundary resetOnProps>
            <Switch>
              <Route
                path="/instantsettlements"
                render={() => (
                  <InstantSettlements
                    settlementExists={settlementExists}
                    esOndemandSettlementEnabled={esOndemandSettlementEnabled}
                    checkIfFirstEverSettlement={checkIfFirstEverSettlement}
                  />
                )}
              />
              <Route
                path="/settlements"
                render={() => (
                  <SettlementsListContainer
                    settlementExists={settlementExists}
                    esOndemandSettlementEnabled={esOndemandSettlementEnabled}
                    checkIfFirstEverSettlement={checkIfFirstEverSettlement}
                  />
                )}
              />
            </Switch>
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

export default connect(
  (state) => ({
    user: state.session.user,
    current_balance: state.home.current_balance,
    merchantBalanceConfigs: state.home.merchantBalanceConfigs,
  }),
  {},
)(Settlements);
