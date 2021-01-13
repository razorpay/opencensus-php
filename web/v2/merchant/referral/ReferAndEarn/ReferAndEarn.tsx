import React from 'react';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Text from '@razorpay/blade/src/atoms/Text';
import Link from '@razorpay/blade/src/atoms/Link';
import Space from '@razorpay/blade/src/atoms/Space';
import Size from '@razorpay/blade/src/atoms/Size';

export interface PropsT {
  isOneReferralDone: boolean;
  isHomePage: boolean;
  width?: number | string;
}

const ReferAndEarn: React.FC<PropsT> = ({ isOneReferralDone, isHomePage, width }) => {
  return (
    <Flex flexDirection="column">
      <Size width={width}>
        <View>
          {isHomePage ? (
            isOneReferralDone ? (
              <Text weight="bold" _lineHeight="xlarge" size="xxlarge" color="shade.980">
                Refer 5 businesses and earn extra ₹2,50,000 bonus credits
              </Text>
            ) : (
              <Text weight="bold" _lineHeight="xlarge" size="xxlarge" color="shade.980">
                Refer & earn Rs.5L worth of transaction credits
              </Text>
            )
          ) : (
            <Text weight="bold" _lineHeight="xlarge" size="xxlarge" color="shade.980">
              Refer and earn Rs.50,000 transaction Credits for every referral
            </Text>
          )}
          {isHomePage ? (
            !isOneReferralDone && (
              <Space margin={[1, 0, 0]}>
                <Text color="shade.970" _lineHeight="medium">
                  Refer your friends who needs online payments and earn Rs.50,000 transaction
                  Credits for every referral <Link>More Details</Link>
                </Text>
              </Space>
            )
          ) : (
            <Space margin={[1, 0, 0]}>
              <Text color="shade.970" _lineHeight="medium">
                Know a business who needs to setup online payments? You will both receive ₹50,000
                transaction credits.<Link>Terms apply</Link>
              </Text>
            </Space>
          )}
        </View>
      </Size>
    </Flex>
  );
};

export default ReferAndEarn;
