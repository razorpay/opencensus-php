import React from 'react';
import { StyledFooter } from './styled';
import {
  getOnPrivacyPolicyClicked,
  getOnTnCClicked,
} from 'merchant/views/PartnerDashboard/Onboarding/steps/helpers/analytics';
import { POLICY_LINKS } from 'merchant/constants/urls';

const TNC_LINKS = {
  rzp: POLICY_LINKS.PARTNER_TERMS_OF_USE,
  curlec: POLICY_LINKS.PARTNER_TERMS_OF_USE_CURLEC,
};

const PRIVACY_LINKS = {
  rzp: POLICY_LINKS.PRIVACY_POLICY,
  curlec: POLICY_LINKS.PRIVACY_POLICY_CURLEC,
};

interface TnCFooterProps {
  orgDetails: { custom_code: string };
  screenName: string;
  handleOtherCTAClicks: () => void;
}

const TnCFooter: React.FC<TnCFooterProps> = ({ orgDetails, screenName, handleOtherCTAClicks }) => {
  const orgCode = orgDetails.custom_code;
  const onPrivacyPolicyClicked = getOnPrivacyPolicyClicked(screenName, handleOtherCTAClicks);
  const onTnCClicked = getOnTnCClicked(screenName, handleOtherCTAClicks);
  return (
    <StyledFooter data-testid="tnc-footer">
      By signing up you agree to our{' '}
      <a
        href={PRIVACY_LINKS[orgCode]}
        target="_blank"
        onClick={onPrivacyPolicyClicked}
        rel="noopener noreferrer"
      >
        privacy policy
      </a>{' '}
      and{' '}
      <a
        href={TNC_LINKS[orgCode]}
        target="_blank"
        className="highlight"
        onClick={onTnCClicked}
        rel="noreferrer noopener"
      >
        terms of use.
      </a>
    </StyledFooter>
  );
};

export default TnCFooter;
