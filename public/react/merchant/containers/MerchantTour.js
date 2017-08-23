import { Component, Children } from 'react';
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
    showOnboardingTour: false,
  };

  componentWillMount() {
    let isNewUIEnabled = this.props.user.isNewUIEnabled;
    let showNewUITour = LocalStorageService.getItem('show_newui_tour');

    if (isNewUIEnabled && showNewUITour) {
      this.setState({
        showOnboardingTour: true,
      });

      window.setTimeout(() => {
        this.props.openModal({
          size: 'small',
          component: (
            <NewUIOnboardingDialog
              onShowChanges={this.showTour}
              onCancelClick={this.closeTour}
            />
          ),
        });
      }, 1500);
    }
  }

  showTour = () => {
    this.props.closeModal();
    this.setState({ isTourActive: true });
  };

  closeTour = () => {
    LocalStorageService.removeItem('show_newui_tour');
    this.setState({ isTourActive: false });
    this.props.closeModal();
  };

  gotoNextTourStep = () => {
    this.setState({ activeTourStep: this.state.activeTourStep + 1 });
  };

  render() {
    let isNewUIEnabled = this.props.user.isNewUIEnabled;
    let showOnboardingTour = this.state.showOnboardingTour;

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
              ,
              {' '}
              <b>Refunds</b>
              {' '}
              and
              {' '}
              <b>Orders</b>
              {' '}
              have moved to Transactions.
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
              are now under My Account.
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
              have moved to Settings.
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
          <TourStep
            to="#profile-dropdown"
            attachment="top center"
            targetAttachment="bottom left"
            offset="-15px 30px"
            arrowLeftPos="85%"
          >
            <p>
              Prefer the old design? Click here to switch or to give feedback.
            </p>
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
