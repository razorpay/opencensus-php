import TimedSlider, { TimedSlide, TimedSliderTabs } from 'common/ui/TimedSlider';
import GoToTransactionsImg from 'assets/product_onboarding/route/goto_transactions.png';
import CapturePaymentImg from 'assets/product_onboarding/route/capture_payment.png';
import CreateTransferImg from 'assets/product_onboarding/route/create_transfer.png';

import ModalHeader from 'common/ui/ModalHeader';
import Image from '../../../../common/ui/Image';

const SLIDER_DATA = [
  {
    duration: 2000,
    imgURL: GoToTransactionsImg,
    title: '1. Go to Transactions',
  },
  {
    duration: 2000,
    imgURL: CapturePaymentImg,
    title: '2. Select a captured payment',
  },
  {
    duration: 2000,
    imgURL: CreateTransferImg,
    title: '3. Create Transfer',
  },
];

export default ({ closeModal }) => (
  <div className="TransfersPreviewModal">
    <ModalHeader onCloseClick={closeModal} />

    <div className="modal-body">
      <TimedSlider AfterFrame={TimedSliderTabs}>
        {SLIDER_DATA.map((data, idx) => (
          <TimedSlide key={idx} {...data}>
            <Image src={data.imgURL} isWebP />
          </TimedSlide>
        ))}
      </TimedSlider>

      <div className="got-it-button" onClick={closeModal}>
        Ok, Got it
      </div>
    </div>
  </div>
);
