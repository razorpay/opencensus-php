import { connect } from 'react-redux';
import RTracking from 'react-tracking';

import ComingSoon from 'merchant/components/ComingSoon';
import { RZPFeatures } from 'merchant/helpers/data';
import { setOnBoardingDataInLocalState } from 'merchant/components/OnBoarding';
import { showNotification } from 'merchant_common/reducers/notifications';
import { updateFeatures } from 'merchant/reducers/config';
import { saveOnboarding, handleProductQuickGuide } from 'merchant/reducers/onboarding';
import featuresList from './features.json';
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
    return this.props
      .updateFeatures(
        {
          features: {
            qr_codes: 1,
          },
        },
        this.props.user.current,
      )
      .then((res) => {
        window.location.reload();
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    return (
      <ComingSoon
        product="QR codes"
        title="Razorpay QR Codes"
        description="Adopt contactless payments through customized UPI & Bharat QR Codes"
        features={featuresList}
        previewURL="/dist/css/assets/qr_code/product_preview.gif"
        interestClicked={() => {
          window.rzpQ.push(window.rzpQ.now().qrCode().interaction('qr.click.interested'));

          this.handleEnableFeature();
        }}
      />
    );
  }
}
