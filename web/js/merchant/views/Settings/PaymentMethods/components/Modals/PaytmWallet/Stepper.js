import React from 'react';

export const Stepper = ({ activeStep }) => {
  return (
    <div className="stepper">
      {[1, 2, 3].map((item) => {
        return <div className={activeStep === item ? 'active' : 'inactive'} key={item} />;
      })}
    </div>
  );
};
