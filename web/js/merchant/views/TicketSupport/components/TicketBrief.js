import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';

import { statuses } from './data.js';
import { titleCase } from 'common/utils/rzp-utils.js';
import TicketStatus from './TicketStatus.js';

export default class TicketBrief extends React.Component {
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
    let message;
    // if (status == 'closed') {
    //   message = `Ticket closed ${moment(ticket.stats.closed_at).fromNow()}`;
    // }
    // if (status == 'resolved') {
    //   message = `Ticket resolved ${moment(ticket.stats.resolved_at).fromNow()}`;
    // }
    // if (status == 'pending') {
    //   message = `Team is investigating the ticket`;
    // }
    // if (status == 'open') {
    //   if (ticket.stats.requester_responded_at) {
    //     message = `You have responded ${moment(ticket.stats.requester_responded_at).fromNow()}`;
    //   }
    //   if (ticket.stats.agent_responded_at) {
    //     message = `Razorpay responded ${moment(ticket.stats.requester_responded_at).fromNow()}`;
    //   }
    //   if (!(ticket.stats.requester_responded_at && ticket.stats.agent_responded_at)) {
    //     message = `Support team will respond within 8hrs`;
    //   }
    // }
    let subject = ticket.subject;
    subject = subject.replace('[Merchant]', '');

    const formattedDate = moment(ticket.created_at).fromNow();
    return (
      <div className="panel ticket-row-panel">
        <div
          className="panel-header"
          style={{ borderBottom: this.props.last ? `1px solid rgba(22,47,86,0.1)` : 'auto' }}
        >
          <div className="row">
            <div className="col-xs-12">
              <div className="panel" style={{ marginBottom: 0 }}>
                <div className="panel-body">
                  <div className="row">
                    <div className="col-xs-8">
                      <Link to={`/ticket-support/${ticket.fd_instance}/${ticket.id}/conversation`}>
                        <p class="message-subject">{subject}</p>
                      </Link>

                      <p>
                        <span>Ticket ID #{ticket.id}</span>
                        <span className="ticket-detail-separator">•</span>
                        <span>Raised {formattedDate}</span>
                      </p>
                    </div>
                    <div className="col-xs-4">
                      <TicketStatus ticket={ticket} />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
