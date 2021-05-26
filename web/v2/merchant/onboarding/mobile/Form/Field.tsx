import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';

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
    <Space margin={[0, 0, 3.5, 0]}>
      <View>{children}</View>
    </Space>
  );
};

export default Field;
