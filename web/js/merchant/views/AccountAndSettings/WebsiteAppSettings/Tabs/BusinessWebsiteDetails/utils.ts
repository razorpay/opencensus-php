import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';

export const isWorkflowChangeAllowed = (workflow) => {
  return (
    !workflow?.loading &&
    (workflow?.workflow_exists === false ||
      !['open', 'approved'].includes(workflow?.workflow_status))
  );
};

export const useBusinessWebsiteRevamp = (): boolean => {
  const {
    abExperiments: { business_website_revamp },
  } = useSplitzService();

  return business_website_revamp?.variables?.result === 'on';
};

export const shouldShowBusinessWebsiteV2 = (experiment, user) => {
  const isV2ExperimentOn = isExperimentActive(experiment);
  const isUserEligibleForV2 = user.isOrgRZP && user.isCountryIndia;
  return isV2ExperimentOn && isUserEligibleForV2;
};
