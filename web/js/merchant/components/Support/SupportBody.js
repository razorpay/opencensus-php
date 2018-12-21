import { Component } from 'react';
import Banner from 'rzp/ui/Banner';

import { getExperiment } from 'common/util';

export default class SupportBody extends Component {
  handleClick = id => {
    const { onToggle, onChat, notifyCount } = this.props;
    const rzpTicketSystem = window.rzpTicketSystem;

    if (rzpTicketSystem) {
      // if not working day for call/chat support, do nothing
      if (id === 'call') {
        if (!isWorkingDay()) {
          return;
        }
      }

      if (id === 'chat') {
        // if notifications pending, then enable chat
        if (!isWorkingDay() && notifyCount < 1) {
          return;
        }

        onToggle();
        onChat();
        return;
      }

      onToggle();
      rzpTicketSystem.openModal(`#${id}`);
    } else {
      console.log('RZP TICKET SYSTEM INIT FAILED');
    }
  };

  handleFeedback = () => {
    const { onToggle } = this.props;

    document.querySelector('[class$="feedback_minimized_label"]').click();
    onToggle();
  };

  handleFaqs = () => {
    window.open('https://razorpay.com/knowledgebase/#merchant', '_blank');
  };

  render() {
    const { notifyCount, isOpened } = this.props;
    const { handleClick } = this;
    let shouldDisable = !isWorkingDay();

    return (
      <div class={`support-body ${isOpened ? ' open' : ''}`}>
        <div class="p-all">
          <h4>Reach out to us</h4>
          <ul class="support-list">
            <li
              class="support-item p-all ticket"
              onClick={() => handleClick('ticket')}
            >
              Write to us
              <small class="help-block">
                For integration, account and payment issues
              </small>
            </li>
            {window.rzp_user ? (
              ['activated', 'under_review', 'instantly_activated'].indexOf(
                window.rzp_user.activation_status
              ) > -1 ? (
                <li
                  class={`support-item p-all chat ${
                    shouldDisable && notifyCount < 1 ? 'disabled' : ''
                  }`}
                  onClick={() => handleClick('chat')}
                >
                  Chat with us{' '}
                  <small class="help-content">(9am-6pm, working days)</small>
                  {notifyCount > 0 && (
                    <span class="support-notify m-l">{notifyCount}</span>
                  )}
                  <small class="help-block">
                    {shouldDisable && notifyCount < 1
                      ? 'Currently unavailable'
                      : 'For quick questions or help on dashboard'}
                  </small>
                </li>
              ) : null
            ) : null}
            {getExperiment('support_call') === 'on' ? (
              <li
                class={`support-item p-all call ${
                  shouldDisable ? 'disabled' : ''
                }`}
                onClick={() => handleClick('call')}
              >
                Call Support{' '}
                <small class="help-content">(9am-6pm, working days)</small>
                <small class="help-block">
                  {shouldDisable
                    ? 'Currenlty unavailable'
                    : 'For queries and help on the dashboard'}
                </small>
              </li>
            ) : null}
          </ul>

          <div class="support-feedback">
            <button
              class="btn btn-default pull-left"
              onClick={this.handleFeedback}
            >
              <i class="i i-voice-record m-r" />
              Share Feedback
            </button>
            <button
              class="btn btn-default pull-right"
              onClick={this.handleFaqs}
            >
              <i class="i i-help  m-r" />
              FAQs
            </button>
          </div>
        </div>
      </div>
    );
  }
}

const isWorkingDay = () => {
  const today = new Date();
  const day = today.getDay();
  const hours = today.getHours();

  if (day >= 1 && day <= 5 && (hours >= 9 && hours < 18)) {
    return true;
  }
  return false;
};
