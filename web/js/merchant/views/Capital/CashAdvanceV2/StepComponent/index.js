import React from 'react';
import './Step.styl';

const StepComponent = (props) => {
  const { totalSteps = 0, currentStep = 0 } = props;
  const totalStepArray = new Array(totalSteps).fill(1);

  return (
    <div className="step-wrapper">
      <div className="step-div">
        STEP {currentStep}/{totalSteps}
      </div>
      <div className="particular-step-wrapper">
        {totalStepArray.map((item, index) => {
          const isActive = index + 1 <= currentStep;
          return (
            <div key={index} className={`particular-step ${isActive ? 'active' : 'inactive'}`} />
          );
        })}
      </div>
    </div>
  );
};

export default StepComponent;
