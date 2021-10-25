import React from 'react';

const getStatusText = (status, downMethods) => {
  let text, image;
  switch (status) {
    case 'operational':
      image = 'green-tick.svg';
      text = 'All methods are operational';
      break;
    case 'fewDrops':
      image = 'yellow-status.svg';
      text = `Few drops noticed in ${downMethods.toString()}`;
      break;
    case 'majorDrops':
      image = 'orange-status.svg';
      text = `Major drops noticed in ${downMethods.toString()}`;
      break;
    case 'severeDrop':
      image = 'red-status.svg';
      text = `Major drops noticed in ${downMethods.toString()}`;
      break;
    default:
      image = '';
      text = 'Sorry! We ran into an error';
  }
  return { text, image };
};

const OverallStatus = (props) => {
  const { status, downMethods } = props;
  const { text, image } = getStatusText(status, downMethods);
  return (
    <>
      <img
        src={`${window.cdnBaseUrl}/static/assets/downtimes/${image}`}
        className="main-tick"
        alt="Overall Status"
      />
      <div className="main-status-text">{text}</div>
    </>
  );
};

export default OverallStatus;
