import React, { Component } from 'react';
import ModalHeader from 'rzp/ui/ModalHeader';
import ajax from 'merchant/utils/ajax';
import { connect } from 'react-redux';
import { closeModal } from 'rzp/modules/modals';
import Button, { AsyncBtn } from 'component/Button';
import { updateFeatures } from 'merchant/modules/config';
import User, { setFeatures } from 'merchant/models/User';
import * as SessionActions from 'merchant/modules/session';
import { showNotification } from 'rzp/modules/notifications';

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
    };
  }

  componentDidMount() {
    this.fetchPercentageFees();
  }

  componentWillUnmount() {
    if (this.props.onExit) {
      this.props.onExit();
    }
  }

  openSupport = () => {
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
        console.log(response);
        this.setState({
          fees: response.data.percent_rate,
          isLoading: false,
        });
      })
      .catch(response => {
        this.setState({
          feesFetched: false,
          fees: '',
          isLoading: false,
        });
      });
  };

  onEnable = () => {
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
          onCloseClick={() => this.props.closeModal()}
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
        {this.state.autoEnabled
          ? this.renderPostEnablement()
          : this.renderPreEnablement()}
      </div>
    );
  }
}
