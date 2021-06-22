import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import * as axios from 'axios';
import { Fragment } from 'react';
import { tickets, statuses, conversations } from './data.js';
import { titleCase } from 'common/utils/rzp-utils.js';
import Ticket from './Ticket';
import Reply from './Reply';
import Attachment from './Attachment.js';
import { withRouter } from 'react-router-dom';

@withRouter
@connect((state) => {
  return {
    ...state.session,
    ...state.config.config,
    user: state.session.user,
  };
})
export default class Message extends React.Component {
  renderMessage(from_razorpay) {
    if (from_razorpay) {
      return (
        <div
          className="message-body body"
          style={{ marginTop: '0' }}
          dangerouslySetInnerHTML={{
            __html: `<div>${this.props.message.body}</div>`,
          }}
        />
      );
    } else {
      return (
        <div className="message-body body" style={{ marginTop: '0' }}>
          {this.props.message.body_text}
        </div>
      );
    }
  }

  render() {
    let from_dashboard_user = this.props.message.user_id === this.props.ticket.requester_id;
    let from_razorpay = !from_dashboard_user;
    if (this.props.message.incoming) {
      from_razorpay = false;
    }

    let img = <i className="i i-user-circle message-user-circle" />;
    if (!from_razorpay) {
      img = this.props.user.logo_url ? (
        <img class="img-round user-image" src={this.props.user.logo_url} />
      ) : (
        <i className="i i-user-circle message-user-circle" />
      );
    } else {
      img = <img class="img-round user-image" src={RZP_IMG} />;
    }
    let name = !from_razorpay
      ? from_dashboard_user
        ? this.props.user.name
        : 'CC: ' + this.props.message.from_email
      : 'Razorpay Support';

    const attachments = this.props.message.attachments;

    return (
      <Fragment>
        <div className="message panel ticket-row-panel" key={i}>
          <div className="panel-body" style={{ paddingLeft: 0 }}>
            <div className="row min-ht-56">
              <div className="col-xs-2">{img}</div>
              <div className="col-xs-10 reply-message-container">
                <h5 style={{ marginBottom: 0, marginTop: 0 }}>
                  <div className="row">
                    <div className="col-xs-5 message-owner">
                      <b>{name}</b>
                    </div>
                    <div className="col-xs-7 text-right">
                      {moment(this.props.message.created_at).format('ddd, MMM D, h:mm A')} (
                      {moment(this.props.message.created_at).fromNow()})
                    </div>
                  </div>
                </h5>
                {/* <p class="message-to">To - {this.props.message.to_emails.join(', ')}</p> */}
                <div class="message-body" style={{ marginTop: '15px' }}>
                  {this.renderMessage(from_razorpay)}
                </div>
                {attachments && attachments.length !== 0 && (
                  <div className="message-body body">
                    {attachments.map((file, index) => (
                      <Attachment key={file.id} file={file} />
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </Fragment>
    );
  }
}

const RZP_IMG = `https://cdn.razorpay.com/static/assets/merchant-dash/rzp-logo.png`;
