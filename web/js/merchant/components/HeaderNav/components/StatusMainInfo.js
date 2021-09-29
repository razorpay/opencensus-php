import greenTick from '../../../../../icons/merchant/greenTick.svg';
import yellowStatus from '../../../../../icons/merchant/yellowStatus.svg';
import orangeStatus from '../../../../../icons/merchant/orangeStatus.svg';
import redStatus from '../../../../../icons/merchant/redStatus.svg';

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

  return props.overallStatus === 'operational' ? (
    <>
      <img src={greenTick} className="main-tick" />
      <div className="main-status-text">All methods are operational</div>
    </>
  ) : props.overallStatus === 'fewDrops' ? (
    <>
      <img src={yellowStatus} className="main-tick" />
      <div className="main-status-text">
        Few drops noticed in {'  '}
        {getMethodsDownText(props.methodsDown)}
      </div>
    </>
  ) : (
    <>
      {props.overallStatus === 'majorDrops' ? (
        <img src={orangeStatus} className="main-tick" />
      ) : (
        props.overallStatus === 'severeDrop' && <img src={redStatus} className="main-tick" />
      )}
      <div className="main-status-text">
        Major drops noticed in {'  '}
        {getMethodsDownText(props.methodsDown)}
      </div>
    </>
  );
};

export default StatusMainInfo;
