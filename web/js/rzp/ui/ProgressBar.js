export const ProgressBar = ({ value, min, max, type, className, color }) => {
  let completionPercentage = `${100 / (max - min) * value}%`;

  const style = {
    width: completionPercentage,
    ...(!!color && { backgroundColor: color }),
  };

  return (
    <div class={`progress ${className}`}>
      <div class={`progress-bar progress-bar-${type}`} style={style}>
        <span class="sr-only">
          {completionPercentage} Complete ({type})
        </span>
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
