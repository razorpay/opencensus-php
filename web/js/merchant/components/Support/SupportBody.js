import { Component } from 'react';
import Banner from 'rzp/ui/Banner';

export default class SupportBody extends Component {
  isWorkingDay = () => {
    const today = new Date();
    const day = today.getDay();
    const hours = today.getHours();

    if (day >= 1 && day <= 5 && (hours >= 9 && hours < 18)) {
      return true;
    }
    return false;
  };

  handleClick = id => {
    const { onToggle, onChat } = this.props;
    const rzpTicketSystem = window.rzpTicketSystem;

    if (rzpTicketSystem) {
      if (id === 'chat' || id === 'call') {
        if (!this.isWorkingDay()) {
          return;
        }
      }

      if (id === 'chat') {
        onToggle();
        onChat();
        return;
      }

      rzpTicketSystem.openModal(`#${id}`);
    } else {
      console.log('RZP TICKET SYSTEM INIT FAILED');
    }
  };

  render() {
    const { notifyCount, isOpened } = this.props;
    const { handleClick, isWorkingDay } = this;
    let shouldDisable = !isWorkingDay();

    return (
      <div class={`support-body ${isOpened ? ' open' : ''}`}>
        {shouldDisable && (
          <Banner>
            <span>
              Our chat and call support is currently offline, kindly{' '}
              <span
                class="btn-link"
                onClick={() => {
                  handleClick('ticket');
                }}
              >
                Raise a Request
              </span>{' '}
              to get in touch with us
            </span>
          </Banner>
        )}
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
            <li
              class={`support-item p-all chat ${
                shouldDisable ? 'disabled' : ''
              }`}
              onClick={() => handleClick('chat')}
            >
              Chat with us{' '}
              <small class="help-content">(9am-6pm, working days)</small>
              {notifyCount > 0 && (
                <span class="support-notify m-l">{notifyCount}</span>
              )}
              <small class="help-block">
                For quick questions or help on dashboard
              </small>
            </li>
            <li
              class={`support-item p-all call ${
                shouldDisable ? 'disabled' : ''
              }`}
              onClick={() => handleClick('call')}
            >
              Call Support{' '}
              <small class="help-content">(9am-6pm, working days)</small>
              <small class="help-block">
                For queries and help on the dashboard
              </small>
            </li>
          </ul>

          <div class="support-feedback">
            <button class="btn-default">
              <i class="i i-voice-record m-r" />
              Share Feedback
            </button>
            <button
              class="btn-default"
              onClick={() => {
                window.open(
                  'https://razorpay.com/knowledgebase/#merchant',
                  '_blank'
                );
              }}
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
