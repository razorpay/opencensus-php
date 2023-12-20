import { useSplitzService } from 'common/splitz';

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
