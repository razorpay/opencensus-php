import React from 'react';
import moment from 'moment';
import { Link } from 'react-router-dom';
import Popover, { PopoverBody } from 'common/ui/Popover';

import { statuses } from './data';
import TicketStatus from './TicketStatus';

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

    let subject = ticket.subject;
    subject = subject.replace('[Merchant]', '');
    const isScheduleCallbackEnabled = this.props.user.isScheduleCallbackEnabled;
    const formattedDate = moment(ticket.created_at).fromNow();
    const responseFormatDate = moment(ticket.fr_due_by).format('DD MMM');
    // only date showed here
    return (
      <div className="panel ticket-row-panel" style={{ marginBottom: 0 }}>
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
                      <Link
                        to={`/ticket-support/${ticket.fd_instance}/${ticket.id}/${this.props.ticketType}/conversation`}
                      >
                        <p className="ticket-subject">{subject}</p>
                      </Link>

                      <Link
                        to={`/ticket-support/${ticket.fd_instance}/${ticket.id}/${this.props.ticketType}/conversation`}
                      >
                        <p className="ticket-short-details">
                          <span>Ticket # {ticket.ticket_id}</span>
                          <span className="ticket-detail-separator">•</span>
                          <span>Raised {formattedDate}</span>
                        </p>
                      </Link>
                    </div>
                    {/* Render only if status `Awaiting Your Reply` */}
                    {statuses[ticket.status] &&
                      statuses[ticket.status].name === 'Awaiting Your Reply' && (
                        <div className="col-xs-4">
                          <TicketStatus ticket={ticket} />
                        </div>
                      )}
                    {statuses[ticket.status] &&
                      statuses[ticket.status].name === 'Active' &&
                      ticket.priority === 4 && (
                        <div className="ticket-escalated-response">
                          <span>
                            <i class="i i-forward ticket-escalated-icon" />
                            <Popover align="bottom" theme="dark">
                              <PopoverBody>
                                <div>We are looking at this escalation on priority.</div>
                              </PopoverBody>
                            </Popover>
                          </span>
                          <span>Response expected before:</span>
                          <span className="ticket-escalated-response-time">
                            {responseFormatDate}
                          </span>
                        </div>
                      )}
                  </div>
                </div>
                {isScheduleCallbackEnabled && ticket.tags.includes('callback') ? (
                  <p class="call-requested">
                    <img
                      class="schedule-call-icon"
                      src="https://cdn.razorpay.com/static/assets/ticket-system/icon-call.svg"
                      alt=""
                    />{' '}
                    Call is requested on this query.{' '}
                    <b
                      onClick={() => {
                        if (window.rzpTicketSystem) {
                          window.rzpTicketSystem.openModal(`#call-details`, {
                            id: ticket.custom_fields.cf_callback_id,
                          });
                        }
                      }}
                      class="pointer"
                    >
                      View Details
                    </b>
                  </p>
                ) : null}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
