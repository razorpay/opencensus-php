import { createPopup } from '@typeform/embed';

export default function getSurveyForm(user, onSubmit = () => {}, surveyURL = 'Kzw8bOUb') {
  const { current: merchantId, email } = user;
  return createPopup(surveyURL, {
    hideHeaders: true,
    hideFooters: true,
    hidden: { mid: merchantId, email },
    onSubmit,
  });
}
