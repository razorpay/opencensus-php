import { Component } from 'react';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { trackSupportOptions } from 'merchant/components/Support/ga';
import { Link } from 'react-router-dom';
import { fetchTicketsRaisedByAgents } from 'merchant/reducers/config';

import ShowWhen from 'merchant/components/ShowWhen';
import { analyticsTrack } from 'common/utils/analytics';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import WriteToUsPopup from './WriteToUsPopup';
import { CreateTicketEmitter } from '../../../views/TicketSupport/utils';

const isWorkingDay = () => {
  return window.RZP && window.RZP.holidays && window.RZP.holidays.isExtendedWorkingDay;
};

@connect(
  (state) => {
    return {
      call_slots: state.config.call_slots,
      scheduleCallConfig: state.config.scheduleCallConfig,
    };
  },
  {
    openModal,
    fetchTicketsRaisedByAgents,
    closeModal,
  },
)
class SupportBody extends Component {
  state = {
    timings: [],
  };
  openDashboardGuide = (_) => {
    analyticsTrack({
      objectName: 'dashboard guide',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'Help and Support',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    trackSupportOptions('dashboard_guide');
    window.open('https://razorpay.com/docs/payment-gateway/dashboard-guide/', '_blank');
  };

  createTicket = (id, pcb, lcb) => {
    analyticsTrack({
      objectName: 'create ticket',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const user = this.props.user;
    const rzpTicketSystem = window.rzpTicketSystem;
    if (rzpTicketSystem) {
      if (pcb) {
        pcb();
      }
      if (this.props.supportFlags.show_create_ticket_popup) {
        this.props.openModal({
          size: 'small',
          component: (
            <WriteToUsPopup
              businessName={user.name}
              id={id}
              supportFlags={this.props.supportFlags}
              rzpTicketSystem={rzpTicketSystem}
              closeModal={this.props.closeModal}
            />
          ),
        });
      } else rzpTicketSystem.openModal(`#${id}`);
      if (lcb) {
        lcb();
      }
    }
  };

  componentDidMount() {
    if (this.props.user.isMobileSignupCareActive) {
      this.props.fetchTicketsRaisedByAgents();
    }

    CreateTicketEmitter.on('create-ticket', (id, pcb, lcb) => {
      this.createTicket(id, pcb, lcb);
    });
    const params = {
      url: 'merchants/chat/timings_config',
      headers: {
        'Content-Type': 'application/json',
      },
    };
    return merchantFetch(params).then((r) => {
      if (r.success) {
        this.setState({ timings: r.data });
      }
    });
  }

  handleClick = (id) => {
    const { onToggle, onChat, notifyCount } = this.props;
    const rzpTicketSystem = window.rzpTicketSystem;
    if (rzpTicketSystem) {
      trackSupportOptions(id);
      if (id === 'call') {
        if (!isWorkingDay()) {
          return;
        }
      }

      if (id === 'schedule-call') {
        // eslint-disable-next-line consistent-return
        return rzpTicketSystem.openModal(`#schedule-call`);
      }

      if (id === 'chat') {
        analyticsTrack({
          objectName: 'chat with us',
          actionName: 'clicked',
          screen: 'home page',
          properties: {
            location: 'Help and Support',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        // if notifications pending, then enable chat
        if (!this.props.supportFlags.show_chat && notifyCount < 1) {
          return;
        }

        onToggle();
        onChat();
        return;
      }
      onToggle();
      this.createTicket(id);
    } else {
      console.log('RZP TICKET SYSTEM INIT FAILED');
    }
  };

  handleFeedback = () => {
    const { onToggle } = this.props;
    analyticsTrack({
      objectName: 'share feedback',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'Help and Support',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    trackSupportOptions('feedback');

    try {
      document.querySelector('[class$="feedback_minimized_label"]').click();
    } catch (err) {
      console.log(err);
    }

    onToggle();
  };

  handleFaqs = () => {
    analyticsTrack({
      objectName: 'faqs',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'Help and Support',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    window.open('https://razorpay.com/knowledgebase/#merchant', '_blank');
    trackSupportOptions('faqs');
  };

  render() {
    const { notifyCount, isOpened, onToggle, isCallEnabled, scheduleCallConfig } = this.props;
    const { handleClick, openDashboardGuide } = this;
    const shouldDisable = !isWorkingDay();
    let scheduleCallbackReason =
      scheduleCallConfig && scheduleCallConfig.is_eligible === false && scheduleCallConfig.reason
        ? scheduleCallConfig.reason
        : 'For elaborate queries needing quick resolution';

    if (scheduleCallConfig.reason === 'NOT_AVAILABLE') {
      scheduleCallbackReason = (
        <span class="text-danger">Slots are unavailable right now, try later.</span>
      );
    }

    if (scheduleCallConfig.reason === 'ALREADY_BOOKED') {
      scheduleCallbackReason = <span class="text-danger">Call already requested.</span>;
    }

    const today = new Date().getDay();
    let date;
    if (this.state.timings.length) {
      date = {
        start: this.state.timings[today].start / 60,
        end: this.state.timings[today].end / 60,
        start_zone: 'AM',
        end_zone: 'AM',
      };

      if (date.start > 12) {
        date.start = date.start - 12;
        date.start_zone = 'PM';
      }

      if (date.end > 12) {
        date.end = date.end - 12;
        date.end_zone = 'PM';
      }
    }

    return (
      <div class={classList('support-body', isOpened && 'active')}>
        <header>
          <i class="i i-headset m-r" /> Help and Support{' '}
          <i class="i i-close pull-right mob-close" onClick={onToggle} />
        </header>
        <ul class="support-list">
          <ShowWhen additionalCondition={(user) => !user.isNewGrievanceFlowEnabled}>
            <li
              class={`support-item p-all ticket ${
                !this.props.supportFlags.loaded ? 'disabled' : ''
              }`}
              onClick={() => {
                analyticsTrack({
                  objectName: 'have a query',
                  actionName: 'clicked',
                  screen: 'home page',
                  properties: {
                    location: 'Help and Support',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                handleClick('tickets');
              }}
            >
              Have a query?
              <small class="help-block">For integration, account and payment issues</small>
            </li>
          </ShowWhen>
          <ShowWhen additionalCondition={(user) => user.isNewGrievanceFlowEnabled}>
            <li
              class={`support-item p-all ticket ${
                !this.props.supportFlags.loaded ? 'disabled' : ''
              }`}
              onClick={() => {
                analyticsTrack({
                  objectName: 'have a query',
                  actionName: 'clicked',
                  screen: 'home page',
                  properties: {
                    location: 'Help and Support',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                handleClick('tickets');
              }}
            >
              Have a query? <small class="help-block">Check existing query/raise a new one</small>
            </li>
          </ShowWhen>
          <ShowWhen
            myRole="owner admin"
            additionalCondition={(user) =>
              user.isScheduleCallbackEnabled &&
              !(
                scheduleCallbackReason === 'NOT_FETCHED_YET' ||
                (scheduleCallbackReason === 'NOT_APPLICABLE' && !scheduleCallConfig.is_eligible)
              )
            }
          >
            <li
              class={`support-item p-all callback ${
                !scheduleCallConfig.is_eligible ? 'disabled' : ''
              }`}
              onClick={() => {
                analyticsTrack({
                  objectName: 'request a call',
                  actionName: 'clicked',
                  screen: 'home page',
                  properties: {
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                });
                handleClick('schedule-call');
              }}
            >
              <span>
                Request a call <span class="badge">Recommended</span>
              </span>
              <small class="help-block">{scheduleCallbackReason}</small>
            </li>
          </ShowWhen>
          <ShowWhen
            myRole="owner admin"
            additionalCondition={(user) => !user.isNewGrievanceFlowEnabled}
          >
            <li class="support-item p-all history">
              <Link
                to="/ticket-support/tickets"
                onClick={() => {
                  window.rzpAnalytics({
                    eventCategory: 'Ticket Dashboard',
                    eventAction: 'write to us clicked',
                    eventLabel: `Tickets`,
                  });
                }}
              >
                Track Tickets <small class="help-block">View all tickets raised by you</small>
              </Link>
            </li>
          </ShowWhen>
          {window.rzp_user ? (
            ['activated', 'under_review', 'instantly_activated', 'needs_clarification'].indexOf(
              window.rzp_user.activation_status,
            ) > -1 && this.props.supportFlags.show_chat ? (
              <li
                class={`support-item p-all chat ${
                  (!this.props.supportFlags.show_chat && notifyCount < 1) ||
                  (this.props.user.isChatbotLive && !this.props.botIsLoaded)
                    ? 'disabled'
                    : ''
                }`}
                onClick={() => {
                  analyticsTrack({
                    objectName: 'chat with us',
                    actionName: 'clicked',
                    screen: 'home page',
                    properties: {
                      location: 'Help and Support',
                      ...getCommonAnalyticsProperties(window.rzp_user),
                    },
                  });
                  handleClick('chat');
                }}
              >
                Chat with us
                {this.state.timings.length ? (
                  <small class="help-content">
                    ({date.start} {date.start_zone} - {date.end} {date.end_zone})
                  </small>
                ) : null}
                {notifyCount > 0 && <span class="notify-icon m-l">{notifyCount}</span>}
                <small class="help-block">
                  {!this.props.supportFlags.show_chat && notifyCount < 1
                    ? 'Currently unavailable'
                    : 'For quick questions or help on dashboard'}
                </small>
              </li>
            ) : null
          ) : null}
          {isCallEnabled ? (
            <li
              class={`support-item p-all call ${shouldDisable ? 'disabled' : ''}`}
              onClick={() => handleClick('call')}
            >
              Call Support <small class="help-content">(9am-9pm, working days)</small>
              <small class="help-block">
                {shouldDisable ? 'Currently unavailable' : 'For queries and help on the dashboard'}
              </small>
            </li>
          ) : null}
          <li
            className="support-item p-all dashboard_guide"
            onClick={() => {
              openDashboardGuide();
            }}
          >
            Dashboard Guide{' '}
            <small className="help-block">Read more about how to use the dashboard</small>
          </li>
        </ul>

        <div class="support-feedback">
          <button class="btn btn-default pull-left" onClick={this.handleFeedback}>
            <i class="i i-voice-record m-r" /> Share Feedback
          </button>
          <button class="btn btn-default pull-right" onClick={this.handleFaqs}>
            <i class="i i-help  m-r" /> FAQs
          </button>
        </div>
      </div>
    );
  }
}

export default SupportBody;
