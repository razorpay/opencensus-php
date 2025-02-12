import React from 'react';

const DescriptionWrapper = ({ description, meta, expanded, selected }) => {
  if ((selected && meta) || expanded) return meta;

  return <p className="toggle-description">{description}</p>;
};

const HintWrapper = ({ hint }) => {
  if (hint && typeof hint === 'string') return <span className="text-faded">&nbsp;{hint}</span>;

  return hint || null;
};

const ToggleWithDescription = ({
  title,
  description,
  onClick,
  selected,
  style,
  name,
  hint,
  disabled = false,
  loading = false,
  showRadioInput = true,
  expanded = false,
  size = 'large',
  radioPosition = 'right',
  className = '',
  icon = '',
  meta,
}) => {
  return (
    <div
      onClick={() => {
        if (!disabled && !selected) onClick();
      }}
      className={`toggle-with-description ${disabled ? 'disabled' : ''} ${
        selected ? 'selected' : ''
      } ${size} ${className}`}
      role="button"
      style={{
        ...style,
      }}
    >
      <div className="title-content-wrapper flex">
        <div className="toggle-title flex">
          {icon ? (
            <span>
              <i className={icon} />
            </span>
          ) : radioPosition === 'left' ? (
            <input type="radio" className="radio-pointer" name={name} checked={selected} />
          ) : null}
          {typeof title === 'string' ? (
            <p className={`${selected ? 'selected' : ''} ${radioPosition === 'left' ? 'm-l' : ''}`}>
              {title}
            </p>
          ) : (
            title
          )}
          <HintWrapper hint={hint} />
        </div>
        {(() => {
          if (!showRadioInput || radioPosition !== 'right') return null;

          if (loading) {
            return (
              <div className="loader-ring">
                <div />
                <div />
                <div />
                <div />
              </div>
            );
          } else {
            return <input type="radio" className="radio-pointer" name={name} checked={selected} />;
          }
        })()}
      </div>
      <DescriptionWrapper
        description={description}
        meta={meta}
        selected={selected}
        expanded={expanded}
      />
    </div>
  );
};

export default ToggleWithDescription;
