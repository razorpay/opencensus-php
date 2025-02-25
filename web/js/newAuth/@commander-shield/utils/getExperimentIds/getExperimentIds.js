import { signUpSrc } from '../../screens/screenHelpers';
import { REMOVE_PRESIGNUP_FUNCTIONALITY } from '../../shared/Experiments/Experiments';
import getExpStatus from '../getExperimentStatus';

const getExperimentIds = (data, locationQuery) => {
  const experimentIds = [];
  const authSource = locationQuery.get('auth_source');
  if (authSource === signUpSrc.websitePaymentLink) {
    experimentIds.push('Signup_experiment_1');
  }
  if (getExpStatus(data, REMOVE_PRESIGNUP_FUNCTIONALITY)) {
    experimentIds.push('Lead questions removed');
  }

  return experimentIds;
};

export default getExperimentIds;
