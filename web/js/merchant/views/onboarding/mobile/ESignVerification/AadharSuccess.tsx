import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import TextInput from '@razorpay/blade-old/src/atoms/TextInput';

const AadharSuccess: React.FC = () => {
  return (
    <View>
      <Text size="medium" weight="bold" color="shade.970">
        Aadhaar Verification ( via OTP )
      </Text>
      <Text size="xsmall" color="positive.900">
        We have Received your Aadhaar details successfully
      </Text>
      <Space margin={[4, 0, 0, 0]}>
        <View>
          <TextInput
            width="auto"
            name="aadharNumber"
            type="text"
            label="12 Digit Aadhaar Number"
            value="XXXXXXXXXXXX"
            hasRightIcon="check"
            disabled
          />
        </View>
      </Space>
    </View>
  );
};

export default AadharSuccess;
