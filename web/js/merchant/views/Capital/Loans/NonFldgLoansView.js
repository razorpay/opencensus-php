import React from 'react';
import Button from 'common/new-ui/Button';
import { NON_FLDG_LOANS_DATA } from './constants';
import UpRightLogo from '../../../../../css/assets/capital/arrow-up-right.svg';

const NonFldgLoansView = () => {
  const {
    title = '',
    tips = [],
    action_point = '',
    description = '',
    ctaText = '',
    redirectTo = '',
  } = NON_FLDG_LOANS_DATA;

  return (
    <div className="non-fldg-loan-onboarding-body">
      <div className="title">{title}</div>

      <div className="inner">
        <p className="description">{description}</p>
        {tips.length > 0 && (
          <div className="tips-container">
            {tips.map((item) => (
              <div key={item} className="flex wrapper">
                <i class="i i-check text-success" />
                <p className="text tip">{item}</p>
              </div>
            ))}
          </div>
        )}
        <p className="text action-point">{action_point}</p>
        <a href={redirectTo} target="_blank" rel="noreferrer">
          <Button.Primary className="text cta">
            {ctaText}
            <img src={UpRightLogo} alt="UpRightLogo" />
          </Button.Primary>
        </a>
      </div>
    </div>
  );
};

export default NonFldgLoansView;
