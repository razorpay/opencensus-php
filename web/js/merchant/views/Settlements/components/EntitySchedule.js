import React from 'react';

const EntitySchedule = ({ entityType, schedule }) => {
  return (
    <div className="entity-schedule-container">
      <div className="default-cycle schedule-row">
        <div className="section capitalize">{entityType}</div>
        <div className="section capitalize text-right">
          <strong>{schedule}</strong>
        </div>
      </div>
    </div>
  );
};

export default EntitySchedule;
