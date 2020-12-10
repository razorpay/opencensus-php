import { connect } from 'react-redux';
import { Component } from 'react';
import { trackSupportButton } from './ga';

import { checkCallEligibility } from 'merchant/reducers/config';
import SupportHeader from 'merchant/components/Support/components/SupportHeader';
import SupportBody from 'merchant/components/Support/components/SupportBody';

import { classList } from 'common/utils/rzp-utils';

@connect(
  (state) => {
    return {
      isCallEnabled: state.config.isCallEnabled,
    };
  },
  {
    checkCallEligibility,
  },
)
export default class Support extends Component {
  state = {
    isOpened: false,
    isHidden: false,
    notifyCount: 0,
  };

  componentDidMount() {
    this.props.checkCallEligibility();
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
    const isCallEnabled = !user.isActivated || this.props.isCallEnabled;

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
          isCallEnabled={isCallEnabled}
        />
      </div>
    );
  }
}
