import React from 'react';

const getTimerText = (timerSeconds) => {
  const minutes = Math.floor(timerSeconds / 60);
  let seconds = timerSeconds >= 60 ? timerSeconds - 60 : timerSeconds;
  if (seconds < 10) {
    seconds = `0${seconds}`;
  }
  return `${minutes}:${seconds}`;
};

const useOTPCountdownTimer = (initialTimerSeconds = 120) => {
  const initialTimerText = getTimerText(initialTimerSeconds);
  const [timerText, setTimerText] = React.useState(initialTimerText);
  const [isTimerRunning, setIsTimerRunning] = React.useState(true);
  const resendOTPTimerInterval = React.useRef(null);

  const setTimer = () => {
    let timerSeconds = initialTimerSeconds;
    resendOTPTimerInterval.current = setInterval(() => {
      timerSeconds--;
      if (timerSeconds <= 0) {
        setIsTimerRunning(false);
        clearInterval(resendOTPTimerInterval.current);
      }
      const nextTimerText = getTimerText(timerSeconds);
      setTimerText(nextTimerText);
    }, 1000);

    return resendOTPTimerInterval.current;
  };

  const resetTimer = () => {
    setTimerText(initialTimerText);
    setIsTimerRunning(true);
    resendOTPTimerInterval.current = setTimer();
    return resendOTPTimerInterval.current;
  };

  React.useEffect(() => {
    resendOTPTimerInterval.current = setTimer();
    return () => {
      if (resendOTPTimerInterval.current) {
        clearInterval(resendOTPTimerInterval.current);
      }
    };
  }, []);

  return { timerText, isTimerRunning, resetTimer };
};

export default useOTPCountdownTimer;
