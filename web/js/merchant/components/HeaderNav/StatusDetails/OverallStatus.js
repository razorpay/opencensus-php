import React from 'react';

const getDownMethodsDescription = (downMethods) => {
  if (downMethods.length === 1) {
    return <span>{downMethods[0]}</span>;
  } else if (downMethods.length === 2) {
    return <span>{`${downMethods[0]} and ${downMethods[1]}`}</span>;
  } else if (downMethods.length === 3) {
    return <span>{`${downMethods[0]}, ${downMethods[1]} and ${downMethods[2]}`}</span>;
  } else {
    return <span>All methods are operational</span>;
  }
};

const getStatusText = (status, downMethods) => {
  let text, image;
  switch (status) {
    case 'operational':
      image = 'green-tick.svg';
      text = 'All methods are operational';
      break;
    case 'fewDrops':
      image = 'yellow-status.svg';
      text = `Few drops noticed in ${getDownMethodsDescription(downMethods)}`;
      break;
    case 'majorDrops':
      image = 'orange-status.svg';
      text = `Major drops noticed in ${getDownMethodsDescription(downMethods)}`;
      break;
    case 'severeDrop':
      image = 'red-status.svg';
      text = `Major drops noticed in ${getDownMethodsDescription(downMethods)}`;
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
      <img src={`${window.cdnBaseUrl}/static/assets/downtimes/${image}`} className="main-tick" />
      <div className="main-status-text">{text}</div>
    </>
  );
};

export default OverallStatus;
