import React from 'react';
import moment from 'moment';
import { Link } from 'react-router-dom';
import TicketStatus from './TicketStatus';
import TicketBriefMessage from './TicketBriefMessage';
import { STATUSES } from '../utils';
export default class TicketBriefRevamped extends React.Component {
  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Ticket Dashboard',
      eventAction: 'ticket clicked',
      eventLabel: `Tickets`,
    });
  }

  render() {
    const ticket = this.props.ticket;
    const isTicketCreatedByAgent = ticket?.custom_fields?.cf_created_by === 'agent';
    let subject = ticket.subject;
    subject = subject.replace('[Merchant]', '');
    const formattedDate = moment(ticket.created_at).fromNow();
    const ticketStatus = STATUSES[ticket.status];
    // only date showed here
    return (
      <Link
        to={`/ticket-support/${ticket.fd_instance}/${ticket.id}/${this.props.ticketType}/conversation`}
      >
        <div className="panel ticket-row-panel revamped">
          <div className={`panel-header ${this.props.last ? 'border-solid' : 'border-auto'}`}>
            <div className="row">
              <div className="col-xs-12">
                <div className="panel mb-0">
                  <div className="panel-body">
                    <div className="row">
                      <div className="col-xs-10">
                        <p className="ticket-subject">
                          {isTicketCreatedByAgent ? (
                            subject
                          ) : (
                            <>
                              {ticket.custom_fields.cf_requestor_subcategory}
                              {ticket.custom_fields.cf_requester_item ? (
                                <>
                                  <span className="ticket-detail-separator">•</span>
                                  {ticket.custom_fields.cf_requester_item}
                                </>
                              ) : null}
                            </>
                          )}
                        </p>

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
                      <div className="col-xs-2">
                        <TicketStatus ticket={ticket} />
                      </div>
                      <div className="col-xs-12">
                        <div className="ticket-brief-desc-text">{ticket.description_text}</div>
                      </div>
                    </div>
                  </div>
                  {ticketStatus !== 'CLOSED' ? (
                    ticket.tags.includes('callback') ||
                    ticket.tags.includes('instant_callback_requested') ? (
                      <p className="call-requested">
                        <img
                          className="schedule-call-icon"
                          src="https://cdn.razorpay.com/static/assets/ticket-system/icon-call.svg"
                          alt="schedule callback icon"
                        />{' '}
                        Call is requested on this query.{' '}
                        <b
                          onClick={() => {
                            if (window.rzpTicketSystem) {
                              window.rzpTicketSystem.openModal(`#call-details`, {
                                id: ticket.custom_fields.cf_callback_id,
                                ticket,
                              });
                            }
                          }}
                          className="pointer"
                        >
                          View Details
                        </b>
                      </p>
                    ) : (
                      <div className="Ticket-Status-Desc">
                        <TicketBriefMessage ticketType={this.props.ticketType} ticket={ticket} />
                      </div>
                    )
                  ) : null}
                </div>
              </div>
            </div>
          </div>
        </div>
      </Link>
    );
  }
}
