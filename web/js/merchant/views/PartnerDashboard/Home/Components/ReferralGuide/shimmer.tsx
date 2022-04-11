import React from 'react';

const getShimmerLines = (lineCount: number): JSX.Element => {
  return (
    <div className="side-space">
      {new Array(lineCount).fill(null).map((_, idx) => (
        <span className="shimmer-loader" key={idx} />
      ))}
    </div>
  );
};

const RecommendationWidgetShimmer = (): JSX.Element => {
  return (
    <div className="recommendation-shimmer">
      <div className="primary-shimmer">
        <span className="shimmer-loader primary-icon" />

        {getShimmerLines(4)}
      </div>
      <div className="secondary-shimmer">
        <div className="more-content">
          <span className="shimmer-loader icon" />
          {getShimmerLines(2)}
        </div>
        <div className="more-content sec-content">
          <span className="shimmer-loader icon" />
          {getShimmerLines(2)}
        </div>
      </div>
    </div>
  );
};

export default RecommendationWidgetShimmer;
