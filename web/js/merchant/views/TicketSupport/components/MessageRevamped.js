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
  renderMessage(from_razorpay) {
    if (from_razorpay) {
      return (
        <div
          className="message-body revamped body mt-0"
          dangerouslySetInnerHTML={{
            __html: `<div>${this.props.message.body}</div>`,
          }}
        />
      );
    } else {
      return (
        <div className="message-body revamped body mt-0">
          {this.props.message.body_text}
        </div>
      );
    }
  }

  render() {
    const isTicketRevampFlowEnabled=this.props.user.isTicketRevampFlowEnabled;
    let from_dashboard_user = this.props.message.user_id === this.props.ticket.requester_id;
    let from_razorpay = !from_dashboard_user;
    if (this.props.message.incoming) {
      from_razorpay = false;
    }

    let img = <i className="i i-user-circle message-user-circle-revamped" />;
    if (!from_razorpay) {
      img = this.props.user.logo_url ? (
        <div class="revamped-user-image"><img class="img-round revamped-user-image" src={this.props.user.logo_url} /></div>
      ) : (
        <div class="revamped-user-image"><i className="i i-user-circle message-user-circle-revamped" /></div>
      );
    } else {
      img = <div class="revamped-user-image">
        <img class="img-round revamped-user-image" src={RAZORPAY_LOGO} />
      </div>;
    }
    let name = !from_razorpay
      ? from_dashboard_user
        ? this.props.user.name
        : 'CC: ' + this.props.message.from_email
      : 'Razorpay Support';

    const attachments = this.props.message.attachments;

    return (
      <React.Fragment>
        <div className={`message ${isTicketRevampFlowEnabled?'revamped':''} panel ticket-row-panel mt-0 border-bt-0 mb-0` }key={i}>
          <div className="panel-body p-v-24">
            <div className="row min-ht-56">
              <div className="col-xs-2 w-auto">{img}</div>
              <div className="col-xs-10 reply-message-container pr-0">
                <h5 className="title-container">
                  <div className="row flex pr-0">
                    <div className="col-xs-5 message-owner">
                      <b className="name">{name}</b>
                    </div>
                    <div
                      className="col-xs-7 text-right created-time"
                    >
                      {moment(this.props.message.created_at).fromNow('h')} ago
                    </div>
                  </div>
                </h5>
                {/* <p class="message-to">To - {this.props.message.to_emails.join(', ')}</p> */}
                <div class="message-body revamped">
                  {this.renderMessage(from_razorpay)}
                </div>
                {attachments && attachments.length !== 0 && (
                  <div className="message-body revamped body mt-20">
                    {attachments.map((file, index) => (
                      <Attachment key={file.id} file={file} />
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </React.Fragment>
    );
  }
}