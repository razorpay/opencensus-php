import { connect } from 'react-redux';
import { Component } from 'react';
import { trackSupportButton } from './ga';
import { withRouter } from 'react-router-dom';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, classList } from 'common/utils/rzp-utils';

import {
  checkCallEligibility,
  checkScheduleCallConfig,
  fetchCallSlots,
} from 'merchant/reducers/config';
import SupportHeader from 'merchant/components/Support/components/SupportHeader';
import SupportBody from 'merchant/components/Support/components/SupportBody';
import { merchantFetch } from 'merchant/utils/ajax';

import { COMDEL_URL } from './constants';

@withRouter
@connect(
  (state) => {
    return {
      isCallEnabled: state.config.isCallEnabled,
      scheduleCallConfig: state.config.scheduleCallConfig,
      user: state.session.user,
      org: state.session.org,
    };
  },
  {
    checkCallEligibility,
    checkScheduleCallConfig,
    fetchCallSlots,
  },
)
export default class Support extends Component {
  state = {
    isOpened: false,
    isHidden: false,
    notifyCount: 0,
    supportFlags: {
      show_chat: true,
      message_body: null,
      cta_list: [],
      show_create_ticket_popup: false,
      no_of_days_for_activation: '3 to 5',
      loaded: false,
    },
  };

  componentDidMount() {
    this.props.checkCallEligibility();
    if (this.props.user.isScheduleCallbackEnabled) {
      this.props
        .checkScheduleCallConfig(this.props.user.isCallbackCategoryExpEnabled)
        .then((response) => {
          if (response.is_eligible) {
            analyticsTrack({
              objectName: 'request a call',
              actionName: 'viewed',
              screen: 'home page',
              properties: {
                message: response.reason,
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
          }
        });
    }
    this.bindEvents();
    merchantFetch({
      url: 'merchants/support/option/flags',
    }).then((res) => {
      this.setState({
        supportFlags: {
          no_of_days_for_activation: '3 to 5',
          cta_list: ['continue_with_ticket', 'faqs'],
          message_body: `Your account is currently not activated. Our team is working hard to fast track your activation and it can take ${`3 to 5`} business days. If you have any other concerns, please feel free to raise a ticket.`,
          ...res.data,
          loaded: true,
        },
      });
    });
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
    const { user } = this.props;

    if (user.isComdelApiEnabled) return window.open(COMDEL_URL, '_blank');

    const { isOpened } = this.state;

    if (!isOpened) {
      trackSupportButton();
    }

    this.setState({
      isOpened: !isOpened,
    });

    return null;
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
    const { user, org, history } = this.props;
    const { notifyCount, isOpened, isHidden } = this.state;
    // Temporarily disabled till further notice for improving support quality index for calls,
    const isCallEnabled = false;
    // const isCallEnabled = !user.isActivated || this.props.isCallEnabled;

    const isOnBoardingRevampScreen =
      history.location.pathname.includes('onboarding') ||
      history.location.pathname.includes('tncform');
    const DASHBOARD_HOST_REGEX = /(dashboard.*\.razorpay\.(com|in)|localhost)$/;

    // Don't show support for Axis org
    if (
      !user.isComdelApiEnabled &&
      (!DASHBOARD_HOST_REGEX.test(location.hostname) || org.custom_code === 'axis')
    ) {
      return null;
    }

    if (isOnBoardingRevampScreen) {
      return null;
    }
    return (
      <div class={classList('support', isHidden && 'hidden')}>
        <SupportHeader
          onToggle={this.handleToggle}
          isOpened={isOpened}
          notifyCount={notifyCount}
          isOnBoardingRevampScreen={isOnBoardingRevampScreen}
          showComdelPopover={user.isComdelApiEnabled}
        />
        <SupportBody
          onToggle={this.handleToggle}
          isOpened={isOpened}
          onChat={this.handleChat}
          notifyCount={notifyCount}
          isCallEnabled={isCallEnabled}
          scheduleCallConfig={this.props.scheduleCallConfig}
          supportFlags={this.state.supportFlags}
          user={this.props.user}
        />
      </div>
    );
  }
}
