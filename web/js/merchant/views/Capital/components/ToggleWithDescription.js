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
      <div class="flex">
        <p class="toggle-title">{title}</p>
        <input
          type="radio"
          class="radio-pointer"
          name={name}
          checked={selected}
        />
      </div>
      <p class="toggle-description">{description}</p>
    </div>
  );
};

export default ToggleWithDescription;
