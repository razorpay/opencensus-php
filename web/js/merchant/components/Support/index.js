import { connect } from 'react-redux';
import { Component, lazy, Suspense } from 'react';
import { trackSupportButton } from './ga';
import { withRouter } from 'react-router-dom';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, classList } from 'common/utils/rzp-utils';
import SupportHeader from 'merchant/components/Support/components/SupportHeader';
import { merchantFetch } from 'merchant/utils/ajax';
import { CHATBOT_CLOSING_TEXTS, COMDEL_URL } from 'merchant/components/Support/constants';
import getMobileDetect from 'common/utils/mobileDetect';
import SupportLoader from 'merchant/components/Support/components/Loader';
import { getCommonSupportProperties } from 'merchant/components/Support/getCommonSupportProperties';
import { isMobileDevice } from 'merchant/components/Home/data';
import initChat from 'merchant/components/Support/chat';
import { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import errorService from '@razorpay/universe-utils/errorService';

const SupportBody = lazy(() => import('merchant/components/Support/components/SupportBody'));

@withRouter
@connect((state) => {
  return {
    user: state.session.user,
    org: state.session.org,
  };
}, null)
export default class Support extends Component {
  state = {
    isOpenedOnce: false,
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
    this.fetchSupportFlags();

    this.handleIsWebView();
  }

  handleIsWebView = () => {
    if (getMobileDetect().isWebView()) {
      this.setState({ isWebView: true });
    }
  };

  fetchSupportFlags = () => {
    const { user = {} } = this.props;
    return new Promise((resolve) => {
      if (user.current) {
        const { supportFlags: oldSupportFlags } = this.state;
        this.setState(
          {
            supportFlags: {
              ...oldSupportFlags,
              isFetching: true,
            },
          },
          async () => {
            try {
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
            } catch (error) {
              errorService.captureError(error, {
                tags: {
                  team: Teams.CARE,
                },
                rank: Ranks.P2,
              });
            }
          },
        );
      } else {
        resolve();
      }
    });
  };

  bindEvents = () => {
    const { user = {} } = this.props;
    //bind events for freshchat if available
    if (window.fcWidget) {
      window.fcWidget.on('widget:opened', () => {
        this.handleVisibility(true);
      });
      window.fcWidget.on('widget:closed', () => {
        this.handleVisibility(false);
      });

      window.fcWidget.on('widget:destroyed', () => {
        this.handleVisibility(false);
      });

      window.fcWidget.on('unreadCount:notify', (response) => {
        this.setState({ notifyCount: response.count });
      });

      if (user.isFreshChatbotLive) {
        window.fcWidget.on('message:received', (payload) => {
          this.handleChatbotMessage(payload);
        });
        window.fcWidget.on('csat:updated', (payload) => {
          this.handleChatbotMessage(payload, true);
        });
      }
    }
  };

  handleInitChat = () => {
    const { user = {} } = this.props;

    setTimeout(() => {
      initChat(user, this.onFreshchatScriptLoad);
    }, 0);

    if (user.isFreshChatbotLive) {
      analyticsTrack({
        objectName: 'chatbot',
        actionName: 'initialised',
        screen: 'home page',
        properties: {
          isContextual: user.isFreshChatbotLive,
          ...getCommonAnalyticsProperties(window.rzp_user),
          ...getCommonSupportProperties(),
        },
      });
    }
  };

  onFreshchatScriptLoad = () => {
    this.setState({ botIsLoaded: true });
    this.bindEvents();
  };

  handleChatbotMessage = (payload = {}, isCsatUpdated = false) => {
    const message = payload?.message?.messageFragments?.[0]?.content || '';
    if (CHATBOT_CLOSING_TEXTS.includes(message) || isCsatUpdated) {
      window.fcWidget.destroy();
      window.fcWidget.on('widget:destroyed', () => {
        this.handleInitChat();
        setTimeout(() => {
          this.handleChat();
        }, 100);
      });
    } else if (message.includes('Please wait while we connect you to a live agent.')) {
      analyticsTrack({
        objectName: 'chat with live agent',
        actionName: 'initiated',
        screen: 'home page',
        properties: {
          message,
          ...getCommonAnalyticsProperties(window.rzp_user),
          ...getCommonSupportProperties(),
        },
      });
    }
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

    const { isOpened, isOpenedOnce } = this.state;

    if (!isOpened) {
      trackSupportButton();
    }

    const isMobile = isMobileDevice(1020);

    if (isMobile) {
      if (!isOpened) {
        this.disableScrolling();
      } else {
        this.enableScrolling();
      }
    }

    if (!isOpenedOnce) {
      this.handleInitChat();
    }

    this.setState({
      isOpened: !isOpened,
      isOpenedOnce: true,
    });

    return null;
  };

  handleVisibility = (shouldHide) => {
    this.setState({ isHidden: shouldHide });
  };

  handleChat = () => {
    if (window.fcWidget) {
      window.fcWidget.open();
      this.handleVisibility(true);
    }
  };

  render() {
    const { user, org, history } = this.props;
    const {
      notifyCount,
      isOpened,
      isHidden,
      isWebView,
      botIsLoaded,
      supportFlags,
      isOpenedOnce,
    } = this.state;

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

    return (
      <div className={classList('support', isHidden && 'hidden')}>
        <SupportHeader
          onToggle={this.handleToggle}
          isOpened={isOpened}
          notifyCount={notifyCount}
          isOnBoardingRevampScreen={isOnBoardingRevampScreen}
          showComdelPopover={user.isComdelApiEnabled}
          isWebView={isWebView}
          user={user}
        />
        <Suspense fallback={<SupportLoader showLoader={isOpened} />}>
          <SupportBody
            onToggle={this.handleToggle}
            isOpened={isOpened}
            isOpenedOnce={isOpenedOnce}
            botIsLoaded={botIsLoaded}
            onChat={this.handleChat}
            notifyCount={notifyCount}
            supportFlags={supportFlags}
            user={user}
            isWebView={isWebView}
            shouldOpenRaiseAQueryOnMount={shouldOpenRaiseAQueryOnMount}
            fetchSupportFlags={this.fetchSupportFlags}
          />
        </Suspense>
      </div>
    );
  }
}
