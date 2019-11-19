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
import ModalCloseReasons from './ModalCloseReasons';

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
      autoEnabled: false,
      isLoading: false,
      modalClosed: false,
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
          isLoading: false,
        });
      })
      .catch(response => {
        this.props.showNotification({
          type: 'error',
          message: 'Error while retrieving Scheduled Pricing',
          hidePrevious: true,
        });
        this.props.closeModal();
      });
  };

  onEnable = () => {
    this.fireGAEvent({
      eventAction: `ES Modal`,
      eventLabel: `On-Demand Success | Enable Scheduled ES`,
    });
    let payload = {
      features: {
        es_on_demand: '0',
        es_automatic: '1',
      },
      should_sync: 1,
    };
    this.setState({
      isLoading: true,
    });
    this.props
      .updateFeatures(payload, this.props.user.current)
      .then(res => {
        let newUser = new User(this.props.user);
        newUser.features = setFeatures(res.success ? res.data.features : []);
        this.props.updateSession({ user: newUser });
        this.setState({
          autoEnabled: true,
          isLoading: false,
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: 'There was a problem enabling ES - Scheduling',
          hidePrevious: true,
        });
        this.props.closeModal();
      });
  };

  successModalHeader = () => {
    return (
      <div>
        <i className="i i-done-all text-success modal-header-success" />
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
        <div className="modal-body">
          Your Early settlement feature request has been sent to Razorpay's
          operation team. Thus early settlement feature will be activated in 1
          working day.
          <div className="border">
            <p>
              Early settlement applies to domestic settlements only. For
              International, please{' '}
            </p>
            <a className="btn-link" onClick={this.openSupport}>
              Contact support
            </a>
          </div>
          <a
            target="_blank"
            href="https://razorpay.freshdesk.com/support/solutions/folders/11000011340"
            className="highlight-support"
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
            className="pull-right"
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
            this.setState({ modalClosed: true });
          }}
        />
        <div className="modal-body">
          <div>
            Early settlements will automatically settle the amount to your
            account in few hours from the time of transaction, everyday.
            <a
              className="btn-link"
              target="_blank"
              href="http://razorpay.com/settlement"
            >
              {` `}Learn more
            </a>
          </div>
          <div className="overflow-box">
            <div className="schedule-header">
              Here's how instantly it works:
            </div>
            <div className="schedule-desc-container">
              <ul className="schedule-desc">
                <li>
                  Everyday at <b>9AM</b> and <b>5PM</b> all your payments get
                  settled
                </li>
                <li>
                  A Minimal fee of <b>{`${this.state.fees / 100}%`}</b> charged
                  for each settlement
                </li>
              </ul>
            </div>
            <div className="schedule-img-container">
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
          <div className="border">
            <p>
              Early settlement applies to domestic settlements only. For
              International, please{' '}
            </p>
            <a className="btn-link" onClick={this.openSupport}>
              Contact support
            </a>
          </div>
        </div>
      </>
    );
  };

  render() {
    return (
      <div className="container-scheduled-modal">
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
