import { makePopup } from '@typeform/embed';

export default function getSurveyForm(user, onSubmit = () => {}) {
  const { current: merchantId, email } = user;
  const surveyURL = `https://razorpay.typeform.com/to/Kzw8bOUb?mid=${merchantId}&email=${email}`;
  return makePopup(surveyURL, {
    mode: 'popup',
    hideHeaders: true,
    hideFooters: true,
    onSubmit,
  });
}
