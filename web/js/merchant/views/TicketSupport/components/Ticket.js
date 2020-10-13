import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import { tickets, statuses, conversations } from './data.js';
import { titleCase } from 'common/utils/rzp-utils.js';
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
    const status = statuses[this.props.ticket.status];
    return (
      <Fragment>
        <div>
          <div className="row message" style={{ padding: '0 18px' }}>
            <div className="col-xs-12 ticket-conv-body">
              <div className="row m-body-stroke">
                <div className="col-xs-2">
                  <img
                    class="img-round user-image ticket-img-round"
                    src={this.props.logo_url || USER_IMG}
                  />
                </div>
                <div className="col-xs-10" style={{ paddingLeft: 0 }}>
                  <h5 style={{ marginBottom: 0 }}>
                    <div className="row" style={{ paddingRight: '10px' }}>
                      <div className="col-xs-9 message-owner">
                        <b>
                          TICKET ID #{this.props.ticket.id} | Category:{' '}
                          {this.props.ticket.custom_fields.cf_category}
                        </b>
                      </div>
                      <div className="col-xs-3 text-right">
                        <span
                          className={`label ${(() => {
                            var label = 'label-warning';
                            if (status == 'closed') {
                              label = 'label-danger';
                            }
                            if (status == 'resolved') {
                              label = 'label-success';
                            }
                            if (status == 'resolved') {
                              label = 'label-success';
                            }
                            return label;
                          })()}`}
                        >
                          {titleCase(status)}
                        </span>{' '}
                      </div>
                    </div>
                  </h5>
                  <p class="message-to">
                    Created {moment(this.props.ticket.created_at).fromNow()}(
                    {moment(this.props.ticket.created_at).format('LLL')} )
                  </p>
                  {this.props.ticket.cc_emails.length ? (
                    <p class="message-to" style={{ marginTop: '3px' }}>
                      Cc - {this.props.ticket.cc_emails.join(', ')}
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
                      __html: this.props.ticket.description,
                    }}
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </Fragment>
    );
  }
}

const USER_IMG = `https://cdn.razorpay.com/static/assets/merchant-dash/user.png`;
