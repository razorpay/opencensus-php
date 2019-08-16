import Stories, { Story, StoriesTabs } from 'rzp/ui/Stories';

import ModalHeader from 'rzp/ui/ModalHeader';

export default ({ closeModal }) => (
  <div class="TransfersPreviewModal">
    <ModalHeader onCloseClick={closeModal} />

    <div class="modal-body">
      <Stories afterFrame={StoriesTabs}>
        {STORIES_DATA.map((data, idx) => (
          <Story key={idx} {...data}>
            <img src={data.imgURL} />
          </Story>
        ))}
      </Stories>

      <div class="got-it-button" onClick={closeModal}>
        Ok, Got it
      </div>
    </div>
  </div>
);

const STORIES_DATA = [
  {
    duration: 2000,
    imgURL: 'https://razorpay.com/assets/payments/dashboard.png',
    title: '1. Go to Transactions',
  },
  {
    duration: 2000,
    imgURL: 'https://razorpay.com/assets/payments/dashboard.png',
    title: '2. Select a captured payment',
  },
  {
    duration: 2000,
    imgURL: 'https://razorpay.com/assets/payments/dashboard.png',
    title: '3. Create Transfer',
  },
];
