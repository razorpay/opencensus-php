import React from 'react';
import { connect } from 'react-redux';
import Tickets from './Tickets';
import { NavLink, Route, Routes, Navigate } from 'react-router-dom';
import {
  QUERY_ROUTE_LINK_MERCHANT,
  QUERY_REQUEST_ROUTE_LINK__AGENT,
  QUERY_ROUTE_LINK_MERCHANT_NEW,
  QUERY_REQUEST_ROUTE_LINK__AGENT_NEW,
} from './data';
import { RouteGuard } from 'merchant/components/ShowWhen';

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
        className={({ isActive }) => `btn btn-outline btn-top-tab${isActive ? ' active' : ''}`}
        to={supportQueruesRouteLink}
      >
        Support Queries
      </NavLink>
      <NavLink
        className={({ isActive }) => `btn btn-outline btn-top-tab${isActive ? ' active' : ''}`}
        to={supportRequestsRouteLink}
      >
        Support Requests
      </NavLink>
      <Routes>
        <Route
          path=":ticketType"
          element={
            <RouteGuard>
              <Tickets />
            </RouteGuard>
          }
        />
        <Route index element={<Navigate to={supportQueruesRouteLink} replace />} />
      </Routes>
    </div>
  );
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default connect(mapStateToProps, null)(TicketsContainer);
