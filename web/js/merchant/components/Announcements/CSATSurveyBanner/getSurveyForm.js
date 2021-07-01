import { createPopup } from '@typeform/embed';

export default function getSurveyForm(user, onSubmit = () => {}) {
  const { current: merchantId, email } = user;
  const surveyURL = 'Kzw8bOUb';
  return createPopup(surveyURL, {
    hideHeaders: true,
    hideFooters: true,
    hidden: { mid: merchantId, email },
    onSubmit,
  });
}
