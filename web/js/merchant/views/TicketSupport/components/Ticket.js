import { connect } from 'react-redux';
import React from 'react';
import moment from 'moment';
import TicketStatus from './TicketStatus';
import Attachment from './Attachment';
import Message from './Message';
import sanitizer from 'common/utils/xss-sanitizer';
import { TICKET_STATUS_LABELS } from './data';
import { sanitizeRaySubcategory } from '../utils';
const RAZORPAY_LOGO = `https://razorpay.com/assets/razorpay-glyph.svg`;

@connect((state) => {
  return {
    ...state.session,
    ...state.config.config,
    user: state.session.user,
  };
})
export default class Ticket extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      showMoreConversation: false,
      expandLastReply: false,
      showFullMessage: false,
    };
  }

  componentDidMount() {
    window.rzpAnalytics?.({
      eventCategory: 'Ticket Dashboard',
      eventAction: 'ticket details fetched | Status: Success',
      eventLabel: `Tickets`,
    });
  }

  toggleFullReply = () => {
    const { showFullMessage } = this.state;
    this.setState({ showFullMessage: !showFullMessage });
  };

  render() {
    const { ticket, user, totalConversations, workflow } = this.props;
    const { showMoreConversation } = this.state;
    const isTicketCreatedByAgent = ticket?.custom_fields?.cf_created_by === 'agent';

    let img = this.props.logo_url ? (
      <img className="img-round mt-0" src={this.props.logo_url} alt="ticket-user-logo" />
    ) : (
      <i className="i i-ticket-user img-round" />
    );

    if (isTicketCreatedByAgent) {
      img = <img className="img-round" src={RAZORPAY_LOGO} alt="ticket-user-logo" />;
    }

    const category =
      workflow?.sub_category ||
      ticket?.custom_fields?.cf_requestor_subcategory ||
      ticket?.custom_fields?.cf_new_requester_sub_category;
    const subCategory =
      workflow?.item ||
      ticket?.custom_fields?.cf_new_requester_item ||
      ticket?.custom_fields?.cf_requester_item;
    const ticketConversationsLength = totalConversations?.length;
    const isRzpPosTicket = ticket?.type === 'Ezetap';
    const description =
      workflow?.state === TICKET_STATUS_LABELS.REJECTED
        ? `Rejection Reason: ${
            ticket?.rejection_reason ||
            'We apologise that we cannot support your request at this time. Please reach out to support for more queries.'
          }`
        : workflow?.description || ticket?.description;

    if (ticket || workflow) {
      return (
        <div className="message-container">
          <div className="title-section">
            <div className="title-text-container">
              <div className="category-container">
                {isTicketCreatedByAgent ? (
                  <p className="title-text">{ticket?.subject}</p>
                ) : isRzpPosTicket ? (
                  <>
                    <p className="title-text">{'In store/In person'}</p>
                    {<p className="separator">&#183;</p>}
                    <p className="title-text">{'RazorpayPOS'}</p>
                  </>
                ) : (
                  <>
                    <p className="title-text">{category}</p>
                    {category && subCategory && <p className="separator">&#183;</p>}
                    <p className="title-text">{sanitizeRaySubcategory(subCategory)}</p>
                  </>
                )}
              </div>
              <TicketStatus ticket={ticket} workflow={workflow} />
            </div>
            <div className="title-text-container sub-text" style={{ justifyContent: 'flex-start' }}>
              <p>#{workflow?.id || ticket?.ticket_id}</p>
              <p className="separator text-size-25">&#183;</p>
              <p>
                {moment(
                  workflow?.created_at ? parseInt(workflow.created_at, 10) : ticket.created_at,
                ).format('ddd, MMM D, YYYY, hh:mm A')}
              </p>
            </div>
          </div>
          <div className="user-details-container">
            <div className="user-section" onClick={this.toggleFullReply}>
              <div className="user-image">{img}</div>
              <div className="user-details">
                <p className="user">
                  {isTicketCreatedByAgent ? 'Razorpay Support' : user?.name}
                  <span className="created-time pull-right">
                    {moment(ticket.created_at).fromNow()}
                  </span>
                </p>
                {this.state.showFullMessage && !isTicketCreatedByAgent && (
                  <p className="message-to">To: Razorpay Account</p>
                )}
                {/* nosemgrep */}
                <p
                  className={`lh-18 user-ticket-description ${
                    this.state.showFullMessage ? '' : 'truncated'
                  }`}
                  dangerouslySetInnerHTML={{
                    __html: sanitizer(description),
                  }}
                />
              </div>
            </div>
            {ticket?.attachments?.length > 0 && (
              <div className="attachment-container">
                {ticket?.attachments?.map((file) => (
                  <Attachment key={file.id} file={file} />
                ))}
              </div>
            )}
          </div>

          {totalConversations?.length >= 3 && !showMoreConversation && (
            <div className="show-more-container">
              <div className="divider" />
              <button
                type="button"
                onClick={() => {
                  this.setState({ showMoreConversation: true });
                }}
                className="btn btn-outline requested show-more-button"
              >
                <p>
                  {totalConversations?.length - 2} more{' '}
                  {totalConversations?.length === 3 ? 'reply' : 'replies'}
                </p>

                <div className="icons-container">
                  <i className="i i-chevron-up icons-show-more" />
                  <i className="i i-chevron-down icons-show-more" />
                </div>
              </button>
              <div className="divider" />
            </div>
          )}
          <div>
            {((totalConversations.length >= 3 && showMoreConversation) ||
              totalConversations.length < 3) && (
              <>
                {totalConversations.length > 0 && <div className="solid-divider" />}

                {totalConversations.map((conversation, i) => {
                  return (
                    <>
                      <Message
                        showExpandedReply={
                          this.state.expandLastReply && i == totalConversations.length - 1
                        }
                        last={i === totalConversations.length - 1}
                        ticket={ticket}
                        key={i}
                        message={conversation}
                      />
                      {i !== ticketConversationsLength - 1 && <div className="solid-divider" />}
                    </>
                  );
                })}
              </>
            )}
            {totalConversations?.length >= 3 && !showMoreConversation && (
              <>
                <Message
                  last={false}
                  ticket={ticket}
                  key={3}
                  message={totalConversations[ticketConversationsLength - 2]}
                />
                <div className="solid-divider" />
                <Message
                  showExpandedReply={this.state.expandLastReply}
                  last={true}
                  ticket={ticket}
                  key={4}
                  message={totalConversations[ticketConversationsLength - 1]}
                />
              </>
            )}
          </div>
        </div>
      );
    }

    return null;
  }
}
