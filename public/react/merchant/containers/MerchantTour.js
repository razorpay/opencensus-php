import { Component } from 'react';
import { connect } from 'react-redux';
import { Tour, TourStep } from 'rzp/ui/Tour';
import { fetchGST } from 'merchant/modules/profile';
import LocalStorageService from 'rzp/utils/localStorage';

@connect(null, { fetchGST })
export default class MerchantTour extends Component {
  state = {
    isTourActive: false,
    activeTourStep: 0,
  };

  componentWillMount() {
    if (LocalStorageService.getItem('gst_tour_shown')) {
      return;
    }

    this.props.fetchGST().then(({ data }) => {
      if (!data.gstin && !data.p_gstin) {
        this.showTour();
      }
    });
  }

  showTour = () => {
    this.setState({ isTourActive: true });
  };

  closeTour = () => {
    LocalStorageService.setItem('gst_tour_shown', true);
    this.setState({ isTourActive: false });
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
