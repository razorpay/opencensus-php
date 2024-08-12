import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { renderHook } from 'test-utils';

import { useReconTracking } from './../hooks.ts';

jest.mock('common/utils/analytics');

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
    jest.useFakeTimers();
    const { unmount } = renderHook(() => useReconTracking({ objectName, screen, properties }));
    unmount();
    expect(clearTimeout).toHaveBeenCalled();
    jest.useRealTimers();
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
