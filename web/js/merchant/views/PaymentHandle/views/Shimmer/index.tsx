import React from 'react';
import Card from 'common/components/Card';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import View from '@razorpay/blade-old/src/atoms/View';
import { Text } from '@razorpay/blade/components';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Space from '@razorpay/blade-old/src/atoms/Space';
import { LoadingCTA } from 'merchant/views/PaymentHandle/style';
import Loader from 'merchant/views/PaymentHandle/components/loader';
import Shimmer from 'merchant/views/PaymentHandle/components/shimmer';

interface LoadingText {
  showError: boolean;
}

const LoadingText = ({ showError = false }: LoadingText): JSX.Element => {
  return (
    <View>
      <Space padding={[5, 4, 0, 3]}>
        <View>
          <Card>
            <Size height="308px">
              <Flex alignItems="center" justifyContent="center" flexDirection="column">
                <View>
                  <Flex alignItems="center" justifyContent="flex-start">
                    <Size width="432px">
                      <LoadingCTA>
                        {!showError && <Loader width="12px" height="12px" margin={[0, 1, 0, 0]} />}
                        <Text size="medium" color="surface.text.gray.subtle">
                          {showError
                            ? 'Something went wrong, Our team will get back to you shortly.'
                            : 'Please wait... Loading your Razorpay.me link details...'}
                        </Text>
                      </LoadingCTA>
                    </Size>
                  </Flex>
                  <Shimmer />
                </View>
              </Flex>
            </Size>
          </Card>
        </View>
      </Space>
    </View>
  );
};

export default LoadingText;
