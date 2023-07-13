import { useIdleTimer } from 'react-idle-timer';
import PropTypes from 'prop-types';

const IdleTimer = ({ timeoutInMillisecond, onIdle }) => {
  useIdleTimer({
    onIdle,
    timeout: timeoutInMillisecond,
  });
  return null;
};

IdleTimer.propTypes = {
  timeoutInMillisecond: PropTypes.number,
  onIdle: PropTypes.func,
};

export default IdleTimer;
