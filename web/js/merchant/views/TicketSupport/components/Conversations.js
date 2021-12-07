import React, { Fragment } from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { Link, withRouter } from 'react-router-dom';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import {
  SAMPLE_TICKET,
  MAX_CONVERSATION,
  MIN_TIME_TO_REFRESH,
  PRERECORDED_RESPONSES,
} from './data';
import {
  getExpiryTime,
  getEscalationType,
  getResponseArrivalType,
  getTicketStatus,
} from '../utils';
import Ticket from './Ticket';
import Message from './Message';
import { merchantFetch } from 'merchant/utils/ajax';
import Spinner from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  fetchSupportTickets,
  replyToConversation,
  TICKET_BASE_URL,
} from 'merchant/reducers/config';
import Reply from './Reply';
import { showNotification } from 'merchant_common/reducers/notifications';
import TicketRevamped from './TicketRevamped';
import ReplyRevamped from './ReplyRevamped';
import FailedScreen from './FailedScreen';

@withRouter
@connect(
  (state) => {
    return {
      ...state.session,
      ...state.config.config,
      scheduleCallConfig: state.config.scheduleCallConfig,
      user: state.session.user,
    };
  },
  {
    fetchSupportTickets,
    showNotification,
    replyToConversation,
  },
)
export default class Conversations extends React.Component {
  state = {
    ticket: SAMPLE_TICKET,
    error: false,
    conversations: {
      data: { 1: [] },
      loading: false,
    },
    toggleReply: false,
    loadingTicket: true,
    size: MAX_CONVERSATION,
    current_page: 1,
    isReplyAdded: false,
  };

  goNext = (page) => {
    // Disable local caching because file might expire
    const TICKET_ID = this.props.match.params.id;
    // eslint-disable-next-line react/no-access-state-in-setstate
    const c = this.state.conversations;
    c.loading = true;
    this.setState({ conversations: c });

    merchantFetch({
      url: `${TICKET_BASE_URL}/${TICKET_ID}/conversations`,
      mode: 'live',
    })
      .then((e) => {
        // eslint-disable-next-line react/no-access-state-in-setstate
        const conversations = this.state.conversations;
        conversations.loading = false;
        conversations.data[page] =
          e.data instanceof Array ? e.data : Object.entries(e.data).map((C) => C[1]);
        this.setState({ conversations });
        this.setTimerToReload(conversations.data[page]);
      })
      .catch(() => {
        c.loading = false;
        this.setState({ conversations: c });
      });
  };

  track(action, label) {
    window.rzpAnalytics({
      eventCategory: 'Ticket Dashboard',
      eventAction: action,
      eventLabel: label,
    });
  }

