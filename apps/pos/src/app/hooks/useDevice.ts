import { useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { DeviceType } from '../types';

const useDevice = (deviceType: DeviceType) => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  return matchedDeviceType === deviceType;
};

export default useDevice;
