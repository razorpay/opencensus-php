import React from 'react';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import Text from '@razorpay/blade/src/atoms/Text';
import Card from '../../../../components/Card';

interface FormSectionProps {
  title: string;
  subtitle?: string;
  last?: boolean;
  hasError?: boolean;
}

const FormSection: React.FC<FormSectionProps> = ({
  title,
  subtitle,
  children,
  hasError = false,
  last = false,
}) => (
  <Card padding={[2]} margin={last ? 0 : [0, 0, 2, 0]}>
    <Space margin={[0, 0, 3, 0]}>
      <View>
        <Text size="medium" weight="bold">
          {title}
        </Text>
        {subtitle ? (
          <Text size="xxsmall" color={hasError ? 'negative.900' : 'shade.950'}>
            {subtitle}
          </Text>
        ) : null}
      </View>
    </Space>
    {children}
  </Card>
);

export default FormSection;
