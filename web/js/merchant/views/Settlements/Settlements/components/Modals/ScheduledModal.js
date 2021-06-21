import React, { Component } from 'react';
import { withRouter } from 'react-router-dom';
import ModalHeader from 'common/ui/ModalHeader';
import ajax from 'merchant/utils/ajax';
import { connect } from 'react-redux';
import { closeModal } from 'merchant_common/reducers/modals';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { updateFeatures } from 'merchant/reducers/config';
import User, { setFeatures } from 'merchant/models/User';
import * as SessionActions from 'merchant/reducers/session';
import { showNotification } from 'merchant_common/reducers/notifications';
import ModalCloseReasons from 'merchant/views/Settlements/Settlements/components/Modals/ModalCloseReasons';
import { CreateTicketEmitter } from '../../../../TicketSupport/utils';
import { trackConfirmEnableNow, trackEnableNowClose } from '../../../trackEvents';

@connect(
  (state) => ({
    user: state.session.user,
    features: state.config.features,
  }),
  {
    closeModal,
    updateFeatures,
    ...SessionActions,
    showNotification,
  },
)
class ScheduledModal extends Component {
  constructor(props) {
    super(props);

    this.state = {
      fees: '',
      feeBearer: '',
      autoEnabled: false,
      isLoading: false,
      modalClosed: false,
      errors: '',
    };
  }

  componentDidMount() {
    this.fireGAEvent({
      eventAction: `Click Enable ES`,
      eventLabel: `Enable Scheduled ES - ${this.props.fromWhere}`,
    });
    this.fetchPercentageFees();
    document.addEventListener('keydown', this.escFunction);
  }

  escFunction = (event) => {
    if (event.keyCode === 27) {
      this.setState({ modalClosed: true });
    }
  };

  componentWillUnmount() {
    document.removeEventListener('keydown', this.escFunction);
    if (this.props.onExit) {
      this.props.onExit();
    }
  }

  openSupport = () => {
    this.fireGAEvent({
      eventAction: `Support`,
      eventLabel: `Clicks | Support`,
    });
    if (window.rzpTicketSystem) {
      const rzpTicketSystem = window.rzpTicketSystem;
      CreateTicketEmitter.emit(
        'create-ticket',
        'ticket',
        () => {
          rzpTicketSystem.setPrefill('#request', ['merchant', 'international-early-settlement']);
        },
        () => {
          setTimeout(() => {
            rzpTicketSystem.modal.next();
          }, 0);
        },
      );
    }
  };

  fetchPercentageFees = () => {
    this.setState({
      isLoading: true,
    });
    ajax(
      {
        url: 'es/scheduled_pricing',
        method: 'GET',
      },
      {},
      '/merchant/api',
    )
      .then((response) => {
        this.setState({
          fees: response.data.percent_rate,
          feeBearer: response.data.fee_bearer,
          isLoading: false,
        });
      })
      .catch(() => {
        this.setState({
          errors: 'Error while retrieving Scheduled Pricing',
        });
        this.fireGAEvent({
          eventAction: `Click on Enable`,
          eventLabel: `Failure while fetching price`,
        });
      });
  };

  onEnable = () => {
    trackConfirmEnableNow(this.props.fromBanner ? 'banner' : this.props.location.pathname);
    this.fireGAEvent({
      eventAction: `ES Modal`,
      eventLabel: `Scheduled ES Enabling attempt | Enable Scheduled ES`,
    });
    this.setState({
      isLoading: true,
    });
    ajax(
      {
        url: 'es/scheduled',
        method: 'POST',
      },
      {},
      '/merchant/api',
    )
      .then(() => {
        let updatedUser = new User(this.props.user);
        updatedUser
          .fetch()
          .then((res) => {
            this.props.updateSession({ user: res.data });
            this.setState({
              autoEnabled: true,
              isLoading: false,
            });
            this.fireGAEvent({
              eventAction: `ES Modal`,
              eventLabel: `Scheduled ES Success | Enable Scheduled ES`,
            });
          })
          .catch(() => {
            this.props.showNotification({
              type: 'error',
              message: 'Error loading user profile',
            });
            this.props.closeModal();
          });
      })
      .catch(({ errors }) => {
        const error = (errors || [])[0];
        this.fireGAEvent({
          eventAction: `ES Modal`,
          eventLabel: `ES Scheduled Enabling failure | ${error}`,
        });
        this.setState({
          errors: error,
          isLoading: false,
        });
      });
  };

  successModalHeader = () => {
    return (
      <div>
        <i class="i i-done-all text-success modal-header-success" />
        Successfully Enabled!
      </div>
    );
  };

