import React from 'react';
import PropTypes from 'prop-types';

const Timeline = ({ items }) => {
  return (
    <div className="timeline-container">
      <img
        className="timeline-img"
        src={`${window.cdnBaseUrl}/static/assets/capital/loc_nudges/progress.svg`}
        alt="timeline"
      />
      <div className="timeline-items">
        {items?.map((item, idx) => (
          <div className="timeline-item" key={idx}>
            {item.title ? <div className="timeline-item-title">{item.title}</div> : null}
            <div className="timeline-item-label">{item.label}</div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default Timeline;

Timeline.propTypes = {
  items: PropTypes.array.isRequired,
};
