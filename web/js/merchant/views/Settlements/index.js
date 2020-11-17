import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import trackIS from 'merchant/views/Settlements/InstantSettlements/ga';
import { Route, Switch, NavLink } from 'react-router-dom';
import SettlementsListContainer from './Settlements/List';
import InstantSettlements from './InstantSettlements/InstantSettlements';

const Settlements = ({ user }) => {
  const onInstantSettlementsClick = () => {
    trackIS.goToTabIS();
  };
  return (
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
  );
};

Settlements.propTypes = {
  user: PropTypes.object,
};

export default connect((state) => ({ user: state.session.user }), null)(Settlements);
