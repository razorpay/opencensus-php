import { connect } from 'react-redux';
import { Component, lazy, Suspense } from 'react';
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
import { merchantFetch } from 'merchant/utils/ajax';
import { COMDEL_URL } from './constants';
import getMobileDetect from 'common/utils/mobileDetect';
import SupportLoader from 'merchant/components/Support/components/Loader';
import { getCommonSupportProperties } from 'merchant/components/Support/getCommonSupportProperties';

const SupportBody = lazy(() => import('merchant/components/Support/components/SupportBody'));
const SupportBodyOld = lazy(() => import('merchant/components/Support/components/SupportBodyOld'));
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
    botIsLoaded: false,
    supportFlags: {
      show_chat: true,
      message_body: null,
      cta_list: [],
      show_create_ticket_popup: false,
      no_of_days_for_activation: '3 to 5',
      loaded: false,
      isFetching: false,
    },
    isWebView: false,
  };

  componentDidMount() {
    this.props.checkCallEligibility();

    this.props.checkScheduleCallConfig().then((response) => {
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

    this.bindEvents();

    this.fetchSupportFlags();

    this.handleIsWebView();

    if (this.props.user.isChatbotLive) {
      analyticsTrack({
        objectName: 'chatbot',
        actionName: 'initialised',
        screen: 'home page',
        properties: {
          ...getCommonAnalyticsProperties(window.rzp_user),
          ...getCommonSupportProperties(),
        },
      });
    }

    //@NOTE: below lines will be uncommented with 100% live of new ui on webview
    // const shouldOpenSupportOnMount = this.props?.history?.location?.pathname?.includes(
    //   '/app-support',
    // );
    // if (shouldOpenSupportOnMount) {
    //   this.handleToggle();
    // }
  }

  handleIsWebView = () => {
    if (getMobileDetect().isWebView()) {
      this.setState({ isWebView: true });
    }
  };

  fetchSupportFlags = () => {
    return new Promise((resolve) => {
      if (this.props.user.current) {
        const { supportFlags: oldSupportFlags } = this.state;
        this.setState(
          {
            supportFlags: {
              ...oldSupportFlags,
              isFetching: true,
            },
          },
          async () => {
            const response = await merchantFetch({
              url: 'merchants/support/option/flags',
            });
            const newSupportFlags = {
              ...oldSupportFlags,
              ...response.data,
              loaded: true,
              isFetching: false,
            };

            this.setState(
              {
                supportFlags: newSupportFlags,
              },
              () => {
                resolve(newSupportFlags);
              },
            );
          },
        );
      } else {
        resolve();
      }
    });
  };

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
    const chatBotInt = setInterval(() => {
      if (window.chatBotCloseIcon) {
        this.setState({ botIsLoaded: true });
        window.chatBotCloseIcon.onclick = () => {
          window.chatbotToggle();
          this.handleVisibility(false);
        };
        clearInterval(chatBotInt);
      }
    }, 500);
  };

  disableScrolling = () => {
    document.body.classList.add('overflow-hidden');
  };

  enableScrolling = () => {
    document.body.classList.remove('overflow-hidden');
  };

  // eslint-disable-next-line consistent-return
  handleToggle = () => {
    const { user } = this.props;

    if (user.isComdelApiEnabled) return window.open(COMDEL_URL, '_blank');

    const { isOpened } = this.state;

    if (!isOpened) {
      trackSupportButton();
      this.disableScrolling();
    } else {
      this.enableScrolling();
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
    if (this.props.user.isChatbotLive) {
      if (window.chatbotToggle) {
        window.chatbotToggle();
      }
    } else if (window.fcWidget) {
      window.fcWidget.open();
      this.handleVisibility(true);
    }
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

    const shouldOpenRaiseAQueryOnMount = history?.location?.pathname?.includes('/app-support');

    const showNewSupport = this.state.isWebView ? false : user.showNewTicketCreationUI;
    return (
      <div className={classList('support', isHidden && 'hidden')}>
        <SupportHeader
          onToggle={this.handleToggle}
          isOpened={isOpened}
          notifyCount={notifyCount}
          isOnBoardingRevampScreen={isOnBoardingRevampScreen}
          showComdelPopover={user.isComdelApiEnabled}
          isWebView={this.state.isWebView}
        />
        <Suspense fallback={<SupportLoader isOpened={isOpened} />}>
          {showNewSupport ? (
            <SupportBody
              onToggle={this.handleToggle}
              isOpened={isOpened}
              botIsLoaded={this.state.botIsLoaded}
              onChat={this.handleChat}
              notifyCount={notifyCount}
              isCallEnabled={isCallEnabled}
              scheduleCallConfig={this.props.scheduleCallConfig}
              supportFlags={this.state.supportFlags}
              user={this.props.user}
              isWebView={this.state.isWebView}
              fetchSupportFlags={this.fetchSupportFlags}
            />
          ) : (
            <SupportBodyOld
              onToggle={this.handleToggle}
              isOpened={isOpened}
              botIsLoaded={this.state.botIsLoaded}
              onChat={this.handleChat}
              notifyCount={notifyCount}
              isCallEnabled={isCallEnabled}
              scheduleCallConfig={this.props.scheduleCallConfig}
              supportFlags={this.state.supportFlags}
              user={this.props.user}
              isWebView={this.state.isWebView}
              shouldOpenRaiseAQueryOnMount={shouldOpenRaiseAQueryOnMount}
              fetchSupportFlags={this.fetchSupportFlags}
            />
          )}
        </Suspense>
      </div>
    );
  }
}
