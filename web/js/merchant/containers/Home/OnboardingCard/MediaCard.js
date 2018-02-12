import React from 'react';

export default ({ title, symbol, children }) => {
  return (
    <div className="media">
      <div className="media-left">
        <div className={`media-object ${symbol}`} />
      </div>
      <div className="media-body">
        <div className="media-heading">{title}</div>
        {children}
      </div>
    </div>
  );
};