  trackRenderTicket = () => {
    const ticket = this.state.ticket;
    analyticsTrack({
      objectName: 'show ticket details',
      actionName: 'rendered',
      screen: 'support tickets',
      properties: {
        ticketId: ticket ? ticket.ticket_id : 'NA',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  loadTicketDetails() {
    return merchantFetch({ url: `${TICKET_BASE_URL}/${this.props.match.params.id}`, mode: 'live' })
      .then((e) => {
        let ticket = e.data;
        let error = false;
        if (Array.isArray(e.data)) {
          ticket = SAMPLE_TICKET;
          error = true;
        }
        this.setState({
          ticket,
          loadingTicket: false,
          error,
        });
        this.trackRenderTicket();
      })
      .catch((e) => {
        this.track('conversation loading failed', 'Conversation | Status: Failed');
        this.props.showNotification({
          type: 'error',
          message: `Failed to load conversation, please try later! Status CODE: ${
            e.code || 'UNKNOWN'
          }`,
        });
      });
  }

  componentDidMount() {
    this.loadTicketDetails();
    this.goNext(1);
  }

  componentWillUnmount() {
    this.clearTimer();
  }

  clearTimer() {
    if (this.refreshIntervalID) clearTimeout(this.refreshIntervalID);
  }

  setTimerToReload(currentConversations) {
    // Clear previous timers
    this.clearTimer();

    // Get all attachments in one array
    const attachments = [];
    currentConversations.forEach((conversation) => {
      if (conversation.attachments && conversation.attachments.length) {
        attachments.push(...conversation.attachments);
      }
    });

    // If there are no attachments skip adding timer
    if (attachments.length === 0) return;

    // Get the min relative time for page to refresh from attachement expiry time
    const expiryAtachhmentTimings = attachments.map((attachment) => {
      return getExpiryTime(attachment.attachment_url);
    });

    const timeToRefresh = Math.min(...expiryAtachhmentTimings, MIN_TIME_TO_REFRESH);

    this.refreshIntervalID = setTimeout(() => {
      this.goNext(this.state.current_page);
    }, timeToRefresh);
  }

  shouldBeVisible(ticket) {
    // Hide reply if it's a private message/escaltion reply
    if (ticket.private) return false;

    if (ticket.category !== 3) return true;

    let isEscalationReply = false;

    PRERECORDED_RESPONSES.forEach((RESPONSE) => {
      isEscalationReply = isEscalationReply || ticket.body_text.indexOf(RESPONSE) !== -1;
    });

    return !isEscalationReply;
  }

  openCallDetails = (id) => {
    if (window.rzpTicketSystem) {
      window.rzpTicketSystem.openModal(`#call-details`, {
        id,
      });
    }
  };

  openGrievanceFlow(ticket) {
    if (window.rzpTicketSystem) {
      window.rzpTicketSystem.openModal('#grievance-new', {
        ticketID: ticket.id,
      });
    }
  }

  render() {
    let total_conversations = [];
    const TICKET_ID = this.props.match.params.id;
    const has_callback = this.state.ticket.tags.includes('callback');
    Object.keys(this.state.conversations.data).forEach((k) => {
      total_conversations.push(...this.state.conversations.data[k]);
    });
    total_conversations = total_conversations.filter(this.shouldBeVisible);
    let message, popup;
    const MESSAGE = getResponseArrivalType(this.state.ticket);
    const STATUS = getTicketStatus(this.state.ticket);
    const responseFormatTime = moment(this.state.ticket.fr_due_by).format('DD MMM');
    const is_escalated = getEscalationType(this.state.ticket) === 'escalated';
    const can_be_escalated =
      getEscalationType(this.state.ticket) === 'able-to-escalate' && !has_callback;

    message = (
      <h3 class="fsz-14">
        This query is open and our team is working on it.{' '}
        {!has_callback ? (
          <>
            <span>You can</span>
            <br />
            <span>
              expect reply within <b>8 working hours</b>.
            </span>
          </>
        ) : null}
      </h3>
    );

    const isTicketCreatedByAgent = this.state.ticket.custom_fields.cf_created_by === 'agent';

    if (MESSAGE === 'waiting-for-customer') {
      message = (
        <h3 class="fsz-14">
          This query is open and our team is waiting for your reply. <br />
          <span>
            Please reply before: <b>{responseFormatTime}</b>
          </span>
        </h3>
      );
    }

    if (MESSAGE === 'reply-before-due') {
      message = (
        <h3 class="fsz-14">
          This query is open and our team is waiting for your reply. <br />
          <span class="text-danger">
            Please reply before: <b>{responseFormatTime}</b>
          </span>
        </h3>
      );
    }

    if (is_escalated) {
      message = (
        <h3 class="fsz-14">
          This query is open and our team is working on it. You <br /> can expect reply before:{' '}
          <b style={{ color: '#F89A23' }} class="text-warning">
            {responseFormatTime}
          </b>
        </h3>
      );
    }
    if (STATUS === 'Work In Progress' && MESSAGE === 'within-expected-time') {
      message = (
        <h3 class="fsz-14">
          This query is open and our team is working on it. You <br /> can expect reply before:{' '}
          <b style={{ color: '#F89A23' }} class="text-warning">
            {responseFormatTime}
          </b>
        </h3>
      );
    }

    if (MESSAGE === 'Closed') {
      message = (
        <h3 class="fsz-14">
          This query has been marked closed. If you need further help, please{' '}
          <a onClick={() => window.rzpTicketSystem && window.rzpTicketSystem.openModal(`#ticket`)}>
            <b>create a new query</b>
          </a>
        </h3>
      );
    }

    if (MESSAGE === 'Resolved') {
      message = (
        <h3 class="fsz-14">
          This query has been marked resolved. If you need further help, please{' '}
          <a onClick={() => window.rzpTicketSystem && window.rzpTicketSystem.openModal(`#ticket`)}>
            <b>create a new query</b>
          </a>
          .
        </h3>
      );
    }

    const isNewSupportDashboard = this.props.user.isTicketRevampFlowEnabled;

    if (this.state?.ticket?.status === 5 && MESSAGE !== 'Closed' && isNewSupportDashboard) {
      if (this.state.toggleReply) {
        message = '';
      } else {
        message = (
          <h3 class="fsz-14">
            This query has been marked closed. {`Didn't`} get satisfied response?{' '}
            <a
              onClick={() => {
                this.setState({ toggleReply: true });
              }}
            >
              <b>Re-open Query</b>
            </a>
            .
          </h3>
        );
      }
    }

    const isScheduleCallbackEnabled = this.props.user.isScheduleCallbackEnabled;
    const ticketType = this.state.ticket?.custom_fields?.cf_created_by || 'merchant';

    const isLoading = this.state.conversations.loading || this.state.loadingTicket;
    return isNewSupportDashboard ? (
      <div class="content-wrapper content-sm ticket-support">
        <div className="panel">
          {this.state.error ? (
            <FailedScreen />
          ) : this.state?.conversations?.loading ? (
            <div className="ticket-cont-spinner">
              <Spinner />
            </div>
          ) : (
            <div className="panel-body" style={{ padding: 0 }}>
              <h3>
                {' '}
                <div className="row" style={{ marginBottom: '20px', marginLeft: 0 }}>
                  <div className="col-xs-12">
                    <span>
                      <Link
                        to={`/ticket-support/tickets/${ticketType}`}
                        onClick={() => {
                          window.rzpAnalytics({
                            eventCategory: 'Ticket Dashboard',
                            eventAction: 'view all tickets clicked',
                            eventLabel: `Tickets`,
                          });
                        }}
                      >
                        <i className="i i-arrow-back" />{' '}
                        <span style={{ fontSize: '16px' }}>View All Tickets</span>
                      </Link>
                    </span>
                  </div>
                </div>
              </h3>
              {!isLoading && (
                <TicketRevamped
                  logo_url={this.props.user.logo_url}
                  ticket={this.state.ticket}
                  totalConversations={total_conversations}
                  ticketID={TICKET_ID}
                  isReplyAdded={this.state.isReplyAdded}
                />
              )}

              <div>
                <div class="ticket-replies-container">
                  {isScheduleCallbackEnabled ? (
                    <div className="q-open">
                      {message}
                      {!(MESSAGE === 'Closed' || MESSAGE === 'Resolved') &&
                      this.state.ticket?.status !== 5 ? (
                        <div className="row flex">
                          <button
                            onClick={() => {
                              this.setState((prevState) => {
                                return { toggleReply: !prevState.toggleReply };
                              });
                            }}
                            style={{ position: 'relative' }}
                            className={`btn btn-outline ${this.state.toggleReply ? 'active' : ''}`}
                          >
                            {' '}
                            <i className="i i-reply" /> Send a reply
                            {this.state.toggleReply ? (
                              <i className="i i-caret-down chev-down" />
                            ) : null}
                          </button>
                          {(moment().diff(this.state.ticket?.fr_due_by, 'hours') > 0 ||
                            is_escalated) && (
                            <span>
                              {!isTicketCreatedByAgent && (
                                <button
                                  className={`btn btn-outline grievance-related-btn ${
                                    is_escalated ? 'btn-warning' : ''
                                  } ${!can_be_escalated ? 'disabled-style' : ''}`}
                                  onClick={() => {
                                    if (can_be_escalated) {
                                      this.openGrievanceFlow(this.state.ticket);
                                    }
                                  }}
                                >
                                  <i className="i i-followup" />{' '}
                                  {is_escalated ? 'Requested follow-up' : 'Request follow-up'}
                                </button>
                              )}
                              {!can_be_escalated ? (
                                <Popover align="bottom" theme="dark">
                                  <PopoverBody>
                                    {popup ? (
                                      popup
                                    ) : (
                                      <span>
                                        {has_callback
                                          ? is_escalated
                                            ? `You can expect reply before: ${responseFormatTime}`
                                            : `We will resolve this query over call`
                                          : is_escalated
                                          ? `You can expect reply before: ${responseFormatTime}`
                                          : `You can expect reply    working hours.`}
                                      </span>
                                    )}
                                  </PopoverBody>
                                </Popover>
                              ) : null}
                            </span>
                          )}
                          {!has_callback ? (
                            this.props.scheduleCallConfig.is_eligible ? (
                              <button
                                onClick={() => {
                                  if (window.rzpTicketSystem) {
                                    window.rzpTicketSystem.openModal(`#schedule-call`, {
                                      ticket: this.state.ticket,
                                    });
                                  }
                                }}
                                className="btn btn-outline"
                                disabled={has_callback}
                              >
                                {' '}
                                <i className="i i-call-new" /> Request a call
                              </button>
                            ) : null
                          ) : (
                            <button className="btn btn-outline requested">
                              {' '}
                              <i className="i i-call-new" /> <span>Call requested,</span>{' '}
                              <b
                                onClick={() =>
                                  this.openCallDetails(
                                    this.state.ticket.custom_fields.cf_callback_id,
                                  )
                                }
                                class="details"
                              >
                                View Details
                              </b>
                            </button>
                          )}
                        </div>
                      ) : null}
                    </div>
                  ) : null}

                  {isLoading && (
                    <div className="ticket-cont-spinner">
                      <Spinner />
                    </div>
                  )}
                  {(isScheduleCallbackEnabled ? this.state.toggleReply : true) ? (
                    <ReplyRevamped
                      email={this.props.user.contact_email}
                      last={total_conversations.length === 0}
                      logo_url={this.props.user.logo_url}
                      replyToConversation={this.props.replyToConversation}
                      ticket={this.state.ticket}
                      ticketID={TICKET_ID}
                      onClose={() => {
                        this.setState({ toggleReply: false });
                      }}
                      onSuccess={(reply) => {
                        this.setState((prevState) => {
                          const data = { ...prevState.conversations.data };
                          const k = Object.keys(prevState.conversations.data);
                          const last = k[k.length - 1];
                          if (data[last].length < prevState.size) {
                            data[last].push(reply);
                          }
                          return { data };
                        });
                      }}
                    />
                  ) : null}
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    ) : (
      <div class="content-wrapper content-sm ticket-support">
        <div className="panel">
          <div className="panel-body" style={{ padding: 0 }}>
            <h3>
              {' '}
              <div className="row" style={{ marginBottom: '20px' }}>
                <div className="col-xs-12">
                  <span>
                    <a
                      onClick={() => {
                        this.props.history.goBack();
                        window.rzpAnalytics({
                          eventCategory: 'Ticket Dashboard',
                          eventAction: 'view all tickets clicked',
                          eventLabel: `Tickets`,
                        });
                      }}
                    >
                      <i className="i i-arrow-back" />{' '}
                      <span style={{ fontSize: '16px' }}>View All Tickets</span>
                    </a>
                  </span>
                </div>
              </div>
            </h3>
            {!isLoading && (
              <Ticket
                logo_url={this.props.user.logo_url}
                ticket={this.state.ticket}
                ticketID={TICKET_ID}
              />
            )}

            <div>
              <h1 className="ticket-replies-title">
                {total_conversations && total_conversations.length > 0 ? 'All Replies' : ''}
              </h1>
              <div class="ticket-replies-container">
                <div className="Message-Cards-Container border-solid">
                  {total_conversations &&
                    total_conversations.map((conversation, i) => {
                      return (
                        <Message
                          last={i == total_conversations.length - 1}
                          ticket={this.state.ticket}
                          key={i}
                          message={conversation}
                        />
                      );
                    })}
                </div>
                {isScheduleCallbackEnabled ? (
                  <div className="q-open">
                    {message}
                    {!(MESSAGE === 'Closed' || MESSAGE === 'Resolved') ? (
                      <div className="row flex">
                        <button
                          onClick={() => {
                            this.setState((prevState) => {
                              return { toggleReply: !prevState.toggleReply };
                            });
                          }}
                          style={{ position: 'relative' }}
                          className={`btn btn-outline ${this.state.toggleReply ? 'active' : ''}`}
                        >
                          {' '}
                          <i className="i i-reply" /> Send a reply
                          {this.state.toggleReply ? (
                            <i className="i i-caret-down chev-down" />
                          ) : null}
                        </button>

                        <span>
                          {!isTicketCreatedByAgent && (
                            <button
                              className={`btn btn-outline grievance-related-btn ${
                                is_escalated ? 'btn-warning' : ''
                              } ${!can_be_escalated ? 'disabled-style' : ''}`}
                              onClick={() => {
                                if (can_be_escalated) {
                                  this.openGrievanceFlow(this.state.ticket);
                                }
                              }}
                            >
                              <i className="i i-followup" />{' '}
                              {is_escalated ? 'Requested follow-up' : 'Request follow-up'}
                            </button>
                          )}
                          {!can_be_escalated ? (
                            <Popover align="bottom" theme="dark">
                              <PopoverBody>
                                {has_callback
                                  ? is_escalated
                                    ? `You can expect reply before: ${responseFormatTime}`
                                    : `We will resolve this query over call`
                                  : is_escalated
                                  ? `You can expect reply before: ${responseFormatTime}`
                                  : `You can expect reply within 8 working hours.`}
                              </PopoverBody>
                            </Popover>
                          ) : null}
                        </span>
                        {!has_callback ? (
                          this.props.scheduleCallConfig.is_eligible ? (
                            <button
                              onClick={() => {
                                if (window.rzpTicketSystem) {
                                  window.rzpTicketSystem.openModal(`#schedule-call`, {
                                    ticket: this.state.ticket,
                                  });
                                }
                              }}
                              className="btn btn-outline"
                              disabled={has_callback}
                            >
                              {' '}
                              <i className="i i-call-new" /> Request a call
                            </button>
                          ) : null
                        ) : (
                          <button className="btn btn-outline requested">
                            {' '}
                            <i className="i i-call-new" /> <span>Call requested,</span>{' '}
                            <b
                              onClick={() =>
                                this.openCallDetails(this.state.ticket.custom_fields.cf_callback_id)
                              }
                              class="details"
                            >
                              View Details
                            </b>
                          </button>
                        )}
                      </div>
                    ) : null}
                  </div>
                ) : null}

                {isLoading && (
                  <div className="ticket-cont-spinner">
                    <Spinner />
                  </div>
                )}
                {(isScheduleCallbackEnabled ? this.state.toggleReply : true) ? (
                  <Reply
                    email={this.props.user.contact_email}
                    last={total_conversations.length === 0}
                    logo_url={this.props.user.logo_url}
                    replyToConversation={this.props.replyToConversation}
                    ticket={this.state.ticket}
                    ticketID={TICKET_ID}
                    onSuccess={(reply) => {
                      this.setState((prevState) => {
                        const data = { ...prevState.conversations.data };
                        const k = Object.keys(prevState.conversations.data);
                        const last = k[k.length - 1];
                        if (data[last].length < prevState.size) {
                          data[last].push(reply);
                        }
                        return { data };
                      });
                      this.props.showNotification({
                        type: 'success',
                        message: 'Reply has been sent',
                        closeTimeout: 5000,
                      });
                    }}
                  />
                ) : null}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
