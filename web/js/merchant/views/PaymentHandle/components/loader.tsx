import React from 'react';
import Space from '@razorpay/blade-old/src/atoms/Space';
import { StyledLoader } from 'merchant/views/PaymentHandle/style';
import { LoaderPropsType } from 'merchant/views/PaymentHandle/typings';

const Loader: React.FC<LoaderPropsType> = ({
  margin = [0],
  padding = [0],
  width = '12px',
  height = '12px',
  makePxValue = false,
}): JSX.Element => {
  return (
    <Space margin={margin} padding={padding}>
      <StyledLoader width={width} height={height} role="loader" makePxValue={makePxValue} />
    </Space>
  );
};

export default Loader;
