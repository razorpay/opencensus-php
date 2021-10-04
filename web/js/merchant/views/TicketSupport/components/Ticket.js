import React from 'react';
import moment from 'moment';
import { connect } from 'react-redux';
import TicketStatus from './TicketStatus';
import Attachment from './Attachment';
import Banner from './Banner';

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
      detailsVisible: false,
    };
  }

  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Ticket Dashboard',
      eventAction: 'ticket details fetched | Status: Success',
      eventLabel: `Tickets`,
    });
  }

  showDetails = () => {
    this.setState({ detailsVisible: true });
  };

  render() {
    const { ticket, user } = this.props;
    const { ticketID } = this.props;
    const img = this.props.logo_url ? (
      <img height="56px" class="img-round user-image" src={this.props.logo_url} />
    ) : (
      <i className="i i-user-circle reply-user-circle" />
    );

    const subject = (ticket.subject || '').replace('[Merchant]', '');
    const subjectComponent = subject && <b>| Category: {subject}</b>;

    return (
      <div className="message mb-0">
        <div className="ticket-conv-body">
          <div className="row ticket-title-section">
            <div className="col-xs-2">{img}</div>
            <div className="col-xs-10" style={{ paddingLeft: 0 }}>
              <h5 style={{ marginBottom: 0 }}>
                <div className="row" style={{ paddingRight: '10px' }}>
                  <div className="col-xs-8 message-owner">
                    <b>Ticket ID #{ticket && ticket.ticket_id ? ticket.ticket_id : ticketID} </b>
                    {subjectComponent}
                  </div>
                  <div className="col-xs-4 text-right" style={{ height: '20px' }}>
                    <TicketStatus ticket={ticket} />
                  </div>
                </div>
              </h5>
              <p class="message-to">
                Raised {moment(ticket.created_at).fromNow()}
                {this.state.detailsVisible ? (
                  <span className="ticket-details-caption text-uppercase">
                    ({moment(ticket.created_at).format('ddd, MMM D, YYYY, hh:mm A')})
                  </span>
                ) : (
                  <a className="ticket-details-caption" onClick={this.showDetails}>
                    Show details
                  </a>
                )}
              </p>
              {this.state.detailsVisible && ticket.cc_emails.length !== 0 ? (
                <p class="message-to" style={{ marginTop: '3px' }}>
                  CC: {ticket.cc_emails.join(', ')}
                </p>
              ) : null}
            </div>
          </div>
          <div className="ticket-title-divider" />
          <div className="row">
            <div className="col-xs-2" />
            <div className="col-xs-10" style={{ paddingLeft: 0 }}>
              <div
                className="message-body body"
                dangerouslySetInnerHTML={{
                  __html: ticket.description,
                }}
              />

              {ticket.attachments && ticket.attachments.length !== 0 && (
                <div className="message-body body">
                  {ticket.attachments.map((file) => (
                    <Attachment key={file.id} file={file} />
                  ))}
                </div>
              )}
            </div>
          </div>
          {user.isNewGrievanceFlowEnabled && <Banner ticket={ticket} />}
        </div>
      </div>
    );
  }
}
