import { connect } from 'react-redux';
import { Component } from 'react';
import { trackSupportButton } from './ga';
import { withRouter } from 'react-router-dom';

import { checkCallEligibility } from 'merchant/reducers/config';
import SupportHeader from 'merchant/components/Support/components/SupportHeader';
import SupportBody from 'merchant/components/Support/components/SupportBody';
import { merchantFetch } from 'merchant/utils/ajax';

import { classList } from 'common/utils/rzp-utils';

@withRouter
@connect(
  (state) => {
    return {
      isCallEnabled: state.config.isCallEnabled,
      user: state.session.user,
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
    supportFlags: {
      show_chat: true,
      show_create_ticket_popup: false,
      no_of_days_for_activation: '3 to 5',
    },
  };

  componentDidMount() {
    this.props.checkCallEligibility();
    this.bindEvents();
    if (this.props.user.isNewSupportChangesEnabled) {
      merchantFetch({
        url: 'merchants/support/option/flags',
      }).then((res) => {
        this.setState({
          supportFlags: {
            no_of_days_for_activation: '3 to 5',
            ...res.data,
          },
        });
      });
    }
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
    const { user, history } = this.props;
    const { notifyCount, isOpened, isHidden } = this.state;
    // Temporarily disabled till further notice for improving support quality index for calls,
    const isCallEnabled = false;
    // const isCallEnabled = !user.isActivated || this.props.isCallEnabled;

    const isOnBoardingRevampScreen = history.location.pathname.includes('onboarding');
    const DASHBOARD_HOST_REGEX = /(dashboard.*\.razorpay\.(com|in)|localhost)$/;

    if (!DASHBOARD_HOST_REGEX.test(location.hostname)) {
      return null;
    }

    return (
      <div class={classList('support', isHidden && 'hidden')}>
        <SupportHeader
          onToggle={this.handleToggle}
          isOpened={isOpened}
          notifyCount={notifyCount}
          isOnBoardingRevampScreen={isOnBoardingRevampScreen}
        />
        <SupportBody
          onToggle={this.handleToggle}
          isOpened={isOpened}
          onChat={this.handleChat}
          notifyCount={notifyCount}
          isCallEnabled={isCallEnabled}
          supportFlags={this.state.supportFlags}
          user={this.props.user}
        />
      </div>
    );
  }
}
