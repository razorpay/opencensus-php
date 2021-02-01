import { Component } from 'react';
import Banner from 'common/ui/Banner';

import { classList } from 'common/utils/rzp-utils';
import { trackSupportOptions } from 'merchant/components/Support/ga';
import { Link } from 'react-router-dom';

import ShowWhen from 'merchant/components/ShowWhen';
import analyticsService from '@commander/services/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import WriteToUsPopup from './WriteToUsPopup';

@connect(null, {
  openModal,
  closeModal,
})
class SupportBody extends Component {
  openDashboardGuide = (_) => {
    analyticsService.track({
      objectName: 'help and support',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        item: 'dashboard guide',
        location: 'Help and Support',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    trackSupportOptions('dashboard_guide');
    window.open('https://razorpay.com/docs/payment-gateway/dashboard-guide/', '_blank');
  };

  handleClick = (id) => {
    const user = this.props.user;
    const { onToggle, onChat, notifyCount } = this.props;
    const rzpTicketSystem = window.rzpTicketSystem;
    if (rzpTicketSystem) {
      trackSupportOptions(id);

      // if not working day for call/chat support, do nothing
      if (id === 'call') {
        if (!isWorkingDay()) {
          return;
        }
      }

      if (id === 'chat') {
        analyticsService.track({
          objectName: 'help and support',
          actionName: 'clicked',
          screen: 'home page',
          properties: {
            item: 'chat with us',
            location: 'Help and Support',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        // if notifications pending, then enable chat
        if (!isWorkingDay() && notifyCount < 1) {
          return;
        }

        onToggle();
        onChat();
        return;
      }
      analyticsService.track({
        objectName: 'help and support',
        actionName: 'clicked',
        screen: 'home page',
        properties: {
          item: 'write to us',
          location: 'Help and Support',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
      onToggle();
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
    } else {
      console.log('RZP TICKET SYSTEM INIT FAILED');
    }
  };

  handleFeedback = () => {
    const { onToggle } = this.props;
    analyticsService.track({
      objectName: 'help and support',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        item: 'share feedback',
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
    analyticsService.track({
      objectName: 'help and support',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        item: "faq's",
        location: 'Help and Support',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    window.open('https://razorpay.com/knowledgebase/#merchant', '_blank');
    trackSupportOptions('faqs');
  };

  render() {
    const { notifyCount, isOpened, onToggle, isCallEnabled } = this.props;
    const { handleClick, openDashboardGuide } = this;
    let shouldDisable = !isWorkingDay();

    return (
      <div class={classList('support-body', isOpened && 'active')}>
        <header>
          <i class="i i-headset m-r" />
          Help and Support
          <i class="i i-close pull-right mob-close" onClick={onToggle} />
        </header>
        <ul class="support-list">
          <ShowWhen
            myRole="owner admin"
            additionalCondition={(user) => !user.isNewGrievanceFlowEnabled}
          >
            <li class="support-item p-all ticket" onClick={() => handleClick('ticket')}>
              Have a query?
              <small class="help-block">For integration, account and payment issues</small>
            </li>
          </ShowWhen>
          <ShowWhen
            myRole="owner admin"
            additionalCondition={(user) => user.isNewGrievanceFlowEnabled}
          >
            <li class="support-item p-all ticket" onClick={() => handleClick('tickets')}>
              Have a query?
              <small class="help-block">Check existing query/raise a new one</small>
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
                Track Tickets
                <small class="help-block">View all tickets raised by you</small>
              </Link>
            </li>
          </ShowWhen>
          {window.rzp_user ? (
            ['activated', 'under_review', 'instantly_activated', 'needs_clarification'].indexOf(
              window.rzp_user.activation_status,
            ) > -1 && this.props.supportFlags.show_chat ? (
              <li
                class={`support-item p-all chat ${
                  shouldDisable && notifyCount < 1 ? 'disabled' : ''
                }`}
                onClick={() => handleClick('chat')}
              >
                Chat with us <small class="help-content">(9am-6pm, working days)</small>
                {notifyCount > 0 && <span class="notify-icon m-l">{notifyCount}</span>}
                <small class="help-block">
                  {shouldDisable && notifyCount < 1
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
              Call Support <small class="help-content">(9am-6pm, working days)</small>
              <small class="help-block">
                {shouldDisable ? 'Currently unavailable' : 'For queries and help on the dashboard'}
              </small>
            </li>
          ) : null}
          <li className="support-item p-all dashboard_guide" onClick={openDashboardGuide}>
            Dashboard Guide{' '}
            <small className="help-block">Read more about how to use the dashboard</small>
          </li>
        </ul>

        <div class="support-feedback">
          <button class="btn btn-default pull-left" onClick={this.handleFeedback}>
            <i class="i i-voice-record m-r" />
            Share Feedback
          </button>
          <button class="btn btn-default pull-right" onClick={this.handleFaqs}>
            <i class="i i-help  m-r" />
            FAQs
          </button>
        </div>
      </div>
    );
  }
}

const isWorkingDay = () => {
  return window.RZP && window.RZP.holidays && window.RZP.holidays.isWorkingDay;
};

export default SupportBody;
