import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import { useApp } from 'common/context/App';

const AadharError: React.FC = () => {
  const { experiments } = useApp();
  return (
    <Space padding={[0, 0, 2]}>
      <View>
        <Text size="medium" weight="bold" color="shade.970">
          Aadhaar Verification
        </Text>
        <Space padding={[1, 0, 0]}>
          {experiments.isDigilockerEkyc ? (
            <Text size="small" color="shade.960">
              We can not support OTP based Aadhaar verification because of downtime on Digilocker
              servers. Please upload copies of one the address proofs listed below.
            </Text>
          ) : (
            <Text size="small" color="shade.960">
              We can not support OTP based Aadhaar verification because of downtime on UIDAI
              servers. Please upload copies of one the address proofs listed below.
            </Text>
          )}
        </Space>
      </View>
    </Space>
  );
};

export default AadharError;
