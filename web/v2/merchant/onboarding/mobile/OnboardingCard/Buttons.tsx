import React from 'react';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Link from '@commander/shield/src/shared/Link';

interface ButtonPropsT {
  onClick?: () => void;
  icon?: string;
  title?: string;
}

export const Primary: React.FC<ButtonPropsT> = ({ icon = null, title = '', onClick }) => (
  <Space margin={[2.5, 0, 0, 0]}>
    <View>
      <Button onClick={onClick} size="large" icon={icon} iconAlign="right" block>
        {title}
      </Button>
    </View>
  </Space>
);

export const LinkButton: React.FC<ButtonPropsT> = ({ icon = null, title = '', onClick }) => (
  <Space margin={[2.5, 0, 0, 0]}>
    <Flex>
      <View>
        <Link onClick={onClick}>{title}</Link>
        {icon && (
          <Space margin={[0, 0, 0, 1]}>
            <View>
              <Icon name="arrowRight" size="medium" fill="primary.800" />
            </View>
          </Space>
        )}
      </View>
    </Flex>
  </Space>
);

export const Secondary: React.FC<ButtonPropsT> = ({ icon = null, title = '', onClick }) => (
  <Space margin={[2.5, 0, 0, 0]}>
    <View>
      <Button
        onClick={onClick}
        size="large"
        icon={icon}
        iconAlign="right"
        variant="secondary"
        block
      >
        {title}
      </Button>
    </View>
  </Space>
);

export default {
  Primary,
  LinkButton,
  Secondary,
};
