import redirectToEasyOnboarding from '../redirectToEasyOnboarding';

describe('redirectToEasyOnboarding', () => {
  const mockAssign = jest.fn();
  const mockReplace = jest.fn();
  const mockUrl = 'https://example.com/onboarding';

  beforeEach(() => {
    const location = {
      ...window.location,
      assign: mockAssign,
      replace: mockReplace,
    };
    Object.defineProperty(window, 'location', {
      value: location,
    });
    window.EASY_ONBOARDING_URL = mockUrl;
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should call window.location.assign with the EASY_ONBOARDING_URL when replace is not provided', () => {
    redirectToEasyOnboarding();
    expect(mockAssign).toHaveBeenCalledWith(mockUrl);
  });

  test('should call window.location.replace with the EASY_ONBOARDING_URL when replace is true', () => {
    redirectToEasyOnboarding(true);
    expect(mockReplace).toHaveBeenCalledWith(mockUrl);
  });
});
