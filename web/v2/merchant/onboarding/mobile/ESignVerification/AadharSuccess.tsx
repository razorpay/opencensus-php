import React from 'react';
import View from '@razorpay/blade/src/atoms/View';
import Text from '@razorpay/blade/src/atoms/Text';
import Space from '@razorpay/blade/src/atoms/Space';
import TextInput from '@razorpay/blade/src/atoms/TextInput';

const AadharSuccess: React.FC = () => {
  return (
    <Space margin={[4, 0, 0, 0]}>
      <View>
        <Text size="medium" weight="bold" color="shade.970">
          Aadhar Verification
        </Text>
        <Text size="xsmall" color="positive.900">
          We have recieved your Aadhar details successfully
        </Text>
        <Space margin={[4, 0, 0, 0]}>
          <View>
            <TextInput
              width="auto"
              name="aadharNumber"
              type="text"
              label="12 Digit Aadhar Number"
              value="XXXXXXXXXXXX"
              hasRightIcon="check"
              disabled
            />
          </View>
        </Space>
      </View>
    </Space>
  );
};

export default AadharSuccess;
