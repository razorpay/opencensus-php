import React from 'react';
import FeaturesList from './FeaturesList';
import Button from 'common/new-ui/Button';

const OfferPageContent = ({
  title,
  helpText,
  description,
  featuresList,
  ctaButton,
  termsAndConditions,
}) => {
  return (
    <>
      <div className="title-mob">{title}</div>
      <div className="offer-page--content">
        <div className="title">{title}</div>
        <div className="help-text">{helpText}</div>
        <div className="description">{description}</div>
        <FeaturesList featuresList={featuresList} />
        <div className="cta-container">
          <Button.Primary
            type="button"
            iconAfter="arrow-forward"
            onClick={() => ctaButton?.onCTAClick()}
          >
            {ctaButton?.label}
          </Button.Primary>
        </div>
        <div className="terms-and-conditions">{termsAndConditions}</div>
      </div>
    </>
  );
};

export default OfferPageContent;
