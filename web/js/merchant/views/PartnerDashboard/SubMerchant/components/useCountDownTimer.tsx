import { useState, useEffect } from 'react';

type useCounterResponse = {
  remainingSeconds: number;
  startTimer: () => void;
  stopTimer: () => void;
};
export const useCountDownTimer = (
  initialSeconds: number,
  onTimeOut: () => void,
): useCounterResponse => {
  const [remainingSeconds, setRemainingSeconds] = useState(initialSeconds);
  const [isRunning, setIsRunning] = useState(false);
  const startTimer = () => {
    setIsRunning(true);
  };

  const stopTimer = () => {
    setIsRunning(false);
  };

  useEffect(() => {
    let timer;

    if (isRunning && remainingSeconds > 0) {
      timer = setInterval(() => {
        setRemainingSeconds((prevSeconds) => prevSeconds - 1);
      }, 1000);
    } else if (remainingSeconds === 0) {
      clearInterval(timer);
      onTimeOut(); // Callback when the timer reaches 0
      setIsRunning(false);
    }

    return () => {
      clearInterval(timer);
    };
  }, [remainingSeconds, isRunning, onTimeOut]);

  return { remainingSeconds, startTimer, stopTimer };
};
