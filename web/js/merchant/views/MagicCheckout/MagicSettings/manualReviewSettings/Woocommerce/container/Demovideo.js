import { MANUAL_REVIEW_MODAL } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const DemoVideo = ({ platform }) => (
  <div className="magic-checkout-demo-content text-center">
    <video className="magic-checkout-demo-video" width="100%" controls autoPlay loop>
      <source src={MANUAL_REVIEW_MODAL[platform].demoUrl} type="video/mp4" />
    </video>
  </div>
);

export default DemoVideo;
