import React from 'react';
import Size from '@razorpay/blade-old/src/atoms/Size';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import { headingDescriptionList } from '../../utils';

const OrgView = ({ orgData = {} }) => {
  let heading = `Powered by Razorpay and ${orgData.businessName}`;
  let description = `This joint initiative between ${orgData.businessName} and Razorpay aims to make accepting payments a seamless experience for fast-growing businesses.`;

  if (headingDescriptionList[orgData.orgName]) {
    heading = headingDescriptionList[orgData.orgName].heading;
    description = headingDescriptionList[orgData.orgName].description;
  }

  return (
    <View>
      <Space margin={[1, 0, 0, 0]}>
        <Text size="large" color="shade.700" weight="bold">
          {heading}
        </Text>
      </Space>
      <Space margin={[2, 0, 0, 0]}>
        <Text size="medium" color="shade.700" _lineHeight="large">
          {description}
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
