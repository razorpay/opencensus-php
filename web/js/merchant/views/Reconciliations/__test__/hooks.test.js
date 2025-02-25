import { useRef } from 'react';
import { useToast } from '@razorpay/blade/components';
import { waitFor } from '@testing-library/react';
import { renderHook, act } from '@testing-library/react-hooks';
import moment from 'moment';

import { analyticsTrackWithUserInfo } from 'common/utils/analytics';

import { useReconTracking, useScrollPosition, useCalendarRange } from './../hooks.ts';

jest.mock('@razorpay/blade/components', () => ({
  useToast: jest.fn(),
}));
jest.mock('common/utils/analytics');

const mockShow = jest.fn();
useToast.mockReturnValue({ show: mockShow });

describe('useReconTracking customHook', () => {
  const objectName = 'recon cta click';
  const screen = 'recon dashboard';
  const properties = { key: 'value' };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should track view event on mount', () => {
    renderHook(() => useReconTracking({ objectName, screen, properties }));

    expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith({
      objectName,
      actionName: 'view',
      screen,
      properties,
    });
  });

  test('should track read success event after 15 seconds', async () => {
    renderHook(() => useReconTracking({ objectName, screen, properties, readDelay: 0 }));
    await new Promise((resolve) => setTimeout(resolve, 200));

    expect(analyticsTrackWithUserInfo).toHaveBeenCalledTimes(2);
    expect(analyticsTrackWithUserInfo).toHaveBeenNthCalledWith(1, {
      objectName,
      actionName: 'view',
      screen,
      properties,
    });
    expect(analyticsTrackWithUserInfo).toHaveBeenNthCalledWith(2, {
      objectName,
      actionName: 'read success',
      screen,
      properties,
    });
  });

  test('should clear timeout on unmount', () => {
    window.clearTimeout = jest.fn();
    const { unmount } = renderHook(() => useReconTracking({ objectName, screen, properties }));
    unmount();
    expect(clearTimeout).toHaveBeenCalled();
  });

  it('should use default properties when properties are not provided', () => {
    renderHook(() => useReconTracking({ objectName, screen }));

    expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith({
      objectName,
      actionName: 'view',
      screen,
      properties: {},
    });
  });
});

describe('useCalendarRange', () => {
  it('should initialize with the correct date range', () => {
    const { result } = renderHook(() => useCalendarRange());
    const { dateRange } = result.current;

    expect(dateRange.startDate.isSame(moment().subtract(7, 'days').startOf('day'))).toBe(true);
    expect(dateRange.endDate.isSame(moment().endOf('day'))).toBe(true);
  });

  it('should update the date range when valid dates are provided', () => {
    const { result } = renderHook(() => useCalendarRange());
    const { handleRangeChange } = result.current;

    const newStartDate = moment().subtract(5, 'days').startOf('day');
    const newEndDate = moment().subtract(1, 'days').endOf('day');

    act(() => {
      handleRangeChange({ startDate: newStartDate, endDate: newEndDate });
    });

    const { dateRange } = result.current;
    expect(dateRange.startDate.isSame(newStartDate)).toBe(true);
    expect(dateRange.endDate.isSame(newEndDate)).toBe(true);
  });

  it('should show a toast message when the date range exceeds 7 days', () => {
    const { result } = renderHook(() => useCalendarRange());
    const { handleRangeChange } = result.current;

    const newStartDate = moment().subtract(10, 'days').startOf('day');
    const newEndDate = moment().endOf('day');

    act(() => {
      handleRangeChange({ startDate: newStartDate, endDate: newEndDate });
    });

    expect(mockShow).toHaveBeenCalledWith({
      content: 'Currently max range allowed is 7 days. Please try again with shorter range of days',
      color: 'notice',
      autoDismiss: true,
    });
  });
});

describe('useScrollPosition', () => {
  test('should return the initial scroll position as {scrollLeft: 0, scrollTop: 0}', () => {
    const { result } = renderHook(() => {
      const ref = useRef(document.createElement('div'));
      return useScrollPosition(ref);
    });

    expect(result.current).toEqual({ scrollLeft: 0, scrollTop: 0 });
  });

  test('should update scroll position when scrolling occurs', async () => {
    const requestAnimationFrameSpy = jest
      .spyOn(window, 'requestAnimationFrame')
      .mockImplementation((callback) => callback());

    const divElement = document.createElement('div');
    divElement.style.overflow = 'scroll';
    divElement.style.width = '100px';
    divElement.style.height = '100px';

    const content = document.createElement('div');
    content.style.width = '200px';
    content.style.height = '200px';
    divElement.appendChild(content);

    const { result } = renderHook(() => {
      const ref = useRef(divElement);
      return { scrollPosition: useScrollPosition(ref), ref };
    });

    const { ref } = result.current;

    await act(() => {
      ref.current.scrollLeft = 100;
      ref.current.scrollTop = 200;
      ref.current.dispatchEvent(new Event('scroll'));
    });

    await waitFor(() => {
      expect(result.current.scrollPosition).toEqual({
        scrollLeft: 100,
        scrollTop: 200,
      });
    });

    requestAnimationFrameSpy.mockRestore();
  });

  test('should clean up the scroll event listener on unmount', () => {
    const divElement = document.createElement('div');

    const addEventListenerSpy = jest.spyOn(divElement, 'addEventListener');
    const removeEventListenerSpy = jest.spyOn(divElement, 'removeEventListener');
    const { unmount } = renderHook(() => {
      const ref = useRef(divElement);
      return { scrollPosition: useScrollPosition(ref), ref };
    });

    expect(addEventListenerSpy).toHaveBeenCalledWith('scroll', expect.any(Function));
    unmount();
    expect(removeEventListenerSpy).toHaveBeenCalledWith('scroll', expect.any(Function));
  });
});
