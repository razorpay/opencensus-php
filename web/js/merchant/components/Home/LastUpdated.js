import React from 'react';
import Time from 'common/ui/Time';

const LastUpdated = ({ at, customIcon }) =>
  at ? (
    <small>
      <i className={`i ${customIcon ?? 'i-info-circle'}`} />
      &nbsp;
      <span>
        Graph last updated <Time value={at} relative />
      </span>
    </small>
  ) : null;

export default LastUpdated;
