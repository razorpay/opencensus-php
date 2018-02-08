import React, { Component } from 'react';
import Time from 'rzp/ui/Time';

const LastUpdated = ({at}) => (
  <small>
    <i className="i i-info-circle" />&nbsp;
    <span>
      The graph data last updated {
        at ? (<Time value={at} relative />) : "--"
      }
    </span>
  </small>
);

export default LastUpdated;
