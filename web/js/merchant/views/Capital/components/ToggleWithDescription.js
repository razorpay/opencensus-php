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
}) => {
  return (
    <div
      onClick={() => {
        if (!disabled) onClick();
      }}
      class={`toggle-with-description ${disabled ? 'disabled' : ''} ${size}`}
      role="button"
      style={{
        ...style,
      }}
    >
      <div class="title-content-wrapper flex">
        <div class="toggle-title flex">
          <p class="">{title}</p>
          {hint && <span class="text-faded">&nbsp;{hint}</span>}
        </div>
        {(() => {
          if (!showRadioInput) return null;

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
