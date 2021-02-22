import ComingSoon from 'merchant/components/ComingSoon';
import featuresList from './features.json';

// TODO: add product docs url
const QRCodesContainer = () => {
  return (
    <div class="QRCodes">
      <ComingSoon
        product="QR codes"
        title="Razorpay QR Codes"
        description="Adopt contact less payments through customized UPI & Bharat QR Codes"
        features={featuresList}
        previewURL="/dist/css/assets/qr_code/product_preview.gif"
      />
    </div>
  );
};

export default QRCodesContainer;
