import ComingSoon from 'merchant/components/ComingSoon';
import featuresList from './features.json';
import { RZPFeatures } from 'merchant/helpers/data';
import { setOnBoardingDataInLocalState } from 'merchant/components/OnBoarding';

const ComingSoonContainer = () => {
  return (
    <ComingSoon
      product="QR codes"
      title="Razorpay QR Codes"
      description="Adopt contactless payments through customized UPI & Bharat QR Codes"
      features={featuresList}
      previewURL="/dist/css/assets/qr_code/product_preview.gif"
      interestClicked={() => {
        window.rzpQ.push(
          window.rzpQ.now().qrCode().interaction('qr.click.interested')
        );

        setOnBoardingDataInLocalState({
          feature: RZPFeatures.QR_CODES,
          data: {
            isEnabled: true,
            lastVisitedTime: Date.now(),
          },
        });

        window.location.reload();
      }}
    />
  );
};

export default ComingSoonContainer;
