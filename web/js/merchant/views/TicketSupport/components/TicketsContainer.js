import React from 'react';
import Tickets from './Tickets';
import { NavLink, Route } from 'react-router-dom';
import { Switch, Redirect } from 'react-router';

export default function TicketsContainer() {
  return (
    <div class="content-wrapper content-sm ticket-support support-history-container">
      <NavLink
        activeClassName="active"
        className="btn btn-outline btn-top-tab"
        to="/ticket-support/tickets/merchant"
      >
        Support Queries
      </NavLink>
      <NavLink
        activeClassName="active"
        className="btn btn-outline btn-top-tab"
        to="/ticket-support/tickets/agent"
      >
        Support Requests
      </NavLink>
      <Switch>
        <Route exact path={`/ticket-support/tickets/:ticketType`} component={Tickets} />
        <Redirect to={`/ticket-support/tickets/merchant`} />
      </Switch>
    </div>
  );
}
