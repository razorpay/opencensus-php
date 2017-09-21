import React from 'react';

export default ({ title, symbol, children }) => {
  return (
    <div className="media">
      <div className="media-left">
        <img className="media-object" src={symbol} />
      </div>
      <div className="media-body">
        <div className="media-heading">
          {title}
        </div>
        {children}
      </div>
    </div>
  );
};
