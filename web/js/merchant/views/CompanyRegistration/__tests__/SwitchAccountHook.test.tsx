import { renderHook, act } from '@testing-library/react-hooks';
import { useMultiAccount } from '../hooks/SwitchAccountHook';
import { trackEventOnCreateAccountCtaClick } from '../analytics';
import { MULTI_ACCOUNT } from '../constant';

// Mock the necessary functions
jest.mock('../analytics', () => ({
  trackEventOnCreateAccountCtaClick: jest.fn(),
}));

jest.mock('newAuth/@commander-shield/api/signinApi', () => ({
  logoutUser: jest.fn().mockResolvedValue(undefined),
}));

describe('useMultiAccount Hook', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should call handleLogout when selected is not "primary"', () => {
    const { result } = renderHook(() => useMultiAccount());

    // Change the selected state to something other than 'primary'
    act(() => {
      result.current.setSelected('secondary');
    });

    // Trigger handleUserAction, which should call handleLogout now
    act(() => {
      result.current.handleUserAction();
    });

    // Check if logoutUser was called (handleLogout internally calls logoutUser)
    expect(require('newAuth/@commander-shield/api/signinApi').logoutUser).toHaveBeenCalledTimes(1);
  });

  test('should call trackEventOnCreateAccountCtaClick when handleUserAction is triggered', () => {
    const { result } = renderHook(() => useMultiAccount());

    // Set the selected state to 'primary'
    act(() => {
      result.current.setSelected('primary');
    });
    // Trigger handleUserAction when selected is "primary"
    act(() => {
      result.current.handleUserAction();
    });

    // Check if the trackEventOnCreateAccountCtaClick was called
    expect(trackEventOnCreateAccountCtaClick).toHaveBeenCalledTimes(1);
  });

  test('should open MULTI_ACCOUNT link in a new tab when selected is "primary"', () => {
    const { result } = renderHook(() => useMultiAccount());

    // Mock window.open
    const openSpy = jest.spyOn(window, 'open').mockImplementation(() => {});
    
    // Set the selected state to 'primary'
    act(() => {
      result.current.setSelected('primary');
    });

    // Trigger handleUserAction when selected is "primary"
    act(() => {
      result.current.handleUserAction();
    });

    // Check if window.open was called with MULTI_ACCOUNT link
    expect(openSpy).toHaveBeenCalledWith(MULTI_ACCOUNT, '_blank', 'noopener,noreferrer');
  });
});
