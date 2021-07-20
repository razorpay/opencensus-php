import { Link } from 'react-router-dom';
import TicketStatus from './TicketStatus.js';
import TicketBriefMessage from './TicketBriefMessage';
import { Fragment } from 'react';
import { STATUSES } from '../utils.js';
export default class TicketBriefRevamped extends React.Component {
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
    const ticketStatus = STATUSES[ticket.status];
    // only date showed here
    return (
      <div className="panel ticket-row-panel revamped">
        <div
          className="panel-header"
          style={{ borderBottom: this.props.last ? `1px solid rgba(22,47,86,0.1)` : 'auto' }}
        >
          <div className="row">
            <div className="col-xs-12">
              <div className="panel" style={{ marginBottom: 0 }}>
                <div className="panel-body">
                  <div className="row">
                    <div className="col-xs-10">
                      <Link to={`/ticket-support/${ticket.fd_instance}/${ticket.id}/conversation`}>
                        {!this.props.user.isTicketRevampFlowEnabled ? <p className="ticket-subject">{subject}</p> : <p className="ticket-subject">
                          {ticket.custom_fields.cf_requestor_subcategory}
                          {ticket.custom_fields.cf_requester_item ? <Fragment>
                            <span className="ticket-detail-separator">•</span>
                            {ticket.custom_fields.cf_requester_item}
                          </Fragment> : null}
                        </p>
                        }
                      </Link>

                      <Link to={`/ticket-support/${ticket.fd_instance}/${ticket.id}/conversation`}>
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
                      <div class="ticket-brief-desc-text">{ticket.description_text}</div>

                    </div>
                  </div>
                </div>
                {ticketStatus !== 'CLOSED' ? <Fragment>
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
                          window.rzpTicketSystem &&
                            window.rzpTicketSystem.openModal(`#call-details`, {
                              id: ticket.custom_fields.cf_callback_id,
                            });
                        }}
                        class="pointer"
                      >
                        View Details
                      </b>
                    </p>
                  ) : <div class="Ticket-Status-Desc">
                    <TicketBriefMessage ticket={ticket} />
                  </div>
                  }
                </Fragment> : null}

              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
