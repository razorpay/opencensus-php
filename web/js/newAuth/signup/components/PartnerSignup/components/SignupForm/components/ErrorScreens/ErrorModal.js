import React from 'react';
import { Button } from '@razorpay/blade/components';
import { StyledErrorModalWrap } from './styled';

export default ({
  title,
  description,
  secondaryLabel,
  primaryLabel,
  secondaryButtonClick,
  primaryButtonClick,
}) => {
  return (
    <StyledErrorModalWrap>
      <div className="content">
        <div className="error-title">{title}</div>
        <div className="error-desc">{description}</div>
        <div className="error-btn-wrap">
          <div className="sec-btn-wrap" onClick={secondaryButtonClick}>
            {secondaryLabel}
          </div>
          <div className="primary-btn-wrap">
            <Button size="medium" onClick={primaryButtonClick} isFullWidth>
              {primaryLabel}
            </Button>
          </div>
        </div>
      </div>
    </StyledErrorModalWrap>
  );
};
