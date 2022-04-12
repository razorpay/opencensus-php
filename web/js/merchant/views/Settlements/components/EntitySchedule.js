import React from 'react';
import Popover, { PopoverBody } from 'common/ui/Popover';

const EntitySchedule = ({ entityType, schedule, info }) => {
  return (
    <div className="entity-schedule-container">
      <div className="default-cycle schedule-row">
        <div className="section capitalize">{entityType}</div>
        <div className="section capitalize text-right flex">
          <div className="schedule">
            <strong>{schedule}</strong>
          </div>
          <div>
            {info && (
              <span className="help-content">
                <i className="i i-info-outline flex ml-5 mt-5" />
                <Popover align="bottom" theme="dark" parentQuerySelector=".Modal--medium">
                  <PopoverBody>
                    <div>{info}</div>
                  </PopoverBody>
                </Popover>
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default EntitySchedule;
