import React from 'react';

export default ({ title, value, children, className }) => (
  <div className={`stats-card ${className || ''}`}>
    <div className="title">{title}</div>
    <div className="value">{value ? <h1>{value}</h1> : children}</div>
  </div>
);
