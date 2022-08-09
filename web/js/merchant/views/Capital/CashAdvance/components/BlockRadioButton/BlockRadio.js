import './style.styl';
import { classList } from 'common/utils/rzp-utils';

const BlockRadio = ({ value, label, checked, handleClick, disabled }) => {
  const blockHandleClick = () => {
    if (!disabled) handleClick(value);
  };
  return (
    <div
      className={classList(
        'block-radio-container',
        checked && 'block-radio-container--active',
        disabled && 'block-radio-container--disable',
      )}
      onClick={blockHandleClick}
    >
      <span
        className={classList(
          'block-radio',
          checked && 'block-radio--checked',
          disabled && 'block-radio--disable',
        )}
      />
      {label}
    </div>
  );
};

export default BlockRadio;
