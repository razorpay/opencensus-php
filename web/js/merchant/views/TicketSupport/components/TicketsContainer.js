import React from 'react';
import { connect } from 'react-redux';
import Tickets from './Tickets';
import { NavLink, Route } from 'react-router-dom';
import { Switch, Redirect } from 'react-router';
import {
  QUERY_ROUTE_LINK_MERCHANT,
  QUERY_REQUEST_ROUTE_LINK__AGENT,
  QUERY_ROUTE_LINK_MERCHANT_NEW,
  QUERY_REQUEST_ROUTE_LINK__AGENT_NEW,
} from './data';

function TicketsContainer(props) {
  const { user } = props;
  const supportQueruesRouteLink = user.isAccountAndSettingsRevampEnabled
    ? QUERY_ROUTE_LINK_MERCHANT_NEW
    : QUERY_ROUTE_LINK_MERCHANT;
  const supportRequestsRouteLink = user.isAccountAndSettingsRevampEnabled
    ? QUERY_REQUEST_ROUTE_LINK__AGENT_NEW
    : QUERY_REQUEST_ROUTE_LINK__AGENT;

  return (
    <div class="content-wrapper content-sm ticket-support support-history-container">
      <NavLink
        activeClassName="active"
        className="btn btn-outline btn-top-tab"
        to={supportQueruesRouteLink}
      >
        Support Queries
      </NavLink>
      <NavLink
        activeClassName="active"
        className="btn btn-outline btn-top-tab"
        to={supportRequestsRouteLink}
      >
        Support Requests
      </NavLink>
      <Switch>
        <Route
          exact
          path={
            props.user.isAccountAndSettingsRevampEnabled
              ? '/business-settings/ticket-support/tickets/:ticketType'
              : '/ticket-support/tickets/:ticketType'
          }
          component={Tickets}
        />
        <Redirect to={supportQueruesRouteLink} />
      </Switch>
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps, null)(TicketsContainer);
