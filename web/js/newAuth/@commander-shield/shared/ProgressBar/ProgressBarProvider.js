import React, { useState, useMemo } from 'react';
import PropTypes from 'prop-types';
import ProgressBarContext from './ProgressBarContext';
import ProgressBar from './ProgressBar';

const ProgressBarProvider = ({ children }) => {
  const [percent, setPercent] = useState(0);
  const value = useMemo(
    () => ({
      percent,
      setPercent,
    }),
    [percent],
  );

  return (
    <ProgressBarContext.Provider value={value}>
      <ProgressBar percent={percent} />
      {children}
    </ProgressBarContext.Provider>
  );
};

ProgressBarProvider.propTypes = {
  children: PropTypes.node,
};

export default ProgressBarProvider;
