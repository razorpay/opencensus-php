import React from 'react';
import { Box, Button } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import { withSplitzService } from 'common/splitz';
import Spinner from 'common/ui/Spinner';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getPosActivationStatus, isHelpWidgetDisabled } from 'merchant/components/Support/utils';
import { fetchSupportTickets } from 'merchant/reducers/config';
import { isPaginationEnabled, raiseTicket } from 'merchant/views/TicketSupport/utils';

import FailedScreen from './FailedScreen';
import TicketBrief from './TicketBrief';
import { statuses, MAX_PAGE_SIZE } from './data';

class Tickets extends React.Component {
  componentDidMount() {
    this.goNext(1, true);
  }

  componentDidUpdate(prevProps) {
    if (prevProps.match.params.ticketType !== this.props.match.params.ticketType) {
      this.goNext(1, true);
      if (this.isTicketPaginationEnabled()) {
        this.setState((prevState) => ({ ...prevState, current_page: 1 }));
      }
    }
  }

  state = {
    size: MAX_PAGE_SIZE,
    current_page: 1,
  };

  isTicketPaginationEnabled = () => isPaginationEnabled(this.props.splitz);

  raiseTicket = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Ticket Dashboard',
      eventAction: 'write to us clicked',
      eventLabel: `Tickets`,
    });

    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      rzpTicketSystem.setPrefill('#request', ['merchant', 'other']);

      let options = {};
      options = {
        screens: 'dashboardRequest',
        email: window.rzp_user ? window.rzp_user.email : '',
      };

      rzpTicketSystem.openModal('#ticket', options);

      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
    }
  };

  goNext = (page, bypass) => {
    const { user } = this.props;
    if (
      !(this.props.support_tickets.data[page] && this.props.support_tickets.data[page].length) ||
      bypass
    ) {
      const params = { page, per_page: this.state.size };
      let filter = {
        cf_created_by: this.props.match.params.ticketType,
      };
      if (!this.props.match.params.ticketType) {
        filter = null;
      }
      if (this.props.match.params.ticketType === 'merchant') {
        filter = null;
      }
      const splitz = this.props.splitz;

      const isFetchTicketsApiMigrationActive = user.isFetchTicketsApiMigration;
      this.props
        .fetchSupportTickets(
          params,
          filter,
          isFetchTicketsApiMigrationActive,
          getPosActivationStatus(user, splitz) === 'activated',
          this.isTicketPaginationEnabled(),
        )
        .then(() => {
          window.rzpAnalytics?.({
            eventCategory: 'Ticket Dashboard',
            eventAction: 'support tickets fetched',
            eventLabel: `Tickets | Status:Success`,
          });

          analyticsTrack({
            objectName: 'show all tickets',
            actionName: 'rendered',
            screen: 'support tickets',
            properties: {
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        });
    }
  };

  showTickets = (totalTickets, currentPageTickets, user, createTicket) => {
    if (this.props.support_tickets.loading) {
      return null;
    }

    const NO_TICKETS_PRESENT =
      !this.props.support_tickets.loading &&
      (totalTickets.length === 0 ||
        (this.isTicketPaginationEnabled() && currentPageTickets.length === 0));

    if (NO_TICKETS_PRESENT) {
      return (
        <div className="no-tickets-present-container">
          <h2 className="no-tickets-f">There are no queries yet!</h2>
          <p className="text-center view-raised-queries">
            You can view your raised queries here and track its status.
          </p>
        </div>
      );
    }

    if (this.props.support_tickets.error) {
      return (
        <FailedScreen
          tryAgain={() => {
            this.goNext(1, true);
          }}
        />
      );
    }

    const CLOSED_TICKETS = [];
    const OPEN_TICKETS = [];

    currentPageTickets.forEach((ticket) => {
      if (
        statuses[ticket.status] &&
        (statuses[ticket.status].name === 'Resolved' || statuses[ticket.status].name === 'Closed')
      ) {
        if (ticket.custom_fields.cf_ticket_queue !== 'Internal') CLOSED_TICKETS.push(ticket);
      } else if (ticket.custom_fields.cf_ticket_queue !== 'Internal') OPEN_TICKETS.push(ticket);
    });
    return (
      <div>
        {OPEN_TICKETS.length !== 0 && (
          <h1 className="tickets-section-title">
            {this.props.match.params.ticketType === 'agent' ? (
              <span>Razorpay is requesting some details</span>
            ) : (
              <span>Open queries ({OPEN_TICKETS.length})</span>
            )}
            {!(
              isHelpWidgetDisabled(this.props.splitz) ||
              this.props.match.params.ticketType == 'agent'
            ) ? (
              <button
                onClick={createTicket}
                type="button"
                className="btn btn-outline pull-right raise-new-query-btn"
              >
                <i className="i i-plus" /> Raise New Query
              </button>
            ) : null}
          </h1>
        )}
        <div>
          {OPEN_TICKETS.map((ticket, index) => {
            const isPosMerchant = getPosActivationStatus(user, this.props.splitz) === 'activated';
            if (ticket?.type === 'Ezetap' && !isPosMerchant) {
              return null;
            }
            return (
              <TicketBrief
                ticketType={this.props.match.params.ticketType || 'merchant'}
                user={user}
                last={index == currentPageTickets.length - 1}
                ticket={ticket}
                key={index}
              />
            );
          })}
        </div>
        {OPEN_TICKETS.length !== 0 && CLOSED_TICKETS.length !== 0 && (
          <div className="tickets-section-separator" />
        )}
        {CLOSED_TICKETS.length !== 0 && (
          <h1 className="tickets-section-title">
            <span>Closed queries ({CLOSED_TICKETS.length})</span>
            <i className="i i-chevron-up section-collapse" />
            {!(isHelpWidgetDisabled(this.props.splitz) || OPEN_TICKETS.length !== 0) ? (
              <button
                onClick={createTicket}
                type="button"
                className="btn btn-outline pull-right raise-new-query-btn"
              >
                <i className="i i-plus" /> Raise New Query
              </button>
            ) : null}
          </h1>
        )}
        <div>
          {CLOSED_TICKETS.map((ticket, index) => {
            const isPosMerchant = getPosActivationStatus(user, this.props.splitz) === 'activated';
            if (ticket?.type === 'Ezetap' && !isPosMerchant) {
              return null;
            }
            return (
              <TicketBrief
                ticketType={this.props.match.params.ticketType || 'merchant'}
                user={user}
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

  handleNext = () => {
    this.setState(
      ({ current_page, ...rest }) => ({ current_page: current_page + 1, ...rest }),
      () => {
        this.goNext(this.state.current_page, true);
      },
    );
  };

  handlePrevious = () => {
    this.setState(
      ({ current_page, ...rest }) => ({ current_page: current_page - 1, ...rest }),
      () => {
        this.goNext(this.state.current_page, true);
      },
    );
  };

  render() {
    let tickets = [];
    const total_tickets = [];
    Object.keys(this.props.support_tickets.data).forEach((k) => {
      total_tickets.push(...this.props.support_tickets.data[k]);
    });
    tickets = this.props.support_tickets.data[this.state.current_page] || [];
    if (this.props.match.params.ticketType) {
      if (this.props.match.params.ticketType === 'merchant') {
        tickets = tickets.filter((ticket) => ticket.custom_fields.cf_created_by !== 'agent');
      }
    } else {
      tickets = tickets.filter((ticket) => ticket.custom_fields.cf_created_by !== 'agent');
    }

    const isPreviousDisabled = this.props.support_tickets.loading || this.state.current_page === 1;
    const isNextDisabled =
      this.props.support_tickets.loading || this.state.current_page === 10 || tickets.length === 0;

    const createTicket = raiseTicket;
    const isTicketPaginated = this.isTicketPaginationEnabled();

    return (
      <>
        <div className="content-wrapper content-sm ticket-support">
          <div className="row">
            <div className="col-xs-12">
              <div className="tickets-container">
                {this.props.support_tickets.loading ? (
                  <div className="ticket-cont-spinner">
                    <Spinner />
                  </div>
                ) : null}
                {this.showTickets(total_tickets, tickets, this.props.user, createTicket)}
              </div>
            </div>
          </div>
        </div>
        {isTicketPaginated && !this.props.support_tickets.loading ? (
          <Box display="flex" gap="spacing.3" justifyContent="flex-end">
            <Button
              variant="secondary"
              onClick={this.handlePrevious}
              isDisabled={isPreviousDisabled}
            >
              Previous
            </Button>
            <Button variant="secondary" onClick={this.handleNext} isDisabled={isNextDisabled}>
              Next
            </Button>
          </Box>
        ) : null}
      </>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        ...state.session,
        ...state.config.config,
        support_tickets: state.config.support_tickets,
        user: state.session.user,
      };
    },
    {
      fetchSupportTickets,
    },
  ),
  withRouter,
  withSplitzService,
)(Tickets);
