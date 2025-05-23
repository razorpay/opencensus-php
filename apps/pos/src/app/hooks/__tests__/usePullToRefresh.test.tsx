import React, { useRef, useEffect } from 'react';
import { render, fireEvent } from '@testing-library/react';
import { usePullToRefresh } from '../usePullToRefresh';

describe('usePullToRefresh', () => {
  let reloadSpy: jest.Mock;

  beforeAll(() => {
    reloadSpy = jest.fn();
    Object.defineProperty(window, 'location', {
      configurable: true,
      value: {
        ...window.location,
        reload: reloadSpy,
      },
    });
  });

  afterAll(() => {
    delete (window.location as any).reload;
  });

  const TestComponent: React.FC<{ threshold?: number }> = ({ threshold }) => {
    const containerRef = useRef<HTMLDivElement>(null);
    const pullRef = useRef<HTMLDivElement>(null);
    const { refreshing, setupPullToRefresh } = usePullToRefresh(threshold);

    useEffect(() => {
      const cleanup = setupPullToRefresh(containerRef.current, pullRef.current);
      return cleanup;
    }, [setupPullToRefresh]);

    return (
      <div>
        <div ref={containerRef} data-testid="container">
          <div ref={pullRef} data-testid="pull" style={{ top: '0px' }} />
        </div>
        <span data-testid="refreshing">{refreshing ? 'yes' : 'no'}</span>
      </div>
    );
  };

  test('should return default refreshing state as false', () => {
    const { getByTestId } = render(<TestComponent />);
    expect(getByTestId('refreshing').textContent).toBe('no');
  });

  test('should early return if container or pull element is null', () => {
    const TestWrapper: React.FC<{
      onSetup: (fn: ReturnType<typeof usePullToRefresh>['setupPullToRefresh']) => void;
    }> = ({ onSetup }) => {
      const { setupPullToRefresh } = usePullToRefresh();

      useEffect(() => {
        onSetup(setupPullToRefresh);
      }, [setupPullToRefresh]);

      return null;
    };

    let setupFn: ReturnType<typeof usePullToRefresh>['setupPullToRefresh'] = () => () => {};
    render(<TestWrapper onSetup={(fn) => (setupFn = fn)} />);
    expect(() => {
      setupFn(null, null);
    }).not.toThrow();
  });

  test('should set refreshing to true and call reload on pull', () => {
    const { getByTestId } = render(<TestComponent threshold={10} />);
    const container = getByTestId('container');

    // Simulate scrollY = 0
    Object.defineProperty(window, 'scrollY', { value: 0, writable: true });

    // Touch start
    fireEvent.touchStart(container, { touches: [{ clientY: 0 }] });
    // Touch move (simulate pull > threshold)
    fireEvent.touchMove(container, { touches: [{ clientY: 20 }] });
    // Touch end
    fireEvent.touchEnd(container);

    expect(reloadSpy).toHaveBeenCalled();
    expect(getByTestId('refreshing').textContent).toBe('yes');
  });

  test('should not refresh if pull is less than threshold', () => {
    const { getByTestId } = render(<TestComponent threshold={100} />);
    const container = getByTestId('container');

    Object.defineProperty(window, 'scrollY', { value: 0, writable: true });

    fireEvent.touchStart(container, { touches: [{ clientY: 0 }] });
    fireEvent.touchMove(container, { touches: [{ clientY: 20 }] });
    fireEvent.touchEnd(container);

    expect(reloadSpy).not.toHaveBeenCalled();
    expect(getByTestId('refreshing').textContent).toBe('no');
  });

  test('should cleanup event listeners on unmount', () => {
    const { unmount, getByTestId } = render(<TestComponent />);
    const container = getByTestId('container');
    const removeEventListenerSpy = jest.spyOn(container, 'removeEventListener');
    unmount();
    expect(removeEventListenerSpy).toHaveBeenCalled();
  });
});
