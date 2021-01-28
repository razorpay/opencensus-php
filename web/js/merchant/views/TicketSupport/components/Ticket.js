import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import Spinner from 'common/ui/Spinner';
import { tickets, statuses, conversations } from './data.js';
import { getEscalationType, getResponseArrivalType } from '../utils';
import { titleCase } from 'common/utils/rzp-utils.js';
import TicketStatus from './TicketStatus.js';
import Attachment from './Attachment.js';

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

  openGrievanceFlow = () => {
    const { ticket } = this.props;

    rzpTicketSystem.openModal('#grievance-new', {
      ticketID: ticket.id,
    });
  };

  renderEscalationBanner() {
    const { ticket } = this.props;
    const ESCALATION_TYPE = getEscalationType(ticket);
    const RESPONSE_ARRIVAL_TYPE = getResponseArrivalType(ticket);
    const expectedResponseBy = moment(ticket.fr_due_by).format('HH:mm, DD MMM');

    if (
      ESCALATION_TYPE !== 'escalated' &&
      ESCALATION_TYPE !== 'able-to-escalate' &&
      RESPONSE_ARRIVAL_TYPE !== 'within-expected-time'
    ) {
      return;
    }

    if (ESCALATION_TYPE === 'able-to-escalate') {
      return (
        <div className="row escalate-banner">
          <div className="col-xs-2"></div>
          <div className="col-xs-10" style={{ paddingLeft: 0 }}>
            <div className="message-escalation">
              <i class="i i-forward ticket-escalated-icon" />
              <span>Have any issues with this query? </span>
              <a className="link" onClick={this.openGrievanceFlow}>
                Raise Concern
              </a>
            </div>
          </div>
        </div>
      );
    } else if (
      ESCALATION_TYPE === 'escalated' ||
      RESPONSE_ARRIVAL_TYPE === 'within-expected-time'
    ) {
      return (
        <div className="row escalate-banner escalation-warning">
          <div className="col-xs-2"></div>
          <div className="col-xs-10" style={{ paddingLeft: 0 }}>
            <i class="i i-forward ticket-escalated-icon" />
            <span className="message-escalation">
              Response expected before: {expectedResponseBy}
            </span>
          </div>
        </div>
      );
    }
  }

  render() {
    const { ticket, user } = this.props;
    const { ticketID } = this.props;
    let img = this.props.logo_url ? (
      <img class="img-round user-image" src={this.props.logo_url} />
    ) : (
      <i className="i i-user-circle reply-user-circle" />
    );

    const subject = (ticket.subject || '').replace('[Merchant]', '');
    const subjectComponent = subject && <b>| Category: {subject}</b>;

    return (
      <Fragment>
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
            <div className="ticket-title-divider"></div>
            <div className="row">
              <div className="col-xs-2"></div>
              <div className="col-xs-10" style={{ paddingLeft: 0 }}>
                <div
                  className="message-body body"
                  dangerouslySetInnerHTML={{
                    __html: ticket.description,
                  }}
                />

                {ticket.attachments && ticket.attachments.length !== 0 && (
                  <div className="message-body body">
                    {ticket.attachments.map((file, index) => (
                      <Attachment key={file.id} file={file} />
                    ))}
                  </div>
                )}
              </div>
            </div>
            {user.isNewGrievanceFlowEnabled && this.renderEscalationBanner()}
          </div>
        </div>
      </Fragment>
    );
  }
}
