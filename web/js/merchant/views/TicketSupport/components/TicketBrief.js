import React from 'react';
import moment from 'moment';
import { Link } from 'react-router-dom';
import TicketStatus from './TicketStatus';
import TicketBriefMessage from './TicketBriefMessage';
import {
  STATUSES,
  sanitizeRaySubcategory,
  sanitizeTicketCategory,
} from 'merchant/views/TicketSupport/utils';

const TicketCardHeader = ({ ticket }) => {
  const {
    cf_requestor_subcategory,
    cf_new_requester_sub_category,
    cf_new_requester_item,
    cf_requester_item,
  } = ticket.custom_fields || {};

  const category = sanitizeTicketCategory(
    cf_requestor_subcategory || cf_new_requester_sub_category,
  );
  const subCategory = cf_new_requester_item || cf_requester_item;

  return (
    <>
      {category}
      {subCategory ? (
        <>
          {category ? <span className="ticket-detail-separator">•</span> : null}
          {sanitizeRaySubcategory(subCategory)}
        </>
      ) : null}
    </>
  );
};

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
    const isRzpPosTicket = ticket?.type === 'Ezetap';
    let subject = ticket.subject;
    subject = subject.replace('[Merchant]', '');
    const formattedDate = moment(ticket.created_at).fromNow();
    const ticketStatus = STATUSES[ticket.status];

    const { user, ticketType } = this.props;
    const ticketConversationBaseUrl = `ticket-support/${ticket.fd_instance}/${ticket.id}/${ticketType}/conversation`;
    const ticketConversationUrl = user.isAccountAndSettingsRevampEnabled
      ? `/business-settings/${ticketConversationBaseUrl}`
      : ticketConversationBaseUrl;
    // only date showed here
    return (
      <Link to={ticketConversationUrl}>
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
                          ) : isRzpPosTicket ? (
                            <>
                              {'In store/In person'}
                              <span className="ticket-detail-separator">•</span>
                              {'RazorpayPOS'}
                            </>
                          ) : (
                            <TicketCardHeader ticket={ticket} />
                          )}
                        </p>
                        <p className="ticket-short-details">
                          <span>Ticket # {ticket.ticket_id}</span>
                          <span className="ticket-detail-separator">•</span>
                          <span>Raised {formattedDate}</span>
                        </p>
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
