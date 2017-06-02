export const ProgressBar = ({ value, min, max, type, className }) => {
  let completionPercentage = `${100 / (max - min) * value}%`;

  return (
    <div class={`progress ${className}`}>
      <div
        class={`progress-bar progress-bar-${type}`}
        style={{ width: completionPercentage }}
      >
        <span class="sr-only">{completionPercentage} Complete ({type})</span>
      </div>
    </div>
  );
};

ProgressBar.defaultProps = {
  min: 0,
  type: 'success',
  className: '',
};

export default ProgressBar;
