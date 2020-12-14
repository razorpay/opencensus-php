import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import { tickets, statuses, conversations } from './data.js';
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
  componentDidMount() {
    window.rzpAnalytics({
      eventCategory: 'Ticket Dashboard',
      eventAction: 'ticket details fetched | Status: Success',
      eventLabel: `Tickets`,
    });
  }

  render() {
    const { ticket } = this.props;
    const { ticketID } = this.props;
    let img = this.props.logo_url ? (
      <img style={{ marginLeft: '2px' }} class="img-round user-image" src={this.props.logo_url} />
    ) : (
      <i style={{ left: '5px' }} className="i i-user-circle reply-user-circle" />
    );
    return (
      <Fragment>
        <div>
          <div className="row message" style={{ padding: '0 18px' }}>
            <div className="col-xs-12 ticket-conv-body">
              <div className="row m-body-stroke">
                <div className="col-xs-2">{img}</div>
                <div className="col-xs-10" style={{ paddingLeft: 0 }}>
                  <h5 style={{ marginBottom: 0 }}>
                    <div className="row" style={{ paddingRight: '10px' }}>
                      <div className="col-xs-8 message-owner">
                        <b>
                          TICKET ID #{ticketID}
                          {/* {ticket.custom_fields.cf_category
                            ? ` | Category: ${ticket.custom_fields.cf_category}`
                            : null} */}
                        </b>
                      </div>
                      <div className="col-xs-4 text-right" style={{ height: '20px' }}>
                        <TicketStatus ticket={ticket} />
                      </div>
                    </div>
                  </h5>
                  <p class="message-to">
                    Created {moment(ticket.created_at).fromNow()} (
                    {moment(ticket.created_at).format('LLL')} )
                  </p>
                  {ticket.cc_emails.length !== 0 ? (
                    <p class="message-to" style={{ marginTop: '3px' }}>
                      CC - {ticket.cc_emails.join(', ')}
                    </p>
                  ) : null}
                </div>
              </div>
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
            </div>
          </div>
        </div>
      </Fragment>
    );
  }
}
