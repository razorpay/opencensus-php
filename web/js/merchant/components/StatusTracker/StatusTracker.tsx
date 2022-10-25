import React, { useEffect, useState, useMemo } from 'react';
import { StatusTrackerPropsT } from './statusTracker.types';
import './StatusTracker.styl';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import TrackerView, { TrackerLeftIllustration } from './TrackerView';

const StatusTracker = ({
  title,
  description,
  steps,
  rightIllustration,
  onLoad,
  className,
}: StatusTrackerPropsT): JSX.Element => {
  const [isViewMore, setViewMore] = useState(false);
  const [currentSteps, setCurrentSteps] = useState(steps);

  const toggleView = () => {
    setViewMore((prevState) => !prevState);
  };

  const getFinalImage = (): string | undefined => {
    let backgroundImage = rightIllustration;
    for (const item of steps) {
      if (item?.rightIllustration && item?.isActive) {
        backgroundImage = item?.rightIllustration;
        break;
      }
    }
    return backgroundImage;
  };

  useEffect(() => {
    if (typeof onLoad === 'function') onLoad();
  }, []);

  const finalImage = useMemo(() => getFinalImage(), [rightIllustration]);

  useEffect(() => {
    if (steps.length !== 1) {
      if (isViewMore) {
        setCurrentSteps(steps);
      } else {
        setCurrentSteps(steps.filter(({ isActive }) => isActive));
      }
    }
  }, [isViewMore, steps]);

  return (
    <div className={`status-tracker ${className}`}>
      <TrackerLeftIllustration title={title} description={description} />
      <TrackerView
        stepsLength={steps.length}
        currentSteps={currentSteps}
        toggleView={toggleView}
        isViewMore={isViewMore}
        rightIllustration={finalImage}
      />
    </div>
  );
};

const StatusTrackerWrapper = (statusTrackerProps: StatusTrackerPropsT): JSX.Element => {
  return (
    <GrowthAssetEB>
      <StatusTracker {...statusTrackerProps} />
    </GrowthAssetEB>
  );
};
export default React.memo(StatusTrackerWrapper);
