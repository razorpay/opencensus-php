const StatusMainInfo = (props) => {
  const getMethodsDownText = (methodsDown) => {
    if (methodsDown.length === 1) {
      return <span>{methodsDown[0]}</span>;
    } else if (methodsDown.length === 2) {
      return <span>{`${methodsDown[0]} and ${methodsDown[1]}`}</span>;
    } else if (methodsDown.length === 3) {
      return <span>{`${methodsDown[0]}, ${methodsDown[1]} and ${methodsDown[2]}`}</span>;
    } else {
      return <span>All methods are operational</span>;
    }
  };

  if (props.overallStatus === 'operational') {
    return (
      <>
        <img
          src={`${window.cdnBaseUrl}/static/assets/downtimes/green-tick.svg`}
          className="main-tick"
        />
        <div className="main-status-text">All methods are operational</div>
      </>
    );
  } else if (props.overallStatus === 'fewDrops') {
    return (
      <>
        <img
          src={`${window.cdnBaseUrl}/static/assets/downtimes/yellow-status.svg`}
          className="main-tick"
        />
        <div className="main-status-text">
          Few drops noticed in {'  '}
          {getMethodsDownText(props.methodsDown)}
        </div>
      </>
    );
  } else if (props.overallStatus === 'majorDrops') {
    return (
      <>
        <img
          src={`${window.cdnBaseUrl}/static/assets/downtimes/orange-status.svg`}
          className="main-tick"
        />
        <div className="main-status-text">
          Major drops noticed in {'  '}
          {getMethodsDownText(props.methodsDown)}
        </div>
      </>
    );
  } else if (props.overallStatus === 'severeDrop') {
    return (
      <>
        <img
          src={`${window.cdnBaseUrl}/static/assets/downtimes/red-status.svg`}
          className="main-tick"
        />
        <div className="main-status-text">
          Major drops noticed in {'  '}
          {getMethodsDownText(props.methodsDown)}
        </div>
      </>
    );
  } else {
    return <div>Sorry! We ran into an error</div>;
  }
};

export default StatusMainInfo;
