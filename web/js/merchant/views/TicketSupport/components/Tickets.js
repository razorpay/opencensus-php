import { connect } from 'react-redux';
import { Route, Switch, NavLink, Link, Redirect } from 'react-router-dom';
import { Fragment } from 'react';
import { statuses, MAX_PAGE_SIZE } from './data.js';
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
    size: MAX_PAGE_SIZE,
    current_page: 1,
    loading: false,
  };

  raiseTicket = () => {
    window.rzpAnalytics({
      eventCategory: 'Ticket Dashboard',
      eventAction: 'write to us clicked',
      eventLabel: `Tickets`,
    });

    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);

      let options = {};
      if (this.props.user.isNewGrievanceFlowEnabled) {
        options = {
          screens: 'dashboardRequest',
          email: window.rzp_user ? window.rzp_user.email : '',
        };
      }

      rzpTicketSystem.openModal('#ticket', options);

      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
    }
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

  showTickets = (totalTickets, currentPageTickets) => {
    if (this.props.support_tickets.loading) {
      return null;
    }

    const NO_TICKETS_PRESENT = !this.props.support_tickets.loading && totalTickets.length == 0;

    if (NO_TICKETS_PRESENT) {
      return <h2 class="no-tickets-f">Please click on write to us for any queries</h2>;
    }

    // if (currentPageTickets) {
    //   currentPageTickets[0].status = 6;
    // }

    const CLOSED_TICKETS = [];
    const OPEN_TICKETS = [];

    currentPageTickets.forEach((ticket) => {
      if (
        statuses[ticket.status] &&
        (statuses[ticket.status].name === 'Resolved' || statuses[ticket.status].name === 'Closed')
      ) {
        CLOSED_TICKETS.push(ticket);
      } else {
        OPEN_TICKETS.push(ticket);
      }
    });

    return (
      <div>
        {OPEN_TICKETS.length !== 0 && (
          <h1 className="tickets-section-title">Open tickets ({OPEN_TICKETS.length})</h1>
        )}
        <div>
          {OPEN_TICKETS.map((ticket, index) => {
            return (
              <TicketBrief
                last={index == currentPageTickets.length - 1}
                ticket={ticket}
                key={index}
              />
            );
          })}
        </div>
        {OPEN_TICKETS.length !== 0 && CLOSED_TICKETS.length !== 0 && (
          <div className="tickets-section-separator"></div>
        )}
        {CLOSED_TICKETS.length !== 0 && (
          <h1 className="tickets-section-title">
            <span>Closed tickets ({CLOSED_TICKETS.length})</span>
            <i class="i i-chevron-up section-collapse" />
          </h1>
        )}
        <div>
          {CLOSED_TICKETS.map((ticket, index) => {
            return (
              <TicketBrief
                last={index == currentPageTickets.length - 1}
                ticket={ticket}
                key={index}
              />
            );
          })}
        </div>
      </div>
    );
  };

  render() {
    let tickets = [];
    let total_tickets = [];
    Object.keys(this.props.support_tickets.data).forEach((k) => {
      total_tickets.push(...this.props.support_tickets.data[k]);
    });
    tickets = this.props.support_tickets.data[this.state.current_page] || [];
    // tickets=tickets.filter(t=>t.custom_fields.cf_category!=='Duplicate ticket');
    return (
      <Fragment>
        <div class="content-wrapper content-sm ticket-support">
          <HeaderAction>
            <div class="btn-toolbar pull-right">
              <button onClick={this.raiseTicket} className="btn btn-primary pull-right">
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
                {this.showTickets(total_tickets, tickets)}
              </div>
            </div>
          </div>
        </div>
      </Fragment>
    );
  }
}
