import { SelectInputOnChangeProps } from './types';

// All blade patch including this file and others as well can be tracked using @blade-patch

// @blade-patch
// Issue: https://github.com/razorpay/blade/issues/1102
export const patchedSelectOnChange = (handler: (x: SelectInputOnChangeProps) => void) => {
  return (selectProps: SelectInputOnChangeProps) => {
    if (selectProps.values && selectProps.values[0] === '') return () => {};
    return handler(selectProps);
  };
};
