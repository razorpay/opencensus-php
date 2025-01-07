import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import ComingSoon from 'merchant/components/ComingSoon';
import { RZPFeatures } from 'merchant/helpers/data';
import { setOnBoardingDataInLocalState } from 'merchant/components/OnBoarding';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateFeatures } from 'merchant/reducers/config';
import { saveOnboarding, handleProductQuickGuide } from 'merchant/reducers/onboarding';
import featuresList from './features';
import track from './track';

@connect(
  (state) => {
    return {
      user: state.session.user,
      isTestMode: state.session.mode === 'test',
      onboarding: state.onboarding,
    };
  },
  {
    saveOnboarding,
    updateFeatures,
    showNotification,
    handleProductQuickGuide,
  },
)
@RTracking(() => window.rzpQ.component('QRComingSoonContainer'))
export default class QRComingSoonContainer extends React.Component {
  componentDidMount() {
    track.init({
      track: this.props.tracking.trackEvent,
    });

    track.open();
  }

  handleEnableFeature = () => {
    if (!this.props.isTestMode) {
      track.interested();
    }

    this.props.onInterestClicked();
  };

  render() {
    return (
      <ComingSoon
        product="QR codes"
        title="Razorpay QR Codes"
        description="Adopt contactless payments through customized UPI & Bharat QR Codes"
        features={featuresList}
        previewURL={require("assets/qr_code/product_preview.gif")}
        interestClicked={this.handleEnableFeature}
      />
    );
  }
}
