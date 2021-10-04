import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import Attachment from './Attachment';

const RAZORPAY_LOGO = `https://razorpay.com/assets/razorpay-glyph.svg`;
@withRouter
@connect((state) => {
  return {
    ...state.session,
    ...state.config.config,
    user: state.session.user,
  };
})
export default class Message extends React.Component {
  state = {
    showCompleteReply: this.props.showExpandedReply,
  };

  componentWillReceiveProps(nextProps) {
    if (nextProps.showExpandedReply) {
      this.setState({ showCompleteReply: true });
    }
  }

  renderMessage = (from_razorpay) => {
    const { showCompleteReply } = this.state;
    if (from_razorpay) {
      const { body } = this.props.message;

      return (
        <div
          className={`message-body revamped body mt-0 ${
            showCompleteReply || this.props.showExpandedReply ? '' : 'truncate'
          }`}
          dangerouslySetInnerHTML={{
            __html: `<div>${body}</div>`,
          }}
        />
      );
    } else {
      const { body_text } = this.props.message;

      return (
        <div
          className={`message-body revamped body mt-0 ${showCompleteReply ? '' : 'truncated'}`}
          dangerouslySetInnerHTML={{
            __html: `<div>${body_text}</div>`,
          }}
        />
      );
    }
  };

  toggleReplyState = () => {
    const { showCompleteReply } = this.state;
    this.setState({ showCompleteReply: !showCompleteReply });
  };

  render() {
    const { showCompleteReply } = this.state;
    const isTicketRevampFlowEnabled = this.props.user.isTicketRevampFlowEnabled;
    const from_dashboard_user = this.props.message.user_id === this.props.ticket.requester_id;
    let from_razorpay = !from_dashboard_user;
    if (this.props.message.incoming) {
      from_razorpay = false;
    }

    let img = <i className="i i-ticket-user message-user-circle-revamped" />;
    if (!from_razorpay) {
      img = this.props.user.logo_url ? (
        <div class="revamped-user-image">
          <img class="img-round revamped-user-image" src={this.props.user.logo_url} />
        </div>
      ) : (
        <div class="revamped-user-image">
          <i className="i i-ticket-user message-user-circle-revamped" />
        </div>
      );
    } else {
      img = (
        <div class="revamped-user-image">
          <img class="img-round revamped-user-image" src={RAZORPAY_LOGO} />
        </div>
      );
    }
    const name = !from_razorpay
      ? from_dashboard_user
        ? this.props.user.name
        : `CC: ${this.props.message.from_email}`
      : 'Razorpay Support';

    const attachments = this.props.message.attachments;

    return (
      <div
        className={`message ${isTicketRevampFlowEnabled ? 'revamped' : ''}
          panel ticket-row-panel mt-0 border-bt-0 mb-0`}
      >
        <div className="panel-body p-v-24">
          <div className="row min-ht-56" onClick={this.toggleReplyState}>
            <div className="col-xs-2 w-auto">{img}</div>
            <div className="col-xs-10 reply-message-container pr-0" style={{ marginTop: 6 }}>
              <h5 className="title-container">
                <div className="row flex pr-0">
                  <div className="col-xs-5 message-owner">
                    <b className="name">{name}</b>
                  </div>
                  <div className="col-xs-7 text-right created-time">
                    {moment(this.props.message.created_at).fromNow('h')} ago
                  </div>
                </div>
              </h5>
              {showCompleteReply && (
                <p class="message-to" style={{ marginBottom: 24 }}>
                  To :{' '}
                  {from_razorpay ? this.props.message.to_emails.join(', ') : 'Razorpay Account'}
                </p>
              )}

              <div
                class="message-body revamped"
                style={{ marginTop: showCompleteReply || this.props.showExpandedReply ? 0 : '6px' }}
              >
                {this.renderMessage(from_razorpay)}
              </div>
              {attachments && attachments.length !== 0 && (
                <div className="message-body revamped body mt-20">
                  {attachments.map((file) => (
                    <Attachment key={file.id} file={file} />
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  }
}
