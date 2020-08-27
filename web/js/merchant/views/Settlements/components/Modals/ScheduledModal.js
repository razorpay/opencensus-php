import React, { Component } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import ajax from 'merchant/utils/ajax';
import { connect } from 'react-redux';
import { closeModal } from 'merchant_common/reducers/modals';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { updateFeatures } from 'merchant/reducers/config';
import User, { setFeatures } from 'merchant/models/User';
import * as SessionActions from 'merchant/reducers/session';
import { showNotification } from 'merchant_common/reducers/notifications';
import ModalCloseReasons from 'merchant/views/Settlements/components/Modals/ModalCloseReasons';

@connect(
  state => ({
    user: state.session.user,
    features: state.config.features,
  }),
  {
    closeModal,
    updateFeatures,
    ...SessionActions,
    showNotification,
  }
)
export default class ScheduledModal extends Component {
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

  escFunction = event => {
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
      rzpTicketSystem.setPrefill('#request', [
        'merchant',
        'international-early-settlement',
      ]);
      rzpTicketSystem.openModal('#ticket');
      setTimeout(() => {
        rzpTicketSystem.modal.next();
      }, 0);
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
      '/merchant/api'
    )
      .then(response => {
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
      '/merchant/api'
    )
      .then(() => {
        let updatedUser = new User(this.props.user);
        updatedUser
          .fetch()
          .then(res => {
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

  fireGAEvent = eventPayload => {
    eventPayload['eventCategory'] = 'Dashboard - Early Settlement';
    window.rzpAnalytics(eventPayload);
  };

  renderPostEnablement = () => {
    return (
      <>
        <ModalHeader
          title={this.successModalHeader()}
          onCloseClick={() => this.props.closeModal()}
        />
        <div class="modal-body">
          Congratulations, Your Early Settlement feature has now been enabled.
          Never fall short of cash now!
          <div class="border">
            <p>
              Early settlement applies to domestic settlements only. For
              International, please{' '}
            </p>
            <a class="btn-link" onClick={this.openSupport}>
              Contact support
            </a>
          </div>
          <a
            target="_blank"
            href="https://razorpay.com/capital/#faqs"
            class="highlight-support"
            onClick={() => {
              this.fireGAEvent({
                eventAction: `ES Modal`,
                eventLabel: `FAQs | ES Modal`,
              });
            }}
          >
            Show FAQs
          </a>
          <Button.Primary
            class="pull-right"
            onClick={() => {
              this.props.closeModal();
            }}
          >
            Done
          </Button.Primary>
        </div>
      </>
    );
  };

  renderPreEnablement = () => {
    return (
      <>
        <ModalHeader
          title={'Enable Early Settlement'}
          onCloseClick={() => {
            if (this.state.errors) {
              this.props.closeModal();
            } else {
              this.setState({ modalClosed: true });
            }
          }}
        />
        <div class="modal-body">
          <div>
            Early settlements will automatically settle the amount to your
            account in few hours from the time of transaction, everyday.
            <a
              class="btn-link"
              target="_blank"
              href="http://razorpay.com/settlement"
            >
              {` `}Learn more
            </a>
          </div>
          {!this.state.errors ? (
            <div class="overflow-box">
              <div class="schedule-header">Here's how instantly it works:</div>
              <div class="schedule-desc-container">
                <ul class="schedule-desc">
                  <li>
                    Everyday at <b>9AM</b> and <b>5PM</b> all your payments get
                    settled
                  </li>
                  {!this.state.isLoading && (
                    <div>
                      {this.state.feeBearer === 'platform' ? (
                          <li>
                            A minimal fee of <b>{`${this.state.fees / 100}%`}</b>{' '}
                            charged for each settlement
                          </li>
                        ) : (
                          <li>
                            A minimal fee of <b>{`${this.state.fees / 100}%`}</b>{' '}
                            charged for each settlement, borne by your customers
                          </li>
                        )}                      
                    </div>
                  )}
                </ul>
              </div>
              <div class="schedule-img-container">
                <img src={'/dist/css/assets/settlements-blue-box.png'} />
              </div>
              <div>
                <AsyncBtn.Primary
                  class={'enable-schedule-btn'}
                  pendingState="Enabling..."
                  onClick={this.onEnable}
                  disabled={this.state.isLoading}
                >
                  Enable Early Settlement
                </AsyncBtn.Primary>
              </div>
            </div>
          ) : (
            <div style={{ color: 'red', marginTop: 20 }}>
              {this.state.errors}
            </div>
          )}
          <div class="border">
            <p>
              Early settlement applies to domestic settlements only. For
              International, please{' '}
            </p>
            <a class="btn-link" onClick={this.openSupport}>
              Contact support
            </a>
          </div>
        </div>
      </>
    );
  };

  render() {
    return (
      <div class="container-scheduled-modal">
        {this.state.modalClosed ? (
          <ModalCloseReasons closeOrigin="Scheduled" />
        ) : this.state.autoEnabled ? (
          this.renderPostEnablement()
        ) : (
          this.renderPreEnablement()
        )}
      </div>
    );
  }
}
