import React from 'react';
import { TrackerViewType, TrackerLeftIllustrationType } from './statusTracker.types';
import { BackgroundImage } from './StatusTrackerStyled';
import Status from './Status';

const TrackerView = ({
  stepsLength,
  currentSteps,
  toggleView,
  isViewMore,
  rightIllustration,
}: TrackerViewType): JSX.Element => {
  return (
    <div className="status-tracker__center">
      <div className="timeline">
        {currentSteps.map(({ title, description, isActive, status, buttons }, index) => (
          <Status
            key={`${index}_${title}`}
            title={title}
            description={description}
            status={status}
            isActive={isActive}
            buttons={buttons}
            index={index}
            stepsLength={stepsLength}
          />
        ))}
        {stepsLength > 1 ? (
          <div className="view-more" onClick={toggleView}>
            View {isViewMore ? 'Less' : 'More'}{' '}
            <i className={`i ${isViewMore ? 'i-chevron-up' : 'i-chevron-down'}`} />
          </div>
        ) : null}
      </div>
      <BackgroundImage rightIllustration={rightIllustration} />
    </div>
  );
};
const TrackerLeftIllustration = ({
  title,
  description,
}: TrackerLeftIllustrationType): JSX.Element => (
  <div className="status-tracker__left">
    <div className="status-tracker__left--title">{title}</div>
    <div className="status-tracker__left--desc">{description}</div>
  </div>
);

export default TrackerView;
export { TrackerLeftIllustration };
