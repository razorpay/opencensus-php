import React, { Component, lazy } from 'react';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { merchantFetch } from 'merchant/utils/ajax';
import { trackSupportOptions } from 'merchant/components/Support/ga';
import { fetchTicketsRaisedByAgents } from 'merchant/reducers/config';
import { analyticsTrack } from 'common/utils/analytics';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import WriteToUsPopup from './WriteToUsPopup';
import { CreateTicketEmitter } from '../../../views/TicketSupport/utils';
import { initCare, TicketSystemEmitter } from '../../../care/init';
import ErrorBoundary, { Ranks, Teams, InlineFallbackComponent } from 'common/new-ui/ErrorBoundary';
import { Modal, ModalBody } from 'common/components/Modal';
import errorService from '@razorpay/universe-utils/errorService';
import SupportActions from './SupportActions';

const SupportSection = lazy(() => import('@razorpay/frontend-care-new'));

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
  };
  openDashboardGuide = (_) => {
    analyticsTrack({
      objectName: 'dashboard guide',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        location: 'Help and Support',
        ...getCommonAnalyticsProperties(window.rzp_user),
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
      },
    });
    const user = this.props.user;
    const rzpTicketSystem = window.rzpTicketSystem;
    if (rzpTicketSystem) {
      if (pcb) {
        pcb();
      }
      if (this.props.supportFlags.show_create_ticket_popup) {
        this.props.openModal({
          size: 'small',
          component: (
            <WriteToUsPopup
              businessName={user.name}
              id={id}
              supportFlags={this.props.supportFlags}
              closeModal={this.props.closeModal}
            />
          ),
        });
      } else rzpTicketSystem.openModal(`#${id}`);
      if (lcb) {
        lcb();
      }
    }
  };

  handleCloseCareSupportSection = () => {
    this.setState(
      {
        careSupportSection: null,
      },
      () => {
        if (this.props.isOpened) {
          this.props.onToggle();
        }
        if (this.props.isWebView) {
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

  componentDidMount() {
    window.rzpTicketSystem = {
      openModal: (id, data) => {
        initCare(this.props.user, { id, data });
      },
      options: {},
    };

    if (this.props.user.isMobileSignupCareActive) {
      this.props.fetchTicketsRaisedByAgents();
    }

    CreateTicketEmitter.on('create-ticket', (id, pcb, lcb) => {
      this.createTicket(id, pcb, lcb);
    });
    TicketSystemEmitter.on('openModal', (module, initialData) => {
      this.setState(
        {
          careSupportSection: {
            module,
            initialData,
          },
        },
        () => {
          if (
            ['#ticket', 'ticket', 'tickets', '#tickets'].includes(module) &&
            !this.props.isOpened
          ) {
            this.props.onToggle();
          }
        },
      );
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
      merchantFetch(timingConfigParam).then((resp1) => {
        if (resp1.success) {
          this.setState({ timings: resp1.data });
        }
      }),
      merchantFetch(clickToCallParam).then((resp2) => {
        if (resp2.success) {
          this.setState({ openClickToCall: resp2.data.is_eligible });
        }
      }),
    ]).catch((err) => console.log(err));
  }

  handleClick = (id) => {
    const { onChat, notifyCount, user } = this.props;
    const rzpTicketSystem = window.rzpTicketSystem;
    this.handleCloseCareSupportSection();
    if (rzpTicketSystem) {
      trackSupportOptions(id);
      if (id === 'call') {
        if (!isWorkingDay()) {
          return;
        }
      }

      if (id === 'schedule-call') {
        analyticsTrack({
          objectName: 'request a call',
          actionName: 'clicked',
          screen: 'home page',
          properties: {
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        // eslint-disable-next-line consistent-return
        return rzpTicketSystem.openModal(`#schedule-call`);
      }

      if (id === 'click-to-call') {
        analyticsTrack({
          objectName: 'click to call',
          actionName: 'clicked',
          screen: 'home page',
          properties: {
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        // eslint-disable-next-line consistent-return
        return rzpTicketSystem.openModal(`#click-to-call`);
      }
      if (id === 'chat') {
        const { isChatbotLive } = user;
        analyticsTrack({
          objectName: 'chat with us',
          actionName: 'clicked',
          screen: 'home page',
          properties: {
            location: 'Help and Support',
            isChatbot: isChatbotLive,
            pageUrl: window.location.href,
            pathname: window.location.pathname,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        // if notifications pending, then enable chat
        if (!this.props.supportFlags.show_chat && notifyCount < 1) {
          return;
        }

        onChat();
        return;
      }
      this.createTicket(id);
    } else {
      console.log('RZP TICKET SYSTEM INIT FAILED');
    }
  };

  handleTicketCreatingSuccess = (data) => {
    TicketSystemEmitter.emit('ticket-created', data);
  };

  render() {
    const { notifyCount, isOpened, isCallEnabled, scheduleCallConfig, user } = this.props;
    const { handleClick } = this;
    const { careSupportSection } = this.state;
    const shouldDisable = !isWorkingDay();
    let scheduleCallbackReason =
      scheduleCallConfig && scheduleCallConfig.is_eligible === false && scheduleCallConfig.reason
        ? scheduleCallConfig.reason
        : 'For elaborate queries needing quick resolution';

    if (scheduleCallConfig.reason === 'NOT_AVAILABLE') {
      scheduleCallbackReason = (
        <span class="text-danger">Slots are unavailable right now, try later.</span>
      );
    }

    if (scheduleCallConfig.reason === 'ALREADY_BOOKED') {
      scheduleCallbackReason = <span class="text-danger">Call already requested.</span>;
    }

    const today = new Date().getDay();
    let date;
    if (this.state.timings.length) {
      date = {
        start: this.state.timings[today].start / 60,
        end: this.state.timings[today].end / 60,
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
    return (
      <div class={classList('support-body', isOpened && 'active')}>
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
          <SupportSection
            user={{
              experiments: user.experiments,
              email: user.email,
              name: user.name,
              id: user.id,
              contact_mobile: user?.user?.contact_mobile,
            }}
            analyticsInstance={analyticsTrack}
            // removing hash to support frontend care package
            module={careSupportSection?.module?.replace('#', '')}
            initialData={careSupportSection?.initialData}
            onSuccess={this.handleTicketCreatingSuccess}
            onClose={this.handleCloseCareSupportSection}
            shouldOpenExistingTicketsOnNewTab={!this.props.isWebView}
            shouldPersistSearchString={this.props.isWebView}
            supportComponents={
              this.props.isWebView
                ? []
                : [
                    <SupportActions
                      key="SupportActions"
                      notifyCount={notifyCount}
                      isCallEnabled={isCallEnabled}
                      handleClick={handleClick}
                      shouldDisable={shouldDisable}
                      scheduleCallbackReason={scheduleCallbackReason}
                      openClickToCall={this.state.openClickToCall}
                      user={this.props.user}
                      supportFlags={this.props.supportFlags}
                      botIsLoaded={this.props.botIsLoaded}
                      timings={this.state.timings}
                      date={date}
                      isEligible={scheduleCallConfig.is_eligible}
                    />,
                  ]
            }
          />
        </ErrorBoundary>
      </div>
    );
  }
}

export default SupportBody;
