import React from 'react';

import './styles.styl';

export default ({ children }) => {
  return (
    <blockquote className="rzp-blockquote">
      {children}
    </blockquote>
  );
};
