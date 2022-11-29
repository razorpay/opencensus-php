import React from 'react';

export type StepProps = {
  step: number;
  children: React.ReactNode;
  borderBottom: boolean;
};

const Step = ({ step, children, borderBottom }: StepProps): JSX.Element => {
  return (
    <>
      <div className="keys-plugins-step">
        <div className="keys-plugins-step__number">{step}</div>
        <div className="keys-plugins-step__container">{children}</div>
      </div>
      {borderBottom ? <hr /> : null}
    </>
  );
};

export default Step;
