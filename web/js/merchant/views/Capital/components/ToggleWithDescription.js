import React from 'react';

const DescriptionWrapper = ({ description, meta, selected }) => {
  if (selected && meta) return meta;

  return <p class="toggle-description">{description}</p>;
};

const HintWrapper = ({ hint }) => {
  if (hint && typeof hint === 'string') return <span class="text-faded">&nbsp;{hint}</span>;

  return hint || null;
};

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
  meta,
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
      <DescriptionWrapper description={description} meta={meta} selected={selected} />
    </div>
  );
};

export default ToggleWithDescription;