  fireGAEvent = (eventPayload) => {
    eventPayload['eventCategory'] = this.props.eventCategory;
    window.rzpAnalytics(eventPayload);
  };

  renderFooterCTAEnablement = () => {
    return (
      <div class="footer-container">
        <div>
          <a
            class="btn-link"
            target="_blank"
            href="https://razorpay.com/capital/#faqs"
            onClick={() => {
              this.fireGAEvent({
                eventAction: `ES Modal`,
                eventLabel: `FAQs | ES Modal`,
              });
            }}
          >
            Check FAQs
          </a>
        </div>
        <div class="border"></div>
        <div>
          <a class="btn-link" onClick={this.openSupport}>
            Contact Support
          </a>
        </div>
      </div>
    );
  };

  renderPostEnablement = () => {
    return (
      <>
        <ModalHeader
          title={this.successModalHeader()}
          onCloseClick={() => {
            trackEnableNowClose(this.props.fromBanner ? 'banner' : this.props.location.pathname);
            this.props.closeModal();
          }}
        />
        <div class="modal-body">
          <div class="overflow-box">
            <div class="post-schedule-header">
              <i class="i i-early-settlement scheduled-enable"></i>Scheduled Settlements
            </div>
            <div class="post-schedule-description">
              Congratulations, Your Early Settlement feature has now been enabled, Never Fall short
              of Cash Now!
            </div>
            <Button.Primary
              class="submit-btn"
              onClick={() => {
                this.props.closeModal();
              }}
            >
              Done
            </Button.Primary>
          </div>
          {this.renderFooterCTAEnablement()}
        </div>
      </>
    );
  };

  renderPreEnablement = () => {
    const { isLoading, fees, errors, feeBearer } = this.state;
    const { closeModal } = this.props;
    return (
      <>
        <ModalHeader
          title="Enable Early Settlement"
          onCloseClick={() => {
            trackEnableNowClose(this.props.fromBanner ? 'banner' : this.props.location.pathname);
            if (errors) {
              closeModal();
            } else {
              this.setState({ modalClosed: true });
            }
          }}
        />
        <div class="modal-body">
          <div>
            Early settlements will automatically settle the amount to your account in few hours from
            the time of transaction, everyday.
            <a class="btn-link" target="_blank" href="http://razorpay.com/settlement">
              {` `}Learn more
            </a>
          </div>
          {!errors ? (
            <div class="overflow-box">
              <div class="schedule-header">Here's how instantly it works:</div>
              <div class="schedule-desc-container">
                <div>
                  <div class="schedule-desc-list-container">
                    <div>
                      <i class="i i-early-settlement scheduled-enable"></i>
                    </div>
                    <div>
                      Everyday at <b>9AM</b> and <b>5PM</b> all your payments get settled
                    </div>
                  </div>
                </div>
                {!isLoading && (
                  <div class="schedule-desc-list-container fee-container">
                    <div>
                      <i class="i i-early-settlement scheduled-enable"></i>
                    </div>
                    {feeBearer === 'platform' ? (
                      <div>
                        A minimal fee of <b>{`${fees / 100}%`}</b> charged for each settlement
                      </div>
                    ) : (
                      <div>
                        A minimal fee of <b>{`${fees / 100}%`}</b> charged for each settlement,
                        borne by your customers
                      </div>
                    )}
                  </div>
                )}
              </div>
              <div class="schedule-img-container">
                <img src={'/dist/css/assets/settlements-blue-box.png'} />
              </div>
              <div>
                <AsyncBtn.Primary
                  class="enable-schedule-btn"
                  pendingState="Enabling..."
                  onClick={this.onEnable}
                  disabled={isLoading}
                >
                  Enable Early Settlement
                </AsyncBtn.Primary>
              </div>
            </div>
          ) : (
            <div style={{ color: 'red', marginTop: 20 }}>{errors}</div>
          )}
          {this.renderFooterCTAEnablement()}
        </div>
      </>
    );
  };

  render() {
    const { autoEnabled, modalClosed } = this.state;
    return (
      <div class="container-scheduled-modal">
        {modalClosed ? (
          <ModalCloseReasons
            eventCategory={this.props.eventCategory}
            closeOrigin="Scheduled"
            goBackToInitialModalView={this.props.goBackToInitialModalView}
          />
        ) : autoEnabled ? (
          this.renderPostEnablement()
        ) : (
          this.renderPreEnablement()
        )}
      </div>
    );
  }
}

export default withRouter(ScheduledModal);
