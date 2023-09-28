import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, noop } from 'common/utils/rzp-utils';
import { getCommonSupportProperties } from 'merchant/components/Support/getCommonSupportProperties';
import {
  SAMPLE_TICKET,
  MAX_CONVERSATION,
  MIN_TIME_TO_REFRESH,
  PRERECORDED_RESPONSES,
  TICKET_STATUS_LABELS,
} from './data';
import {
  getExpiryTime,
  getEscalationType,
  getResponseArrivalType,
  getTicketStatus,
  createWorkFlowTicket,
} from 'merchant/views/TicketSupport/utils';
import { merchantFetch } from 'merchant/utils/ajax';
import Spinner from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  fetchSupportTickets,
  replyToConversation,
  TICKET_BASE_URL,
  FETCH_WORKFLOWS,
  FETCH_TICKET,
  FETCH_TICKETS,
} from 'merchant/reducers/config';
import Reply from './Reply';
import { showNotification } from 'merchant_common/reducers/notifications';
import FailedScreen from './FailedScreen';
import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const Ticket = lazy(() => import(/* webpackChunkName: 'Ticket' */ './Ticket'));

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
class Conversations extends React.Component {
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
    shouldCreateNewTicketForWorkflow: false,
    workflow: {},
  };

  goNext = (page) => {
    const { match = {} } = this.props;
    const { ticket = {}, conversations = {} } = this.state;
    // Disable local caching because file might expire
    let TICKET_ID = ticket?.id;
    if (TICKET_ID === SAMPLE_TICKET.id) {
      TICKET_ID = match?.params?.id;
    }
    // eslint-disable-next-line react/no-access-state-in-setstate
    const c = conversations;
    c.loading = true;
    this.setState({ conversations: c });

    merchantFetch({
      url: `${TICKET_BASE_URL}/${TICKET_ID}/conversations?per_page=40`,
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
    window.rzpAnalytics?.({
      eventCategory: 'Ticket Dashboard',
      eventAction: action,
      eventLabel: label,
    });
  }

  trackRenderTicket = () => {
    const { ticket } = this.state;
    analyticsTrack({
      objectName: 'show ticket details',
      actionName: 'rendered',
      screen: 'support tickets',
      properties: {
        ticketId: ticket ? ticket.ticket_id : 'NA',
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties({ location: 'My Account' }),
      },
    });
  };

  handleFetchWorkFlow = () => {
    const { match = {}, showNotification: _showNotification } = this.props;

    const id = match?.params?.id?.replace('w_action_', '');
    return merchantFetch({
      url: FETCH_WORKFLOWS,
      mode: 'live',
      method: 'POST',
      data: { id },
    })
      .then((e) => {
        const workflow = e?.data?.items?.[0] || {};

        this.setState({
          workflow,
        });
      })
      .catch((e) => {
        this.track('conversation loading failed', 'Conversation | Status: Failed');
        _showNotification({
          type: 'error',
          message: `Failed to load conversation, please try later! Status CODE: ${
            e.code || 'UNKNOWN'
          }`,
        });
      });
  };

  handleFetchWorkFlowTicket = () => {
    const { match = {}, showNotification: _showNotification, user } = this.props;

    const payload = {
      cf_workflow_id: match?.params?.id,
      tags: ['workflow_ticket'],
    };

    const requestPayload = {
      url: TICKET_BASE_URL,
      mode: 'live',
      data: payload,
    };
    if (user.isFetchTicketsApiMigration) {
      requestPayload.url = FETCH_TICKETS;
      requestPayload.method = 'post';
      requestPayload.data.type = 'support_dashboard';
      requestPayload.headers = {
        'Content-Type': 'application/json',
      };
    }
    return merchantFetch(requestPayload)
      .then((e) => {
        const results = e?.data?.results || [];
        const ticket = results?.[0] || {};

        this.setState(
          {
            ticket,
            shouldCreateNewTicketForWorkflow: results.length === 0,
            loadingTicket: false,
          },
          () => {
            this.trackRenderTicket();
            this.goNext(1);
          },
        );
      })
      .catch((e) => {
        this.track('conversation loading failed', 'Conversation | Status: Failed');
        _showNotification({
          type: 'error',
          message: `Failed to load conversation, please try later! Status CODE: ${
            e.code || 'UNKNOWN'
          }`,
        });
      });
  };

  loadTicketDetails() {
    const { showNotification: _showNotification, match = {}, user } = this.props;

    const requestPayload = {
      url: `${TICKET_BASE_URL}/${match?.params?.id}`,
      mode: 'live',
    };

    if (user.isGetTicketApiMigration) {
      requestPayload.url = FETCH_TICKET;
      requestPayload.method = 'post';
      requestPayload.data = {
        id: match?.params?.id,
        type: 'support_dashboard',
      };
      requestPayload.headers = {
        'Content-Type': 'application/json',
      };
    }

    return merchantFetch(requestPayload)
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
        _showNotification({
          type: 'error',
          message: `Failed to load conversation, please try later! Status CODE: ${
            e.code || 'UNKNOWN'
          }`,
        });
      });
  }

  loadWorkflowDetails = () => {
    this.handleFetchWorkFlow();
    this.handleFetchWorkFlowTicket();
  };

  componentDidMount() {
    const { match = {} } = this.props;

    const isWorkflow = match?.params?.instance === 'workflow';
    if (isWorkflow) {
      this.loadWorkflowDetails();
    } else {
      this.loadTicketDetails();
      this.goNext(1);
    }
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
      isEscalationReply = isEscalationReply || ticket?.body_text?.indexOf(RESPONSE) !== -1;
    });

    return !isEscalationReply;
  }

  openCallDetails = (id, ticket) => {
    if (window.rzpTicketSystem) {
      window.rzpTicketSystem.openModal(`#call-details`, {
        id,
        ticket,
      });
    }
  };

  handleCreateNewWorkflowTicket = ({ successCallback = noop, errorCallback = noop } = {}) => {
    const { workflow } = this.state;
    const { user, showNotification: _showNotification } = this.props;
    createWorkFlowTicket(workflow, user)
      .then((e) => {
        const ticket = e?.data || {};
        this.setState(
          {
            ticket,
            shouldCreateNewTicketForWorkflow: false,
          },
          () => {
            successCallback(ticket);
          },
        );
      })
      .catch((e) => {
        errorCallback(e);
        _showNotification({
          type: 'error',
          message: `Failed to create workflow ticket, please try later! Status CODE: ${
            e.code || 'UNKNOWN'
          }`,
        });
      });
  };

  openGrievanceFlow(ticket) {
    const { shouldCreateNewTicketForWorkflow } = this.state;

    if (shouldCreateNewTicketForWorkflow) {
      this.handleCreateNewWorkflowTicket({
        successCallback: (newTicket) => {
          window?.rzpTicketSystem?.openModal?.('#raise-grievance', {
            ticketID: newTicket.id,
          });
        },
      });
    } else {
      window?.rzpTicketSystem?.openModal?.('#raise-grievance', {
        ticketID: ticket.id,
      });
    }
    analyticsTrack({
      objectName: 'Request follow-up',
      actionName: 'clicked',
      screen: 'support tickets',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties({ location: 'My Account' }),
        ticket_id: ticket?.ticket_id || ticket?.id,
      },
    });
  }
  handleToggleReplySection() {
    const { ticket = {} } = this.state;
    analyticsTrack({
      objectName: 'Reply Now button',
      actionName: 'clicked',
      screen: 'support tickets',
      properties: {
        ticketId: ticket?.ticket_id || ticket?.id || 'NA',
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties({ location: 'My Account' }),
      },
    });

    this.setState((prevState) => {
      return { toggleReply: !prevState.toggleReply };
    });
  }

  getExpectedReplyTime = () => {
    const { match = {} } = this.props;
    const { workflow } = this.state;
    const isWorkflow = match?.params?.instance === 'workflow';
    if (!isWorkflow || !workflow?.id) {
      return 8;
    }
    const createdAt = moment(parseInt(workflow?.created_at, 10));
    const dueDate = moment(parseInt(workflow?.due_date, 10));
    const duration = moment.duration(dueDate.diff(createdAt));
    const hours = duration.asHours();

    return hours;
  };
  getPopoverMessage(is_escalated, has_callback) {
    if (is_escalated) return 'We are working on resolving this as soon as possible.';
    if (has_callback) return 'We will resolve this query over call';
    return 'You can expect reply within working hours.';
  }
  render() {
    let total_conversations = [];
    const {
      match = {},
      scheduleCallConfig,
      user = {},
      replyToConversation: _replyToConversation,
    } = this.props;
    const {
      conversations = {},
      ticket = {},
      shouldCreateNewTicketForWorkflow,
      workflow,
      toggleReply,
      loadingTicket,
      error,
      isReplyAdded,
    } = this.state;

    const isWorkflow = match?.params?.instance === 'workflow';

    let TICKET_ID = ticket?.id;
    if (TICKET_ID === SAMPLE_TICKET.id) {
      TICKET_ID = match?.params?.id;
    }
    const tags = ticket?.tags || [];
    const has_callback = tags?.includes('callback');
    const has_click_to_call = tags?.includes('instant_callback_requested');
    Object.keys(conversations?.data)?.forEach((k) => {
      total_conversations.push(...conversations?.data?.[k]);
    });
    total_conversations = total_conversations.filter(this.shouldBeVisible);
    let message;
    const MESSAGE = getResponseArrivalType(ticket, workflow);
    const STATUS = getTicketStatus(ticket, workflow);
    const responseFormatTime = moment(
      workflow?.due_date ? parseInt(workflow?.due_date, 10) : ticket.fr_due_by,
    ).format('DD MMM');
    const is_escalated = getEscalationType(ticket, workflow) === 'escalated';
    const can_be_escalated =
      getEscalationType(ticket, workflow) === 'able-to-escalate' && !has_callback;

    message = (
      <h3 className="fsz-14">
        This query is open and our team is working on it.{' '}
        {!(has_callback || has_click_to_call) ? (
          <>
            <span>You can</span>
            <br />
            <span>
              expect reply within <b>{this.getExpectedReplyTime()} working hours</b>.
            </span>
          </>
        ) : null}
      </h3>
    );

    const isTicketCreatedByAgent = ticket?.custom_fields?.cf_created_by === 'agent';

    if (MESSAGE === 'waiting-for-customer') {
      message = (
        <h3 className="fsz-14">
          This query is open and our team is waiting for your reply. <br />
          <span>
            Please reply before: <b>{responseFormatTime}</b>
          </span>
        </h3>
      );
    }

    if (MESSAGE === 'reply-before-due') {
      message = (
        <h3 className="fsz-14">
          This query is open and our team is waiting for your reply. <br />
          <span className="text-danger">
            Please reply before: <b>{responseFormatTime}</b>
          </span>
        </h3>
      );
    }

    if (is_escalated) {
      message = (
        <h3 className="fsz-14">
          We’re working on this request on-priority, and will share an <br /> update soon by:{' '}
          <b style={{ color: '#F89A23' }} className="text-warning">
            {responseFormatTime}
          </b>
        </h3>
      );
    }
    if (
      [TICKET_STATUS_LABELS.BEING_PROCESSED, TICKET_STATUS_LABELS.IN_PROGRESS].includes(STATUS) &&
      MESSAGE === 'within-expected-time'
    ) {
      message = (
        <h3 className="fsz-14">
          This query is open and our team is working on it. You <br /> can expect reply before:{' '}
          <b style={{ color: '#F89A23' }} className="text-warning">
            {responseFormatTime}
          </b>
        </h3>
      );
    }

    if (MESSAGE === 'Closed') {
      message = (
        <h3 className="fsz-14">
          This query has been marked closed. If you need further help, please{' '}
          <a onClick={() => window.rzpTicketSystem && window.rzpTicketSystem.openModal(`#ticket`)}>
            <b>create a new query</b>
          </a>
        </h3>
      );
    }

    if (MESSAGE === 'Resolved') {
      message = (
        <h3 className="fsz-14">
          This query has been marked resolved. If you need further help, please{' '}
          <a onClick={() => window.rzpTicketSystem && window.rzpTicketSystem.openModal(`#ticket`)}>
            <b>create a new query</b>
          </a>
        </h3>
      );
    }

    if (ticket?.status === 5 && MESSAGE !== 'Closed') {
      if (toggleReply) {
        message = '';
      } else {
        message = (
          <h3 className="fsz-14">
            {"This query has been marked closed. Didn't get satisfied response? "}
            <a
              role="button"
              tabIndex="0"
              onClick={() => {
                this.setState({ toggleReply: true });
              }}
            >
              <b>Re-open Query</b>
            </a>
          </h3>
        );
      }
    }

    const ticketType = ticket?.custom_fields?.cf_created_by || 'merchant';
    const isLoading = conversations?.loading || loadingTicket;

    const ticketTypeUrl = user.isAccountAndSettingsRevampEnabled
      ? `/business-settings/ticket-support/tickets/${ticketType}`
      : `/ticket-support/tickets/${ticketType}`;

    return (
      <div className="content-wrapper content-sm ticket-support">
        <div className="panel">
          {error ? (
            <FailedScreen />
          ) : conversations?.loading ? (
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
                        to={ticketTypeUrl}
                        onClick={() => {
                          window.rzpAnalytics?.({
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
                <SuspenseWithLoader>
                  <Ticket
                    logo_url={user.logo_url}
                    ticket={ticket}
                    totalConversations={total_conversations}
                    ticketID={TICKET_ID}
                    isReplyAdded={isReplyAdded}
                    workflow={workflow}
                  />
                </SuspenseWithLoader>
              )}
              <div>
                <div className="ticket-replies-container">
                  <div className="q-open">
                    {message}
                    {!(MESSAGE === 'Closed' || MESSAGE === 'Resolved') && ticket?.status !== 5 ? (
                      <div className="row flex flex-wrap">
                        <button
                          onClick={() => {
                            this.handleToggleReplySection();
                          }}
                          style={{ position: 'relative' }}
                          className={`btn btn-outline${toggleReply ? ' active' : ''}`}
                        >
                          {' '}
                          <i className="i i-reply" /> Send a reply
                          {toggleReply ? <i className="i i-caret-down chev-down" /> : null}
                        </button>
                        {(moment().diff(
                          ticket?.fr_due_by || parseInt(workflow?.due_date, 10),
                          'hours',
                        ) > 0 ||
                          is_escalated) && (
                          <span>
                            {!isTicketCreatedByAgent && (
                              <button
                                className={`btn btn-outline grievance-related-btn ${
                                  is_escalated ? 'btn-warning' : ''
                                } ${!can_be_escalated ? 'disabled-style' : ''}`}
                                onClick={() => {
                                  if (can_be_escalated) {
                                    this.openGrievanceFlow(ticket);
                                  }
                                }}
                              >
                                <i className="i i-followup" />{' '}
                                {is_escalated ? 'Requested status update' : 'Request status update'}
                              </button>
                            )}
                            {!can_be_escalated ? (
                              <Popover align="bottom" theme="dark">
                                <PopoverBody>
                                  <span>{this.getPopoverMessage(is_escalated, has_callback)}</span>
                                </PopoverBody>
                              </Popover>
                            ) : null}
                          </span>
                        )}
                        {!(has_callback || has_click_to_call) ? (
                          scheduleCallConfig?.is_eligible ? (
                            <button
                              onClick={() => {
                                if (window.rzpTicketSystem) {
                                  window.rzpTicketSystem.openModal(`#schedule-call`, {
                                    ticket,
                                  });
                                }
                              }}
                              className="btn btn-outline"
                              disabled={has_callback || has_click_to_call}
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
                                this.openCallDetails(ticket?.custom_fields?.cf_callback_id, ticket)
                              }
                              className="details"
                            >
                              View Details
                            </b>
                          </button>
                        )}
                      </div>
                    ) : null}
                  </div>

                  {isLoading && (
                    <div className="ticket-cont-spinner">
                      <Spinner />
                    </div>
                  )}
                  {toggleReply ? (
                    <Reply
                      email={user?.contact_email}
                      last={total_conversations.length === 0}
                      logo_url={user?.logo_url}
                      replyToConversation={_replyToConversation}
                      ticket={ticket}
                      ticketID={TICKET_ID}
                      shouldCreateNewTicketForWorkflow={shouldCreateNewTicketForWorkflow}
                      handleCreateNewWorkflowTicket={this.handleCreateNewWorkflowTicket}
                      isWorkflow={isWorkflow}
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
    );
  }
}

export default withRouter(Conversations);
