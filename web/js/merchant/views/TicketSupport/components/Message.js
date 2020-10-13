import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import { tickets, statuses, conversations } from './data.js';
import { titleCase } from 'common/utils/rzp-utils.js';
import Ticket from './Ticket';
import Reply from './Reply';
import * as axios from 'axios';
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
  render() {
    let from_razorpay = this.props.message.user_id !== this.props.ticket.requester_id;

    let img = RZP_IMG;
    if (!from_razorpay) {
      img = this.props.user.logo_url || USER_IMG;
    }
    let name = !from_razorpay ? this.props.user.name : 'Razorpay Support';
    let body;
    let d = document.createElement('div');
    d.innerHTML = this.props.message.body;
    // d = d.querySelector(`[dir="ltr"]`);
    body = d.innerHTML;
    return (
      <Fragment>
        <div>
          {' '}
          <div className="message" key={i} style={{ marginBottom: 0 }}>
            <div
              className="panel ticket-row-panel"
              style={{ borderBottom: this.props.last ? `1px solid rgba(22,47,86,0.1)` : 'auto' }}
            >
              <div className="panel-body" style={{ paddingLeft: 0 }}>
                <div className="row">
                  <div className="col-xs-2">
                    <img class="img-round user-image" src={img} />
                  </div>
                  <div className="col-xs-10">
                    <h5 style={{ marginBottom: 0, marginTop: 0 }}>
                      <div className="row">
                        <div className="col-xs-5 message-owner">
                          <b>{name}</b>
                        </div>
                        <div className="col-xs-7 text-right">
                          {moment(this.props.message.created_at).format('lll')} (
                          {moment(this.props.message.created_at).fromNow()})
                        </div>
                      </div>
                    </h5>
                    {/* <p class="message-to">To - {this.props.message.to_emails.join(', ')}</p> */}
                    <div class="message-body" style={{ marginTop: '15px' }}>
                      <div
                        className="message-body body"
                        style={{ marginTop: '0' }}
                        dangerouslySetInnerHTML={{
                          __html: body,
                        }}
                      />
                    </div>
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

const USER_IMG = `https://cdn.razorpay.com/static/assets/merchant-dash/user.png`;
const RZP_IMG = `https://cdn.razorpay.com/static/assets/merchant-dash/rzp-logo.png`;
