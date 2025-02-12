import React from 'react';

const MultiLevelStepper = ({ loading, children }) => {
  const classNames = ['multilevel-stepper', ...(loading ? ['loading'] : [])];
  return <ul className={classNames.join(' ')}>{children}</ul>;
};

const ParentStep = ({
  isExpanded,
  highlight,
  status,
  title,
  description,
  action,
  onClick,
  disabled = false,
}) => {
  const classNames = [
    'multilevel-step',
    'parent-step',
    status,
    ...(highlight ? ['highlight'] : []),
    ...(isExpanded ? ['expanded'] : []),
  ];
  return (
    <li
      className={classNames.join(' ')}
      onClick={() => {
        if (!disabled && onClick) {
          onClick();
        }
      }}
    >
      <div className="multilevel-step__wrapper">
        <span className="multilevel-step__status">
          {status.includes('completed') && (
            <i className="i i-check" style={{ fontSize: 10 }} />
          )}
          {status.includes('pending') && (
            <img
              src={require("assets/capital/pending.svg")}
              style={{ height: 32 }}
            />
          )}
          {status.includes('error') && (
            <i
              className="i i-info-circle"
              style={{ fontSize: 20, color: '#EE6619' }}
            />
          )}
        </span>
        <div className="multilevel-step__info_container">
          <p className="title">{title}</p>
          <p className="description">{description}</p>
          {action}
        </div>
      </div>
    </li>
  );
};

const Step = ({ status, title, onClick, disabled }) => {
  const classNames = ['multilevel-step', 'step', status];
  return (
    <li
      className={classNames.join(' ')}
      onClick={() => {
        if (!disabled && onClick) {
          onClick();
        }
      }}
    >
      <div className="multilevel-step__wrapper">
        <span className="multilevel-step__status">
          {status.includes('completed') && (
            //move these to stylesheet
            <i className="i i-check" style={{ fontSize: 10 }} />
          )}
          {status.includes('pending') && (
            <img
              src={require("assets/capital/pending.svg")}
              style={{ height: 16, marginTop: -4 }}
            />
          )}
          {status.includes('error') && (
            <i
              className="i i-info-circle"
              style={{ fontSize: 14, color: '#EE6619' }}
            />
          )}
        </span>
        <div className="multilevel-step__info_container">
          <p className="title">{title}</p>
        </div>
      </div>
    </li>
  );
};

MultiLevelStepper.ParentStep = ParentStep;
MultiLevelStepper.Step = Step;

export default MultiLevelStepper;
