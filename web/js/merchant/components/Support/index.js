import { Component } from 'react';

import SupportHeader from 'merchant/components/Support/components/SupportHeader';
import SupportBody from 'merchant/components/Support/components/SupportBody';

import { trackSupportButton } from './ga';
import { classList } from 'common/utils/rzp-utils';

export default class Support extends Component {
  state = {
    isOpened: false,
    isHidden: false,
    notifyCount: 0,
  };

  componentDidMount() {
    this.bindEvents();
  }

  bindEvents = () => {
    //bind events for freshchat if available
    if (window.fcWidget) {
      window.fcWidget.on('widget:opened', () => {
        this.handleVisibility(true);
      });
      window.fcWidget.on('widget:closed', () => {
        this.handleVisibility(false);
      });

      window.fcWidget.on('unreadCount:notify', (response) => {
        this.setState({ notifyCount: response.count });
      });
    }
  };

  handleToggle = () => {
    const { isOpened } = this.state;

    if (!isOpened) {
      trackSupportButton();
    }

    this.setState({
      isOpened: !isOpened,
    });
  };

  handleVisibility = (shouldHide) => {
    this.setState({ isHidden: shouldHide });
  };

  handleChat = () => {
    if (window.fcWidget) {
      window.fcWidget.open();
    }

    this.handleVisibility(true);
  };

  render() {
    const { user } = this.props;
    const { notifyCount, isOpened, isHidden } = this.state;

    const DASHBOARD_HOST_REGEX = /(dashboard.*\.razorpay\.(com|in)|localhost)$/;

    if (!DASHBOARD_HOST_REGEX.test(location.hostname)) {
      return null;
    }

    return (
      <div class={classList('support', isHidden && 'hidden')}>
        <SupportHeader onToggle={this.handleToggle} isOpened={isOpened} notifyCount={notifyCount} />
        <SupportBody
          onToggle={this.handleToggle}
          isOpened={isOpened}
          onChat={this.handleChat}
          notifyCount={notifyCount}
          isSupportCallEnabled={user.isSupportCallEnabled}
        />
      </div>
    );
  }
}
