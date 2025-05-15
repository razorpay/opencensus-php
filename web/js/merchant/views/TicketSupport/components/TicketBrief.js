import React from 'react';
import moment from 'moment';
import { Link } from 'react-router-dom';
import TicketStatus from './TicketStatus';
import TicketBriefMessage from './TicketBriefMessage';
import {
  STATUSES,
  sanitizeRaySubcategory,
  sanitizeTicketCategory,
  trackEscalateNowButtonClicked,
  trackEscalateNowButtonDisplayed,
} from 'merchant/views/TicketSupport/utils';
import { Button, Tooltip } from '@razorpay/blade/components';
import { getResponseExpectedBy } from '../getResponseExpectedBy';

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
  constructor(props) {
    super(props);
    this.state = {
      isEscalating: false,
    };
  }

  isTicketEligibleForEscalation =
    this.props.isTicketEligibleForEscalation && this.props.isTicketEscalationEnabled;

  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Ticket Dashboard',
      eventAction: 'ticket clicked',
      eventLabel: `Tickets`,
    });
    if (this.isTicketEligibleForEscalation) {
      trackEscalateNowButtonDisplayed(this.props.ticket, {
        isEligible: this.props.isTicketEligibleForEscalation,
        isMxEscalated: this.props.isTicketMxEscalated,
        reasonForEscalation: this.props.reasonForEscalation,
      });
    }
  }

  handleEscalateClick = (e, ticket) => {
    e.preventDefault();
    e.stopPropagation();
    trackEscalateNowButtonClicked(ticket, {
      isEligible: this.props.isTicketEligibleForEscalation,
      isMxEscalated: this.props.isTicketMxEscalated,
      reasonForEscalation: this.props.reasonForEscalation,
    });
    this.setState({ isEscalating: true });
    this.props.escalateTicket(ticket).finally(() => {
      this.setState({ isEscalating: false });
    });
    return false;
  };

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

    const shouldShowETA =
      getResponseExpectedBy(ticket)?.shouldShowEta || !this.props.isTicketEscalationEnabled;

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
                          <span className="ticket-detail-separator">•</span>
                          <TicketStatus ticket={ticket} shouldUseBladeBadge={true} />
                        </p>
                      </div>
                      {this.isTicketEligibleForEscalation ? (
                        <div
                          className="col-xs-2"
                          style={{ textAlign: 'right' }}
                          onClick={(e) => {
                            e.preventDefault();
                            e.stopPropagation();
                          }}
                        >
                          <Tooltip
                            content="This ticket is taking longer than expected to get resolved. You can escalate it, if you wish for a faster resolution."
                            placement="bottom"
                          >
                            <Button
                              variant="secondary"
                              color="negative"
                              size="small"
                              onClick={(e) => this.handleEscalateClick(e, ticket)}
                              isLoading={this.state.isEscalating}
                            >
                              Escalate now
                            </Button>
                          </Tooltip>
                        </div>
                      ) : null}
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
                    ) : shouldShowETA ? (
                      <div className="Ticket-Status-Desc">
                        <TicketBriefMessage
                          ticketType={this.props.ticketType}
                          ticket={ticket}
                          shouldShowResponseBy={this.props.isTicketEscalationEnabled}
                        />
                      </div>
                    ) : null
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
