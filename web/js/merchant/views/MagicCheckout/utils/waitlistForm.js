import LeadFormModal from 'merchant/views/MagicCheckout/LeadForm';
import { createPopup } from '@typeform/embed';

let feedbackForm;

export const loadWaitlistForm = (openModal, cb = () => {}) => {
  openModal({
    size: 'large',
    className: 'magic-lead-form',
    component: <LeadFormModal onSubmit={cb} />,
  });
};

export const loadFeedbackForm = (user, onSubmit = () => {}) => {
  const formId = 'UQhV85jo';
  const { current: merchantId, email } = user;
  if (!feedbackForm) {
    feedbackForm = createPopup(formId, {
      hideHeaders: true,
      hideFooters: true,
      hidden: { mid: merchantId, email },
      onSubmit,
    });
  }
  feedbackForm.toggle();
};
