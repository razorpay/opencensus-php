import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import { statuses, getActiveTicket } from './data.js';
import { titleCase } from 'common/utils/rzp-utils.js';
import Field from 'common/new-ui/Input/index.js';
import { merchantFetch } from 'merchant/utils/ajax.js';
import * as axios from 'axios';
import { fetchSupportTickets } from 'merchant/reducers/config.js';
import Spinner from 'common/ui/Spinner';
import TicketBrief from './TicketBrief.js';
import HeaderAction from 'common/ui/HeaderAction';

@connect(
  (state) => {
    return {
      ...state.session,
      ...state.config.config,
      support_tickets: state.config.support_tickets,
      user: state.session.user,
    };
  },
  {
    fetchSupportTickets: fetchSupportTickets,
  },
)
export default class Tickets extends React.Component {
  componentDidMount() {
    this.goNext(1, true);
  }

  state = {
    size: 30,
    current_page: 1,
    loading: false,
  };

  goNext = (page, bypass) => {
    if (
      !(this.props.support_tickets.data[page] && this.props.support_tickets.data[page].length) ||
      bypass
    ) {
      const params = { page: page, per_page: this.state.size };
      this.props.fetchSupportTickets(params).then(() => {
        window.rzpAnalytics({
          eventCategory: 'Ticket Dashboard',
          eventAction: 'support tickets fetched',
          eventLabel: `Tickets | Status:Success`,
        });
        this.setState({ loading: false });
      });
    }
  };

  render() {
    let tickets = [];
    let total_tickets = [];
    Object.keys(this.props.support_tickets.data).forEach((k) => {
      total_tickets.push(...this.props.support_tickets.data[k]);
    });
    tickets = this.props.support_tickets.data[this.state.current_page] || [];
    // tickets=tickets.filter(t=>t.custom_fields.cf_category!=='Duplicate ticket');
    const no_tickets = !this.props.support_tickets.loading && total_tickets.length == 0;
    return (
      <Fragment>
        <div class="content-wrapper content-sm ticket-support">
          <HeaderAction>
            <div class="btn-toolbar pull-right">
              <button
                onClick={() => {
                  window.rzpAnalytics({
                    eventCategory: 'Ticket Dashboard',
                    eventAction: 'write to us clicked',
                    eventLabel: `Tickets`,
                  });
                  raiseTicket();
                }}
                className="btn btn-primary pull-right"
              >
                Write to us
              </button>
            </div>
          </HeaderAction>
          <div className="row">
            <div className="col-xs-12">
              <div className="tickets-container">
                {this.props.support_tickets.loading ? (
                  <div className="ticket-cont-spinner">
                    <Spinner />
                  </div>
                ) : null}
                {no_tickets ? (
                  <h2 class="no-tickets-f">Please click on write to us for any queries</h2>
                ) : (
                  <div>
                    {tickets.map((ticket, index) => {
                      return (
                        <TicketBrief
                          last={index == tickets.length - 1}
                          ticket={ticket}
                          key={index}
                        />
                      );
                    })}
                  </div>
                )}
              </div>
              <div className="panel">
                <div className="panel-body">
                  <p>
                    {!(tickets.length < this.state.size) && this.state.current_page == 1 ? (
                      <b>Page {this.state.current_page}</b>
                    ) : null}
                  </p>
                  {total_tickets.length >= 30 ? (
                    <button
                      disabled={tickets.length < this.state.size}
                      className="btn btn-outline pull-right"
                      onClick={() => {
                        const current_page = this.state.current_page + 1;
                        this.setState({ current_page }, () => this.goNext(current_page));
                      }}
                    >
                      Next
                    </button>
                  ) : null}

                  {this.state.current_page > 1 ? (
                    <button
                      style={{ marginRight: '10px' }}
                      className="btn btn-outline pull-right"
                      onClick={() => {
                        const current_page = this.state.current_page - 1;
                        this.setState({ current_page }, () => this.goNext(current_page));
                      }}
                    >
                      Prev
                    </button>
                  ) : null}
                </div>
              </div>
            </div>
          </div>
        </div>
      </Fragment>
    );
  }
}

const raiseTicket = () => {
  if (window.rzpTicketSystem) {
    const rzpTicketSystem = window.rzpTicketSystem;
    rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);
    rzpTicketSystem.openModal('#ticket');
    setTimeout(() => {
      rzpTicketSystem.modal.next();
    }, 0);
  }
};
