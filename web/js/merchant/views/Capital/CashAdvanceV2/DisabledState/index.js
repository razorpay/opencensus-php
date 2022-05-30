import React from 'react';
import { APPLICATION_DISABLED_STATES } from '../constants';
import Button from 'common/new-ui/Button';
import './DisabledState.styl';

const DisabledStateComponent = (props) => {
  const { currentNavigationStatus, handleCtaClick } = props;

  const {
    title = '',
    tips = [],
    subTitle = '',
    actionPoint = '',
    description = '',
    ctaText = '',
  } = APPLICATION_DISABLED_STATES[currentNavigationStatus];
  const entity = 'Cash Advance';

  const handleOnCtaClick = () => {
    handleCtaClick(ctaText);
  };

  return (
    <div className="loan-application-disabled-wrapper">
      <div className="flex title-wrapper">
        <i className="i i-error" />
        <p className="title">{title}</p>
      </div>
      <p className="description">{description}</p>
      {tips.length > 0 && (
        <div className="tips-container">
          {tips.map((item, index) => (
            <div key={index} className="flex wrapper">
              <i className="i i-check text-success" />
              <p className="tip description">{item}</p>
            </div>
          ))}
        </div>
      )}
      <p className="subtitle">{subTitle}</p>
      <p className="action-point description">{actionPoint}</p>
      <Button.Primary className="cta" onClick={handleOnCtaClick}>
        {`${ctaText} ${entity}`}
        <i className="i i-chevron-right" />
      </Button.Primary>
    </div>
  );
};

export default DisabledStateComponent;
