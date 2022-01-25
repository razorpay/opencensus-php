import { statuses } from './data';
import React from 'react';

export default class TicketStatus extends React.Component {
  componentDidMount() {}

  render() {
    const ticket = this.props.ticket;
    const status = statuses[ticket.status];
    return (
      <span
        style={{ marginLeft: '10px' }}
        className={`label ticket-status-label label-${status && status.class}`}
      >
        {status?.name}
      </span>
    );
  }
}
