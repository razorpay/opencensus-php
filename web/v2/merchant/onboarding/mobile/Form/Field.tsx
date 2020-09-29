import React from 'react';
import View from '@razorpay/blade/src/atoms/View';
import Space from '@razorpay/blade/src/atoms/Space';

interface FieldProps {
  visible?: boolean;
  last?: boolean;
}

const Field: React.FC<FieldProps> = ({ children, visible = true, last = false }) => {
  if (!visible) {
    return null;
  }

  if (last) {
    return <>{children}</>;
  }

  return (
    <Space margin={[0, 0, 2.5, 0]}>
      <View>{children}</View>
    </Space>
  );
};

export default Field;
