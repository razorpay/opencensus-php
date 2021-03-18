import ComingSoon from 'merchant/components/ComingSoon';
import featuresList from './features.json';

const ComingSoonContainer = () => {
  return (
    <ComingSoon
      product="QR codes"
      title="Razorpay QR Codes"
      description="Adopt contactless payments through customized UPI & Bharat QR Codes"
      features={featuresList}
      previewURL="/dist/css/assets/qr_code/product_preview.gif"
    />
  );
};

export default ComingSoonContainer;
