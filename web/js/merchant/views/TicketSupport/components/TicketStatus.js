import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';

import { statuses, getActiveTicket } from './data.js';
import { titleCase } from 'common/utils/rzp-utils.js';

export default class TicketStatus extends React.Component {
  componentDidMount() {}

  render() {
    const ticket = this.props.ticket;
    const status = statuses[ticket.status];
    return (
      <span
        style={{ marginLeft: '10px' }}
        className={`label ticket-status-label label-${status.class}`}
      >
        {status.name}
      </span>
    );
  }
}
