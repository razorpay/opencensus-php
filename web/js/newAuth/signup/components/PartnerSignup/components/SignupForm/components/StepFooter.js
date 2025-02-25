import React from "react";

import { Button } from '@razorpay/blade/components';

import { trackWithSegment } from 'newAuth/trackEvents';

import { StyledFooterWrap } from './styled';

export default ({ onClick, ctaText, disabled, isLoading }) => {
  return (
    <StyledFooterWrap>
      <div className="btn-wrap">
        <Button isFullWidth onClick={onClick} isLoading={isLoading} isDisabled={disabled}>
          {ctaText}
        </Button>
      </div>
      <div className="signup-footer">
        By signing up you agree to our{' '}
        <a
          href="https://razorpay.com/s/terms/partners"
          target="_blank"
          rel="noopener noreferrer"
          className="blue-link"
          onClick={() => {
            trackWithSegment({
              objectName: 'Terms of Use',
              actionName: 'Clicked',
            });
          }}
        >
          terms&nbsp;of&nbsp;use
        </a>{' '}
        and{' '}
        <a
          href="https://razorpay.com/privacy"
          target="_blank"
          rel="noopener noreferrer"
          className="blue-link"
          onClick={() => {
            trackWithSegment({
              objectName: 'Privacy Policy',
              actionName: 'Clicked',
            });
          }}
        >
          privacy&nbsp;policy
        </a>
      </div>
    </StyledFooterWrap>
  );
};
