import React from 'react';
import StepFooter from './StepFooter';
import { StyledErrorContent } from './styled';
export default ({ imageURL, title, description, buttonLabel, onButtonClick }) => (
  <>
    <StyledErrorContent>
      <img className="error-image" src={imageURL} />
      <div className="error-screen-title">{title}</div>
      <div className="error-screen-desc">{description}</div>
    </StyledErrorContent>

    <StepFooter ctaText={buttonLabel} onClick={onButtonClick} />
  </>
);
