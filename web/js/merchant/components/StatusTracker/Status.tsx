import React from 'react';
import { TimelineStatus, TimelineIcon, H5 } from './StatusTrackerStyled';
import { StatusType } from './statusTracker.types';
import Button from '@razorpay/blade-old/src/atoms/Button';
import { STATUS_TRACKER_STATUS } from './constants';
import GreenTickIcon from 'assets/status-tracker/statusIcon/greenTick.svg';
import InProgressIcon from 'assets/status-tracker/statusIcon/inProgress.svg';
import GrayLockIcon from 'assets/status-tracker/statusIcon/grayLock.svg';
import ErrorStatusIcon from 'assets/status-tracker/statusIcon/errorStatus.svg';
import GreenStatusIcon from 'assets/status-tracker/statusIcon/greenStatus.svg';

const STATUS_TRACKER = Object.freeze({
  [STATUS_TRACKER_STATUS.DONE]: {
    color: '#2ac5a01a',
    icon: GreenTickIcon,
  },
  [STATUS_TRACKER_STATUS.IN_PROGRESS]: { color: '#62aaff1a', icon: InProgressIcon },
  [STATUS_TRACKER_STATUS.TO_BE_PICKED]: { color: '#162f561a', icon: GrayLockIcon },
  [STATUS_TRACKER_STATUS.ERROR]: { color: '#162f061a', icon: ErrorStatusIcon },
  [STATUS_TRACKER_STATUS.SUCCESS]: { color: '#2ac5a01a', icon: GreenStatusIcon },
});

const Status = ({
  title,
  description,
  status,
  buttons,
  isActive,
  index,
  stepsLength,
}: StatusType): JSX.Element => {
  return (
    <TimelineStatus className="timeline__status" index={index} stepsLength={stepsLength}>
      <TimelineIcon className="timeline__status--icon" colorStatus={STATUS_TRACKER[status].color}>
        <img src={STATUS_TRACKER[status].icon} />
      </TimelineIcon>

      <div className="timeline__status--header">
        <H5 isActive={isActive} stepsLength={stepsLength}>
          {title}
        </H5>
        {description ? <div className="timeline__status--subText">{description}</div> : null}
        <div className="multipleButton">
          {buttons && buttons.length > 1
            ? buttons.map(({ style, label, onClick }, index) => (
                <Button variant={style} onClick={onClick} key={`${label}_${index}`}>
                  {label}
                </Button>
              ))
            : null}
        </div>
      </div>
      {buttons?.length === 1 ? (
        <div className="button">
          <Button size="small" variant={buttons[0]?.style} onClick={buttons[0]?.onClick}>
            {buttons[0]?.label}
          </Button>
        </div>
      ) : null}
    </TimelineStatus>
  );
};
Status.defaultProps = {
  title: '',
  description: '',
  status: '',
  buttons: [],
  isActive: false,
  index: 0,
  stepsLength: 0,
};
export default Status;
