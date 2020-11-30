import React from 'react';

const ToggleWithDescription = ({
  title,
  description,
  onClick,
  selected,
  size = 'large',
  style,
  name,
  disabled = false,
  hint,
  loading = false,
  showRadioInput = true,
  radioPosition = 'right',
}) => {
  return (
    <div
      onClick={() => {
        if (!disabled && !selected) onClick();
      }}
      class={`toggle-with-description ${disabled ? 'disabled' : ''} ${
        selected ? 'selected' : ''
      } ${size}`}
      role="button"
      style={{
        ...style,
      }}
    >
      <div class="title-content-wrapper flex">
        <div class="toggle-title flex">
          {radioPosition === 'left' && (
            <input type="radio" className="radio-pointer" name={name} checked={selected} />
          )}
          {typeof title === 'string' ? (
            <p className={radioPosition === 'left' ? 'm-l' : ''}>{title}</p>
          ) : (
            title
          )}
          {hint && <span class="text-faded">&nbsp;{hint}</span>}
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
      <p class="toggle-description">{description}</p>
    </div>
  );
};

export default ToggleWithDescription;
