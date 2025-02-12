import React from 'react';

export default class CircularProgressBar extends React.Component {
  constructor(props) {
    super(props);
  }
  render() {
    const {
      size,
      showPercentage = true,
      strokeWidth = 4,
      progress,
      helpMsg,
    } = this.props;

    const radius = (size - strokeWidth) / 2;
    // Enclose circle in a circumscribing square
    const viewBox = `0 0 ${size} ${size}`;
    // Arc length at 100% coverage is the circle circumference
    const dashArray =
      size > 50
        ? radius * Math.PI * 2
        : progress > 20
          ? progress === 100 ? radius * Math.PI * 2 : radius * Math.PI * 2 - 4
          : radius * Math.PI * 2;
    // Scale 100% coverage overlay with the actual percent
    const dashOffset = dashArray - dashArray * progress / 100;

    return (
      <div
        className="circular-progress"
        style={{
          height: size,
          width: size,
        }}
      >
        <svg
          width={size}
          height={size}
          viewBox={viewBox}
          aria-labelledby="title"
          role="graphic"
        >
          <circle
            cx={size / 2}
            cy={size / 2}
            r={radius}
            strokeWidth={`${strokeWidth - 2}px`}
          />
          <circle
            cx={size / 2}
            cy={size / 2}
            r={radius}
            style={{
              strokeDasharray: dashArray,
              strokeDashoffset: dashOffset,
            }}
            strokeWidth={`${strokeWidth}px`}
          />
        </svg>
        <div className="percentage-info">
          {showPercentage && (
            <p className="percentage-text">{`${progress}%`}</p>
          )}
          {helpMsg && <p className="description">{helpMsg}</p>}
        </div>
      </div>
    );
  }
}
