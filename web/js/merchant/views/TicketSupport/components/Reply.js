import { connect } from 'react-redux';
import { Fragment } from 'react';

@connect((state) => {
  return {
    ...state.session,
    ...state.config.config,
    user: state.session.user,
  };
})
export default class Reply extends React.Component {
  constructor(props) {
    super(props);
    this.replyRef = React.createRef();
  }
  state = {
    cc: null,
    loading: false,
    body: null,
  };

  render() {
    let img = this.props.logo_url ? (
      <img class="img-round user-image" src={this.props.logo_url} />
    ) : (
      <i className="i i-user-circle reply-user-circle" />
    );
    return (
      <Fragment>
        <div className="message" style={{ marginBottom: 0 }}>
          <div
            className="panel ticket-row-panel"
            style={{ borderBottom: this.props.last ? '1px solid rgba(22,47,86,0.1)' : 'auto' }}
          >
            <div className="panel-body" style={{ paddingLeft: 0 }}>
              <div className="row">
                <div className="col-xs-2">{img}</div>
                <div className="col-xs-10">
                  <h5 style={{ marginBottom: 0 }}>
                    <div className="row">
                      <div className="col-xs-5 message-owner">
                        <b>{this.props.user.name}</b>
                      </div>
                      <div className="col-xs-7 text-right"></div>
                    </div>
                  </h5>
                  <div class="reply-quill">
                    <textarea
                      value={this.state.body}
                      onChange={(e) => this.setState({ body: e.target.value })}
                      cols="30"
                      rows="3"
                      className="form-control"
                      placeholder="Please type something..."
                      ref={this.replyRef}
                    />
                  </div>
                  <div>
                    <button
                      onClick={() => {
                        window.rzpAnalytics({
                          eventCategory: 'Ticket Dashboard',
                          eventAction: 'send reply clicked',
                          eventLabel: `Tickets`,
                        });
                        const body = {
                          body: this.state.body,
                          user_id: this.props.ticket.requester_id,
                          fd_instance: this.props.ticket.fd_instance,
                          cc_emails: this.props.ticket.cc_emails,
                          bcc_emails: this.props.ticket.bcc_emails,
                        };
                        this.setState({ loading: true });
                        this.props.replyToConversation(this.props.ticket.id, body).then((r) => {
                          this.setState({ loading: false, body: null });
                          this.replyRef.current.value = null;
                          window.rzpAnalytics({
                            eventCategory: 'Ticket Dashboard',
                            eventAction: 'reply delivered',
                            eventLabel: `Tickets | Status: Success`,
                          });
                          if (this.props.onSuccess) {
                            this.props.onSuccess(r.data);
                          }
                        });
                      }}
                      disabled={this.state.loading || !this.state.body}
                      className="btn btn-primary ticket-reply-btn"
                    >
                      {this.state.loading ? 'Sending' : 'Send Reply'}
                    </button>
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
