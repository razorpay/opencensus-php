import React, { Component, lazy } from 'react';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { trackSupportOptions } from 'merchant/components/Support/ga';
import { fetchTicketsRaisedByAgents } from 'merchant/reducers/config';
import { analyticsTrack } from 'common/utils/analytics';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import WriteToUsPopup from 'merchant/components/Support/components/WriteToUsPopup';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { initCare, TicketSystemEmitter } from 'merchant/care/init';
import ErrorBoundary, { Ranks, Teams, InlineFallbackComponent } from 'common/new-ui/ErrorBoundary';
import { Modal, ModalBody } from 'common/components/Modal';
import getMobileDetect from 'common/utils/mobileDetect';
import errorService from '@razorpay/universe-utils/errorService';
import {
  getCommonSupportProperties,
  getDeviceSource,
} from 'merchant/components/Support/getCommonSupportProperties';
import { getCookie, setCookie } from 'common/utils/cookies';
import SupportActions from 'merchant/components/Support/components/SupportActions';
import { isMobileDevice } from 'merchant/components/Home/data';

const OpenRequestStatus = lazy(() =>
  import('@razorpay/frontend-care').then((module) => ({
    default: module.OpenRequestStatus,
  })),
);

const SupportSection = lazy(
  () => import(/* webpackChunkName: 'frontend-care' */ '@razorpay/frontend-care'),
  // This will be replaced by @razorpay/care in prod
);

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
    careSupportSection: null,
    openClickToCall: false,
    isClickToCallSubmitted: false,
    isChatWithUsDisabled: false,
    modalToBeOpenedOnBackClick: '',
  };
  openDashboardGuide = (_) => {
    analyticsTrack({
      objectName: 'dashboard guide',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties(),
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
        ...getCommonSupportProperties(),
      },
    });
    const {
      user = {},
      supportFlags = {},
      openModal: _openModal,
      closeModal: _closeModal,
    } = this.props;
    const rzpTicketSystem = window.rzpTicketSystem;
    if (rzpTicketSystem) {
      if (pcb) {
        pcb();
      }
      if (supportFlags.show_create_ticket_popup) {
        _openModal({
          size: 'small',
          component: (
            <WriteToUsPopup
              businessName={user.name}
              id={id}
              supportFlags={supportFlags}
              closeModal={_closeModal}
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
    const {
      user = {},
      shouldOpenRaiseAQueryOnMount,
      fetchTicketsRaisedByAgents: _fetchTickets,
    } = this.props;
    window.rzpTicketSystem = {
      openModal: (id, data) => {
        initCare(user, { id, data });
      },
      options: {},
    };

    if (user.isMobileSignupCareActive) {
      _fetchTickets();
    }

    CreateTicketEmitter.on('create-ticket', (id, pcb, lcb) => {
      this.createTicket(id, pcb, lcb);
    });
    TicketSystemEmitter.on('openModal', (module, initialData) => {
      this.setState({
        careSupportSection: {
          module,
          initialData,
        },
      });
    });
    TicketSystemEmitter.on('closeModal', () => {
      this.handleCloseCareSupportSection();
    });
    const timingConfigParam = {
      url: 'merchants/chat/timings_config',
      headers: {
        'Content-Type': 'application/json',
      },
    };
    const clickToCallParam = {
      url: `care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckInstantCallbackEligibility`,
      mode: 'live',
      method: 'post',
      headers: {
        'Content-Type': 'application/json',
      },
    };
    Promise.all([
      merchantFetch(timingConfigParam).then((response) => {
        if (response?.success) {
          this.setState({ timings: response?.data });
        }
      }),
      merchantFetch(clickToCallParam).then((response) => {
        if (response?.success) {
          this.setState({ openClickToCall: response?.data?.is_eligible }, () => {
            const { user: { isFrontendCareActive, isClickToCallActive } = {} } = this.props;

            const { openClickToCall } = this.state;

            if (isFrontendCareActive && isClickToCallActive && openClickToCall) {
              analyticsTrack({
                objectName: 'Click to Call',
                actionName: 'Initialised',
                screen: 'home page',
                properties: {
                  ...getCommonAnalyticsProperties(window.rzp_user),
                  ...getCommonSupportProperties(),
                },
              });
            }
          });
        }
      }),
    ]).catch((err) => console.log(err));

    if (getMobileDetect().isWebView() && shouldOpenRaiseAQueryOnMount) {
      window.rzpTicketSystem.openModal(`#tickets`);
    }
    this.checkIfClickToCallSubmitted();
  }

  checkIfClickToCallSubmitted = () => {
    if (getCookie('click-to-call-submitted')) {
      this.setState({ isClickToCallSubmitted: true });
    }
  };
  handleClick = async (id, screen = 'home page') => {
    const {
      onToggle,
      onChat,
      notifyCount,
      fetchSupportFlags,
      isOpened,
      supportFlags = {},
    } = this.props;
    const rzpTicketSystem = window.rzpTicketSystem;
    if (rzpTicketSystem) {
      trackSupportOptions(id);
      if (id === 'call') {
        if (!isWorkingDay()) {
          return;
        }
      }

      if (id === 'schedule-call') {
        this.scheduleCallTracking();
        // eslint-disable-next-line consistent-return
        return rzpTicketSystem.openModal(`#schedule-call`);
      }

      if (id === 'click-to-call') {
        this.clickToCallTracking();
        // eslint-disable-next-line consistent-return
        return rzpTicketSystem.openModal(`#click-to-call`);
      }

      if (id === 'open-queries') {
        this.openQueriesTracking(screen);
        if (isOpened) onToggle();
        // eslint-disable-next-line consistent-return
        return rzpTicketSystem.openModal(`#open-queries`);
      }
      if (id === 'chat') {
        const newSupportflags = await fetchSupportFlags(false);
        const showChat = Boolean(newSupportflags?.show_chat);

        this.chatWithUsTracking({ showChat });

        if (!showChat) {
          this.setState({
            isChatWithUsDisabled: true,
          });
          return;
        }
        // if notifications pending, then enable chat
        if (!supportFlags.show_chat && notifyCount < 1) {
          return;
        }

        onToggle();
        onChat();
        return;
      }

      if (id === 'tickets') {
        this.ticketsTracking();
      }
      onToggle();
      this.createTicket(id);
    } else {
      errorService.captureError('RZP TICKET SYSTEM INIT FAILED', {
        tags: {
          team: Teams.CARE,
        },
        rank: Ranks.P2,
      });
    }
  };

  scheduleCallTracking = () => {
    analyticsTrack({
      objectName: 'request a call',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties(),
      },
    });
  };

  clickToCallTracking = () => {
    analyticsTrack({
      objectName: 'click to call',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties(),
      },
    });
  };
  openQueriesTracking = (screen = 'home page') => {
    analyticsTrack({
      objectName: 'View All Queries',
      actionName: 'clicked',
      screen,
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties(),
      },
    });
  };

  chatWithUsTracking = ({ showChat } = {}) => {
    const { user: { isChatbotLive } = {} } = this.props;

    analyticsTrack({
      objectName: 'chat with us',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        isChatbot: isChatbotLive,
        isAvailable: showChat,
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties(),
      },
    });
  };

  ticketsTracking = () => {
    analyticsTrack({
      objectName: 'have a query',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties(),
      },
    });
  };

  openQueriesTracking = () => {
    analyticsTrack({
      objectName: 'view all Queries',
      actionName: 'clicked',
      screen: 'help section',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties(),
      },
    });
  };

  handleTicketCreatingSuccess = (data) => {
    TicketSystemEmitter.emit('ticket-created', data);
  };

  handleCloseCareSupportSection = () => {
    this.setState(
      {
        careSupportSection: null,
        modalToBeOpenedOnBackClick: '',
      },
      () => {
        const { isWebView } = this.props;
        if (isWebView) {
          try {
            window.ReactNativeWebView.postMessage(JSON.stringify({ eventType: 'EXIT' }));
          } catch (error) {
            errorService.captureError(error, {
              tags: {
                team: Teams.CARE,
              },
              rank: Ranks.P2,
            });
          }
        }
      },
    );
  };

  handleCareAnalytics = (payload) => {
    if (!payload) {
      return;
    }
    const { properties = {}, screen, ...rest } = payload;
    try {
      analyticsTrack({
        ...rest,
        screen: screen || 'help section',
        properties: {
          ...properties,
          ...getCommonAnalyticsProperties(window.rzp_user),
          ...getCommonSupportProperties(),
        },
      });
    } catch {
      //
    }
  };
  onClickToCallSuccess = () => {
    const { isClickToCallSubmitted: hasClickToCallSubmitted } = this.state;
    if (!hasClickToCallSubmitted) {
      const now = new Date();
      const minutes = 30;
      now.setTime(now.getTime() + minutes * 60 * 1000);
      setCookie('click-to-call-submitted', new Date(), now);
      this.setState({ isClickToCallSubmitted: true });
    }
  };

  onRequestFollowUp = ({ ticket, openedFrom } = {}) => {
    if (window.rzpTicketSystem) {
      window.rzpTicketSystem.openModal('#raise-grievance', {
        ticketID: ticket.id,
      });
    }
    analyticsTrack({
      objectName: 'Request follow-up',
      actionName: 'clicked',
      screen: 'help section',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties(),
        ticket_id: ticket.id,
      },
    });
    this.setState({
      modalToBeOpenedOnBackClick: openedFrom,
    });
  };

  handleOpenQueries = ({ screen, openedFrom = 'help' } = {}) => {
    this.handleClick('open-queries', screen);
    this.setState({
      modalToBeOpenedOnBackClick: openedFrom,
    });
  };

  handleBackClick = () => {
    const { modalToBeOpenedOnBackClick = 'help' } = this.state;
    const { onToggle, isOpened } = this.props;

    if (modalToBeOpenedOnBackClick === 'help') {
      this.setState(
        {
          careSupportSection: null,
          modalToBeOpenedOnBackClick: '',
        },
        () => {
          if (!isOpened) {
            onToggle();
          }
        },
      );
    } else {
      this.setState(
        {
          modalToBeOpenedOnBackClick: '',
        },
        () => {
          this.handleClick(modalToBeOpenedOnBackClick);
        },
      );
    }
  };

  render() {
    const {
      notifyCount,
      isOpened,
      onToggle,
      isCallEnabled,
      scheduleCallConfig,
      user,
      isWebView,
      supportFlags,
      botIsLoaded,
    } = this.props;
    const {
      careSupportSection,
      isClickToCallSubmitted,
      isChatWithUsDisabled,
      timings,
      openClickToCall,
    } = this.state;
    const shouldDisable = !isWorkingDay();
    const { showOpenTicketStatus = false } = user;

    let scheduleCallbackReason =
      scheduleCallConfig && scheduleCallConfig.is_eligible === false && scheduleCallConfig.reason
        ? scheduleCallConfig.reason
        : 'For elaborate queries needing quick resolution';

    if (scheduleCallConfig.reason === 'NOT_AVAILABLE') {
      scheduleCallbackReason = (
        <span className="text-danger">Slots are unavailable right now, try later.</span>
      );
    }

    if (scheduleCallConfig.reason === 'ALREADY_BOOKED') {
      scheduleCallbackReason = <span className="text-danger">Call already requested.</span>;
    }

    const today = new Date().getDay();
    let date;
    if (timings.length) {
      date = {
        start: timings[today].start / 60,
        end: timings[today].end / 60,
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

    const isMobile = isMobileDevice(1020);
    return (
      <div className={classList('support-body support-body-old', isOpened && 'active')}>
        <ErrorBoundary
          resetOnProps
          rank={Ranks.P1}
          team={Teams.CARE}
          FallbackComponent={(props) => {
            const [isOpen, setIsOpen] = React.useState(true);
            return (
              <Modal
                isOpen={isOpen}
                onClose={() => {
                  setIsOpen(false);
                  this.handleCloseCareSupportSection();
                }}
              >
                <ModalBody>
                  <InlineFallbackComponent {...props} />
                </ModalBody>
              </Modal>
            );
          }}
        >
          {careSupportSection ? (
            <SupportSection
              user={{
                experiments: user.experiments,
                email: user.email,
                name: user.name,
                id: user.id,
                contact_mobile: user?.user?.contact_mobile,
              }}
              analyticsInstance={this.handleCareAnalytics}
              // removing hash to support frontend care package
              module={careSupportSection.module?.replace('#', '')}
              initialData={careSupportSection.initialData}
              onSuccess={this.handleTicketCreatingSuccess}
              onClose={this.handleCloseCareSupportSection}
              hideModalHeader={isWebView}
              shouldPersistSearchString={isWebView}
              onClickToCallSuccess={this.onClickToCallSuccess}
              shouldOpenExistingTicketsOnNewTab={!isWebView}
              deviceSource={getDeviceSource()}
              onRequestFollowUp={this.onRequestFollowUp}
              handleOpenQueries={this.handleOpenQueries}
              showBackButton={isMobile}
              onBackClick={this.handleBackClick}
            />
          ) : null}

          <header>
            <i className="i i-headset m-r" /> Help and Support{' '}
            <i className="i i-close pull-right mob-close" onClick={onToggle} />
          </header>
          {showOpenTicketStatus && (
            <OpenRequestStatus
              user={{
                experiments: user.experiments,
                email: user.email,
                name: user.name,
                id: user.id,
                contact_mobile: user?.user?.contact_mobile,
              }}
              handleOpenQueries={this.handleOpenQueries}
              shouldPersistSearchString={isWebView}
              shouldOpenExistingTicketsOnNewTab={!isWebView}
              onRequestFollowUp={this.onRequestFollowUp}
              analyticsInstance={this.handleCareAnalytics}
            />
          )}
          <SupportActions
            key="SupportActions"
            notifyCount={notifyCount}
            isCallEnabled={isCallEnabled}
            handleClick={this.handleClick}
            shouldDisable={shouldDisable}
            scheduleCallbackReason={scheduleCallbackReason}
            openClickToCall={openClickToCall}
            user={user}
            supportFlags={supportFlags}
            botIsLoaded={botIsLoaded}
            timings={timings}
            date={date}
            isEligible={scheduleCallConfig.is_eligible}
            isClickToCallSubmitted={isClickToCallSubmitted}
            isChatWithUsDisabled={isChatWithUsDisabled}
            openDashboardGuide={this.openDashboardGuide}
          />
        </ErrorBoundary>
      </div>
    );
  }
}

export default SupportBody;
