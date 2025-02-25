import React from 'react';
import ProgressBarContext from './ProgressBarContext';

const useProgressBar = () => {
  const context = React.useContext(ProgressBarContext);

  if (context === undefined) {
    throw new Error('useProgressBar must be used within ProgressBarProvider');
  }

  return context;
};

export default useProgressBar;
