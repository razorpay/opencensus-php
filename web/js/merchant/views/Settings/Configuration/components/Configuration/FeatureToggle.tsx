import React from 'react';

import { Switch } from '@razorpay/blade/components';
import { RightChildrenWrapper } from './styled';
import LineItems from './LineItems';

import {
  FeatureToggleProps,
  RightChildrenProps,
} from 'merchant/views/Settings/Configuration/components/Configuration/types';

const RightChildren: React.FC<RightChildrenProps> = ({
  isChecked,
  onChange,
  accessibilityLabel,
}) => (
  <RightChildrenWrapper>
    <Switch accessibilityLabel={accessibilityLabel} isChecked={isChecked} onChange={onChange} />
  </RightChildrenWrapper>
);

const FeatureToggle: React.FC<FeatureToggleProps> = ({
  feature,
  isChecked,
  title,
  subTitle,
  isFeature = false,
  toggleHandler,
  extraItems,
  blockData,
}) => {
  const [isShowFeedback, setIsShowFeedback] = React.useState(false);
  const handleSwitchChange = ({ isChecked }: { isChecked: boolean }) => {
    if (toggleHandler) {
      toggleHandler(isChecked);
      if (!isChecked && isFeature) {
        setIsShowFeedback(true);
      } else {
        setIsShowFeedback(false);
      }
    }
  };

  return (
    <LineItems
      title={title}
      subTitle={subTitle}
      blockData={blockData}
      showFeedback={isShowFeedback}
      rightChildren={
        <RightChildren
          isChecked={isChecked}
          onChange={handleSwitchChange}
          accessibilityLabel={`enable-${feature}`}
        />
      }
      extraItems={extraItems}
    />
  );
};

export default FeatureToggle;
