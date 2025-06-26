import * as track from '../track';

describe('track.ts', () => {
  const analyticsTrackWithUserInfo = jest.fn();
  beforeAll(() => {
    jest.spyOn(require('common/utils/analytics'), 'analyticsTrackWithUserInfo').mockImplementation(analyticsTrackWithUserInfo);
  });
  afterEach(() => {
    analyticsTrackWithUserInfo.mockClear();
  });

  it('trackProductVersioningWidgetLoaded calls analyticsTrackWithUserInfo with correct args', () => {
    track.trackProductVersioningWidgetLoaded({ productListVisible: 'foo', numberOfProducts: 2, screen: 'home' });
    expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith(expect.objectContaining({
      objectName: 'Product Versioning Widget',
      actionName: 'Loaded',
      screen: 'home',
      properties: expect.objectContaining({
        productListVisible: 'foo',
        experimentName: expect.any(String),
        numberOfProducts: 2,
      }),
      addUserProperties: true,
    }));
  });

  it('trackProductVersioningWidgetClicked calls analyticsTrackWithUserInfo with correct args', () => {
    track.trackProductVersioningWidgetClicked({ productClicked: 'foo', clickButtonName: 'bar', screen: 'home' });
    expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith(expect.objectContaining({
      objectName: 'Product Versioning Widget',
      actionName: 'Clicked',
      screen: 'home',
      properties: expect.objectContaining({
        productClicked: 'foo',
        experimentName: expect.any(String),
        clickButtonName: 'bar',
      }),
      addUserProperties: true,
    }));
  });

  it('trackProductVersioningWidgetFlipAction calls analyticsTrackWithUserInfo with correct args', () => {
    track.trackProductVersioningWidgetFlipAction({ productClicked: 'foo', actionName: 'Flip', screen: 'home' });
    expect(analyticsTrackWithUserInfo).toHaveBeenCalledWith(expect.objectContaining({
      objectName: 'Product Versioning Widget',
      actionName: 'Flip',
      screen: 'home',
      properties: expect.objectContaining({
        productClicked: 'foo',
        experimentName: expect.any(String),
      }),
      addUserProperties: true,
    }));
  });
}); 