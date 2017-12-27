import React from 'react';

import './styles.styl';

const Overlay = ({ children }) => (
  <div className="rzp-overlay">
    <div className="overlay-inner">
      <div className="overlay-content">{children}</div>
    </div>
  </div>
);

export default Overlay;
