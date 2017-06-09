import { Component } from 'react';
import { connect } from 'react-redux';
import { Tour, TourStep } from 'rzp/ui/Tour';
import * as ModalActions from 'rzp/modules/modals';
import LocalStorageService from 'rzp/utils/localStorage';
import NewUIOnboardingDialog from 'merchant/components/NewUIOnboardingDialog';

@connect(null, ModalActions)
export default class MerchantTour extends Component {
  state = {
    isTourActive: false,
    activeTourStep: 0,
  };

  componentWillMount() {
    let isNewUIEnabled = this.props.user.isNewUIEnabled;
    let isNewUITourShown = LocalStorageService.getItem('newui_tour_shown');

    if (isNewUIEnabled && !isNewUITourShown) {
      window.setTimeout(() => {
        this.props.openModal({
          size: 'small',
          component: <NewUIOnboardingDialog onShowChanges={this.showTour} />,
        });
      }, 1000);
    }
  }

  showTour = () => {
    this.props.closeModal();
    this.setState({ isTourActive: true });
  };

  closeTour = () => {
    this.setState({ isTourActive: false });
  };

  gotoNextTourStep = () => {
    this.setState({ activeTourStep: this.state.activeTourStep + 1 });
  };

  render() {
    return (
      <div>
        <Tour
          isActive={this.state.isTourActive}
          tourStep={this.state.activeTourStep}
        >
          <TourStep to="#transactions-nav">
            <p>
              <b>Payments</b>
              ,
              {' '}
              <b>Refunds</b>
              {' '}
              and
              {' '}
              <b>Orders</b>
              {' '}
              are moved to Transactions.
            </p>
            <div class="btn-toolbar">
              <button class="btn btn-link" onClick={this.closeTour}>
                Skip
              </button>
              <button
                class="btn btn-link pull-right"
                onClick={this.gotoNextTourStep}
              >
                Next &gt;
              </button>
            </div>
          </TourStep>

          <TourStep to="#myaccount-nav">
            <p>
              <b>Profile</b>
              ,
              {' '}
              <b>Activation</b>
              ,
              {' '}
              <b>Credits</b>
              {' '}
              and
              {' '}
              <b>Add Funds</b>
              {' '}
              are moved to My Account.
            </p>
            <div class="btn-toolbar">
              <button class="btn btn-link" onClick={this.closeTour}>
                Skip
              </button>
              <button
                class="btn btn-link pull-right"
                onClick={this.gotoNextTourStep}
              >
                Next &gt;
              </button>
            </div>
          </TourStep>

          <TourStep to="#settings-nav">
            <p>
              <b>Configuration</b>
              ,
              {' '}
              <b>API Keys</b>
              , and
              {' '}
              <b>Webhooks</b>
              {' '}
              are moved to Settings.
            </p>
            <div class="btn-toolbar">
              <button class="btn btn-link pull-right" onClick={this.closeTour}>
                Done!
              </button>
            </div>
          </TourStep>
        </Tour>
      </div>
    );
  }
}
