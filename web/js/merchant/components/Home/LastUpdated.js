import React, { Component } from 'react';
import Time from 'rzp/ui/Time';

const LastUpdated = ({at}) => (at ? (
  <small>
    <i className="i i-info-circle" />&nbsp;
    <span>
      The graph data last updated <Time value={at} relative />
    </span>
  </small>
) : null);

export default LastUpdated;
