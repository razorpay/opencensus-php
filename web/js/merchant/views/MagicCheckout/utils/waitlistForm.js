import { createPopup } from '@typeform/embed';

let waitlistForm, feedbackForm;

export const loadWaitlistForm = (user, cb = () => {}) => {
  const formId = 'RJ8edvCU';
  const { current: mid, email } = user;

  if (!waitlistForm) {
    waitlistForm = createPopup(formId, {
      hideHeaders: true,
      hideFooters: true,
      hidden: { mid, email },
      onSubmit: () => {
        cb();
      },
    });
  }

  waitlistForm.toggle();
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
