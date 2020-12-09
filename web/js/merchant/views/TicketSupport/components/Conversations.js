import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { Fragment } from 'react';
import { param_to_qs, SAMPLE_TICKET, MAX_CONVERSATION } from './data.js';
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
    if (!(this.state.conversations.data[page] && this.state.conversations.data[page].length)) {
      const TICKET_ID = this.props.match.params.id;
      const c = this.state.conversations;
      c.loading = true;
      this.setState({ conversations: c });

      const params = {
        page: page,
        per_page: this.state.size,
        fd_instance: this.props.match.params.instance,
      };
      const query = param_to_qs(params);

      merchantFetch(`${TICKET_BASE_URL}/${TICKET_ID}/conversations?${query}`)
        .then((e) => {
          const conversations = this.state.conversations;
          conversations.loading = false;
          conversations.data[page] = e.data;
          this.setState({ conversations: conversations });
        })
        .catch(() => {
          const conversations = this.state.conversations;
          c.loading = false;
          this.setState({ conversations: c });
        });
    }
  };

  componentDidMount() {
    this.goNext(1);
    merchantFetch(
      `${TICKET_BASE_URL}/${this.props.match.params.id}?fd_instance=${this.props.match.params.instance}`,
    ).then((e) => {
      this.setState({ ticket: e.data });
    });
  }

  render() {
    let conversations = [];
    let total_conversations = [];
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
              <Ticket logo_url={this.props.user.logo_url} ticket={this.state.ticket} />
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
