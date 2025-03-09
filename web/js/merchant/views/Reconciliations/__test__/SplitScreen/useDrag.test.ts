import { fireEvent } from '@testing-library/dom';
import { renderHook, act } from '@testing-library/react-hooks';

import { useDrag } from 'merchant/views/Reconciliations/SplitScreen/usedrag';

jest.mock('lodash', () => ({
  throttle: (fn) => {
    const throttledFn = (...args) => fn(...args);
    throttledFn.cancel = () => {};
    return throttledFn;
  },
}));

describe('useDrag', () => {
  beforeAll(() => {
    window.innerWidth = 1000;
  });

  it('should start with initial width', () => {
    const { result } = renderHook(() =>
      useDrag({
        throttleDelay: 100,
        initialWidth: 40,
      }),
    );

    expect(result.current.dynamicWidth).toBe(40);
    expect(result.current.isDragging).toBe(false);
  });

  it('should update width on mouse move', () => {
    const { result } = renderHook(() => useDrag({ throttleDelay: 100 }));

    act(() => {
      result.current.handleDragMouseDown({
        preventDefault: jest.fn(),
        clientX: 500,
      } as unknown as React.MouseEvent);
    });

    act(() => {
      fireEvent.mouseMove(window, { clientX: 600 });
    });

    expect(result.current.dynamicWidth).toBe(60);
  });

  it('should respect min and max constraints', () => {
    const { result } = renderHook(() =>
      useDrag({
        throttleDelay: 100,
        minWidth: 20,
        maxWidth: 80,
      }),
    );

    act(() => {
      result.current.handleDragMouseDown({
        preventDefault: jest.fn(),
        clientX: 500,
      } as unknown as React.MouseEvent);
    });

    act(() => {
      fireEvent.mouseMove(window, { clientX: 900 });
    });
    expect(result.current.dynamicWidth).toBe(80);

    act(() => {
      fireEvent.mouseMove(window, { clientX: 100 });
    });
    expect(result.current.dynamicWidth).toBe(20);
  });

  it('should stop dragging on mouse up', () => {
    const { result } = renderHook(() => useDrag({ throttleDelay: 100 }));

    act(() => {
      result.current.handleDragMouseDown({
        preventDefault: jest.fn(),
        clientX: 500,
      } as unknown as React.MouseEvent);
    });

    act(() => {
      fireEvent.mouseUp(window);
    });

    expect(result.current.isDragging).toBe(false);
  });
});
