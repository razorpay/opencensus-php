import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';

import { statuses, getActiveTicket } from './data.js';
import { titleCase } from 'common/utils/rzp-utils.js';

export default class TicketStatus extends React.Component {
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Ticket Dashboard',
      eventAction: 'ticket clicked',
      eventLabel: `Tickets`,
    });
  }

  render() {
    const ticket = this.props.ticket;
    const status = statuses[ticket.status];
    return (
      <span
        style={{ marginLeft: '10px' }}
        className={`label ticket-status-label ${(() => {
          var label = 'label-warning';
          if (status == 'closed') {
            label = 'label-danger';
          }
          if (status == 'resolved') {
            label = 'label-success';
          }
          if (status == 'resolved') {
            label = 'label-success';
          }
          if (status == 'open') {
            label = 'label-active';
          }
          return label;
        })()}`}
      >
        {(() => {
          let S = status;
          if (status === 'open') {
            S = 'active';
          }
          return titleCase(S);
        })()}
      </span>
    );
  }
}
