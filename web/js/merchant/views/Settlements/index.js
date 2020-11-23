import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import { Route, Switch, NavLink, Link } from 'react-router-dom';
import SettlementsListContainer from './Settlements/List';
import InstantSettlements from './InstantSettlements/InstantSettlements';
import ShowWhen from 'merchant/components/ShowWhen';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import ScheduledNitroBanner from 'merchant/components/ScheduledNitroBanner';
import EarlySettlementsAnnouncement from 'merchant/components/Announcements/EarlySettlements';
import { handleNegativeBalanceLimit } from 'common/utils/rzp-utils';

const Settlements = ({ user, merchantBalanceConfigs, current_balance }) => {
  const onInstantSettlementsClick = () => {
    trackIS.goToTabIS();
  };
  return (
    <>
      {/* instant settlements banner */}
      <div className="settlements-banner-container">
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

        <ShowWhen additionalCondition={(user) => user.isProjectNitroEnabled}>
          <AnnouncementBanner title="Exclusive Offer For You" canBeClosed={false}>
            <ScheduledNitroBanner
              fromWhere="settlements"
              url="https://lp.razorpay.com/razorpayxca-sttlmnts1"
            />
          </AnnouncementBanner>
        </ShowWhen>
      </div>

      <tabbed-container>
        <header>
          <NavLink to="/settlements">Settlements</NavLink>
          {user.isUseSettlementOndemandEnabled && (
            <NavLink onClick={onInstantSettlementsClick} to="/instantsettlements" exact>
              <i className="i i-early-settlement settle-icon mr-5" />
              Ondemand Settlements
            </NavLink>
          )}
        </header>
        <content>
          <Switch>
            <Route path="/instantsettlements" component={InstantSettlements} />
            <Route path="/settlements" component={SettlementsListContainer} />
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
  null,
)(Settlements);
