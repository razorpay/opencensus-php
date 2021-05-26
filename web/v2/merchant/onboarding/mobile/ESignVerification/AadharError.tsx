import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';

const AadharError: React.FC = () => {
  return (
    <Space padding={[0, 0, 2]}>
      <View>
        <Text size="medium" weight="bold" color="shade.970">
          Aadhar Verification ( Via OTP )
        </Text>
        <Text size="xsmall" color="shade.950">
          The Aadhar database does not seem to be working at the moment you can continue without
          verification
        </Text>
      </View>
    </Space>
  );
};

export default AadharError;
