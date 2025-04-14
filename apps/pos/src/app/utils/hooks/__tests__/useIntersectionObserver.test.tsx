import { renderHook, act } from '@testing-library/react-hooks';
import useIntersectionObserver from '../useIntersectionObserver';

describe('useIntersectionObserver', () => {
  let callback: jest.Mock;
  let observe: jest.Mock;
  let disconnect: jest.Mock;

  beforeEach(() => {
    callback = jest.fn();
    observe = jest.fn();
    disconnect = jest.fn();

    // Mock IntersectionObserver
    global.IntersectionObserver = jest.fn(function (this: IntersectionObserver, callback) {
      this.observe = observe;
      this.disconnect = disconnect;
      this.callback = callback;
    }) as unknown as jest.Mock;
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  it('should call the callback when the target element intersects', () => {
    const { result } = renderHook(() => useIntersectionObserver({ callback }));

    const targetElement = document.createElement('div');
    act(() => {
      result.current(targetElement);
    });

    const observer = (IntersectionObserver as jest.Mock).mock.instances[0];
    const entries = [{ isIntersecting: true }];
    act(() => {
      observer.callback(entries);
    });

    expect(callback).toHaveBeenCalled();
  });

  it('should disconnect the observer when the component unmounts', () => {
    const { result, unmount } = renderHook(() => useIntersectionObserver({ callback }));

    const targetElement = document.createElement('div');
    act(() => {
      result.current(targetElement);
    });

    const observer = (IntersectionObserver as jest.Mock).mock.instances[0];
    unmount();

    expect(observer.disconnect).toHaveBeenCalled();
  });

  it('should not create an observer when targetRef is null', () => {
    renderHook(() => useIntersectionObserver({ callback }));
    expect(IntersectionObserver).not.toHaveBeenCalled();
  });
});