import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { Fragment } from 'react';
import { param_to_qs, SAMPLE_TICKET, MAX_CONVERSATION, MIN_TIME_TO_REFRESH } from './data.js';
import { getExpiryTime } from '../utils';
import Ticket from './Ticket';
import * as axios from 'axios';
import { withRouter } from 'react-router-dom';
import Message from './Message.js';
import { merchantFetch } from 'merchant/utils/ajax.js';
import Spinner from 'common/ui/Spinner';
import {
  fetchSupportTickets,
  replyToConversation,
  TICKET_BASE_URL,
} from 'merchant/reducers/config.js';
import Reply from './Reply.js';
import { showNotification } from 'merchant_common/reducers/notifications';

@withRouter
@connect(
  (state) => {
    return {
      ...state.session,
      ...state.config.config,
      user: state.session.user,
    };
  },
  {
    fetchSupportTickets: fetchSupportTickets,
    showNotification: showNotification,
    replyToConversation: replyToConversation,
  },
)
export default class Conversations extends React.Component {
  state = {
    ticket: SAMPLE_TICKET,
    conversations: {
      data: { 1: [] },
      loading: false,
    },
    size: MAX_CONVERSATION,
    current_page: 1,
  };

  goNext = (page) => {
    // Disable local caching because file might expire
    const TICKET_ID = this.props.match.params.id;
    const c = this.state.conversations;
    c.loading = true;
    this.setState({ conversations: c });

    merchantFetch({
      url: `${TICKET_BASE_URL}/${TICKET_ID}/conversations`,
      mode: 'live',
    })
      .then((e) => {
        const conversations = this.state.conversations;
        conversations.loading = false;
        conversations.data[page] = e.data;
        this.setState({ conversations: conversations });
        this.setTimerToReload(conversations.data[page]);
      })
      .catch(() => {
        const conversations = this.state.conversations;
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

  loadTicketDetails() {
    return merchantFetch({ url: `${TICKET_BASE_URL}/${this.props.match.params.id}`, mode: 'live' })
      .then((e) => {
        this.setState({ ticket: e.data });
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
    this.goNext(1);
    this.loadTicketDetails();
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
    let attachments = [];
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

  render() {
    let conversations = [];
    let total_conversations = [];
    const TICKET_ID = this.props.match.params.id;

    Object.keys(this.state.conversations.data).forEach((k) => {
      total_conversations.push(...this.state.conversations.data[k]);
    });
    total_conversations = total_conversations.filter((m) => !m.private);
    conversations = this.state.conversations.data[this.state.current_page] || [];
    const last_page =
      conversations.length < this.state.size ? null : !this.state.conversations.loading;
    return (
      <Fragment>
        <div class="content-wrapper content-sm ticket-support">
          <div className="panel">
            <div className="panel-body" style={{ padding: 0 }}>
              <h3>
                {' '}
                <div className="row" style={{ marginBottom: '20px' }}>
                  <div className="col-xs-12">
                    <span>
                      <Link
                        to={`/ticket-support/tickets`}
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
              <Ticket
                logo_url={this.props.user.logo_url}
                ticket={this.state.ticket}
                ticketID={TICKET_ID}
              />
              <div className="panel">
                <div className="panel-body message-panel-body" style={{ padding: 0 }}>
                  <div>
                    {total_conversations
                      // .filter(m => !m.private)
                      .map((conversation, i) => {
                        return (
                          <Message
                            last={i == total_conversations.length - 1}
                            ticket={this.state.ticket}
                            key={i}
                            message={conversation}
                          />
                        );
                      })}
                    {this.state.conversations.loading ? (
                      <div className="ticket-cont-spinner">
                        <Spinner />
                      </div>
                    ) : null}
                    {last_page && (
                      <div className="panel">
                        <div className="panel-body message-panel-body text-center">
                          <button
                            onClick={() => {
                              const current_page = this.state.current_page + 1;
                              this.setState({ current_page }, () => this.goNext(current_page));
                            }}
                            className="btn btn-link"
                          >
                            View More
                          </button>
                        </div>
                      </div>
                    )}
                    <Reply
                      email={this.props.user.contact_email}
                      last={total_conversations.length === 0}
                      logo_url={this.props.user.logo_url}
                      replyToConversation={this.props.replyToConversation}
                      ticket={this.state.ticket}
                      ticketID={TICKET_ID}
                      onSuccess={(reply) => {
                        const data = { ...this.state.conversations.data };
                        let k = Object.keys(this.state.conversations.data);
                        const last = k[k.length - 1];
                        if (data[last].length < this.state.size) {
                          data[last].push(reply);
                        }
                        this.setState({ data });
                        this.props.showNotification({
                          type: 'success',
                          message: 'Reply has been sent',
                          closeTimeout: 5000,
                        });
                      }}
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Fragment>
    );
  }
}
