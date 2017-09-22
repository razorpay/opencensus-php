import { Component, Children } from 'react';
import { connect } from 'react-redux';
import { Tour, TourStep } from 'rzp/ui/Tour';
import * as ModalActions from 'rzp/modules/modals';
import LocalStorageService from 'rzp/utils/localStorage';
import { showOrHideTour } from 'merchant/modules/session';

@connect(state => state.session, {
  showOrHideTour,
  ...ModalActions,
})
export default class MerchantTour extends Component {
  state = {
    isTourActive: false,
    activeTourStep: 0,
    showOnboardingTour: false,
  };

  componentWillReceiveProps(nextProps) {
    if (this.props.isTourVisible !== nextProps.isTourVisible) {
      nextProps.isTourVisible ? this.showTour() : this.closeTour();
    }
  }

  showTour = () => {
    this.props.closeModal();

    LocalStorageService.setItem('tour_shown', true);
    this.setState({
      isTourActive: true,
      showOnboardingTour: true,
    });
  };

  closeTour = () => {
    this.props.closeModal();

    this.props.showOrHideTour(false);
    this.setState({ isTourActive: false, activeTourStep: 0 });
  };

  setToLastInTour = () => {
    this.setState({ activeTourStep: 3 });
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
          showOverlay={this.state.showOnboardingTour}
        >
          <TourStep to="#transactions-nav">
            <p>
              <b>Payments</b>
              , <b>Refunds</b> and <b>Orders</b> have moved to Transactions.
            </p>
            <div class="btn-toolbar">
              <button class="btn btn-link" onClick={this.setToLastInTour}>
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
              , <b>Activation</b>
              , <b>Credits</b> and <b>Add Funds</b> are now under My Account.
            </p>
            <div class="btn-toolbar">
              <button class="btn btn-link" onClick={this.setToLastInTour}>
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
              , <b>API Keys</b>
              , and <b>Webhooks</b> have moved to Settings.
            </p>
            <div class="btn-toolbar">
              <button class="btn btn-link" onClick={this.setToLastInTour}>
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
          <TourStep
            to="#profile-dropdown"
            attachment="top center"
            targetAttachment="bottom left"
            offset="-15px 30px"
            arrowLeftPos="85%"
          >
            <p>Click here to give feedback or see this UI tour again.</p>
            <div class="btn-toolbar">
              <button class="btn btn-link pull-right" onClick={this.closeTour}>
                Okay, Got it!
              </button>
            </div>
          </TourStep>
        </Tour>
      </div>
    );
  }
}
