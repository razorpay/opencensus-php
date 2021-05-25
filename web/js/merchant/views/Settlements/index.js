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
import CashAdvanceCampaignBanner from 'merchant/components/Announcements/CashAdvanceCampaign';
import { handleNegativeBalanceLimit } from 'common/utils/rzp-utils';
import { getSettlementStatus } from 'merchant/views/Capital/utils';

const Settlements = ({ user, merchantBalanceConfigs, current_balance }) => {
  const [settlementExists, setSettlementExists] = useState(true);
  const esOndemandSettlementEnabled = user.isFeatureEnabled('es_on_demand');

  const onInstantSettlementsClick = () => {
    checkIfFirstEverSettlement();
    trackIS.goToTabIS();
  };

  useEffect(() => {
    checkIfFirstEverSettlement();
  }, []);

  const checkIfFirstEverSettlement = (callbackSettlementStatus) => {
    const settlementStatus = getSettlementStatus(user.current, callbackSettlementStatus);
    const isDisabled =
      settlementStatus === 'disableAnimation' || settlementStatus === 'disableAnimationOnReload';
    setSettlementExists(isDisabled || settlementStatus);
  };

  return (
    <>
      {/* instant settlements banner */}
      <div className="settlements-banner-container">
        {user.isLOCEnabled && !user.isWithdrawFeatureEnabled && (
          <CashAdvanceOrNitroBanner productName="Settlements" />
        )}
        {user.isISBannerEnabled && <EarlySettlementsAnnouncement userId={user.current} />}
        {current_balance.data.balance < 0 && (
          <AnnouncementBanner title="Add Funds" theme="warning" canBeClosed={true}>
            Your balance went into negative value. Add funds to avoid the transaction failures.{' '}
            <Link to="/addfunds" target="_blank">
              {' '}
              Add Funds
            </Link>
          </AnnouncementBanner>
        )}

        {handleNegativeBalanceLimit(merchantBalanceConfigs, current_balance.data.balance) && (
          <AnnouncementBanner title="On Hold!" theme="danger" canBeClosed={true}>
            Your current balance had reached the maximum negative limit. Transactions will start to
            fail now. Please add funds to avoid transaction failures.{' '}
            <Link to="/addfunds" target="_blank">
              {' '}
              Add Funds
            </Link>
          </AnnouncementBanner>
        )}

        <CashAdvanceCampaignBanner />
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
        </content>
      </tabbed-container>
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
