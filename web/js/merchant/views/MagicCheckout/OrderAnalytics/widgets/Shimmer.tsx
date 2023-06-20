import React from 'react';

const Shimmer = ({
  height = 50,
  width = 150,
}: {
  height: number | string;
  width: number | string;
}): JSX.Element => {
  return <div className="shimmer-widget" style={{ height, width }} />;
};

export default Shimmer;
