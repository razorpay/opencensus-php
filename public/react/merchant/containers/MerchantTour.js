import { Component } from 'react';
import { connect } from 'react-redux';
import { Tour, TourStep } from 'rzp/ui/Tour';
import { fetchGST } from 'merchant/modules/profile';
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
    if (LocalStorageService.getItem('gst_tour_shown')) {
      return;
    }
    let isNewUIEnabled = this.props.user.isNewUIEnabled;
    let isNewUITourShown = LocalStorageService.getItem('newui_tour_shown');

    this.props.fetchGST().then(({ data }) => {
      if (!data.gstin && !data.p_gstin) {
        this.showTour();
      }
    });

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
    LocalStorageService.setItem('gst_tour_shown', true);
    this.setState({ isTourActive: false });
  };

  gotoNextTourStep = () => {
    this.setState({ activeTourStep: this.state.activeTourStep + 1 });
  };

  render() {
    let isNewUIEnabled = this.props.user.isNewUIEnabled;

    return (
      <div>
        <Tour
          isActive={this.state.isTourActive}
          tourStep={this.state.activeTourStep}
          showOverlay={false}
        >
          <TourStep to={isNewUIEnabled ? '#myaccount-nav' : '#profile-nav'}>
            <p>
              Find Razorpay's
              {' '}
              <b>GST</b>
              {' '}
              Number and update your
              {' '}
              <b>GST</b>
              {' '}
              details in
              {' '}
              {isNewUIEnabled ? 'My Account > Profile' : 'Profile'}
              {' '}
              tab.
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
