import React from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import { BANK_NAMES } from '../../utils';

const OrgView = ({ orgData }) => {
  // new banking url requirements thread:
  // razorpay.slack.com/archives/CTM086NSF/p1631183451171900
  const bankName =
    orgData.orgName === BANK_NAMES.ICICI ? 'ICICI Bank Eazypay Pro' : orgData.businessName;
  return (
    <View>
      <Space margin={[1, 0, 0, 0]}>
        <Text size="large" color="shade.700" weight="bold">
          {orgData.orgName === BANK_NAMES.ICICI
            ? 'ICICI Bank Eazypay Pro powered by Razorpay'
            : `Powered by Razorpay and ${orgData.businessName}`}
        </Text>
      </Space>
      <Space margin={[2, 0, 0, 0]}>
        <Text size="medium" color="shade.700" _lineHeight="large">
          This joint initiative between {bankName} and Razorpay aims to make accepting payments a
          seamless experience for fast-growing businesses.
        </Text>
      </Space>
      <Space margin={[3, 0, 0, 0]}>
        <Text size="medium" color="shade.700">
          Let’s simplify payments together!
        </Text>
      </Space>
      <Space margin={[3, 0, 15, 0]}>
        <Size maxWidth="160px">
          <img src="img/branding/powered-by-razorpay-login.png" alt="Powered by Razorpay" />
        </Size>
      </Space>
    </View>
  );
};
export default OrgView;
