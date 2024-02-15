import { renderHook, act } from '@testing-library/react-hooks';

import { useCountDownTimer } from 'merchant/views/PartnerDashboard/SubMerchant/components/useCountDownTimer';

// Mock the timer functions
jest.useFakeTimers('modern');

// todo skipping this test cases because jest advanceTimersByTime is not working.
describe.skip('CountDownTimer', () => {
  test('should start the timer', () => {
    const onTimeOutMock = jest.fn();

    const { result } = renderHook(() => useCountDownTimer(10, onTimeOutMock));

    act(() => {
      result.current.startTimer();
    });

    jest.advanceTimersByTime(1000);

    expect(result.current.remainingSeconds).toBe(9);

    jest.advanceTimersByTime(9000);

    expect(result.current.remainingSeconds).toBe(1);
  });

  test('should stop the timer', () => {
    const onTimeOutMock = jest.fn();

    const { result } = renderHook(() => useCountDownTimer(10, onTimeOutMock));

    act(() => {
      result.current.startTimer();
    });

    jest.advanceTimersByTime(5000);

    expect(result.current.remainingSeconds).toBe(5);

    act(() => {
      result.current.stopTimer();
    });

    jest.advanceTimersByTime(5000);

    expect(result.current.remainingSeconds).toBe(1);
  });
});
