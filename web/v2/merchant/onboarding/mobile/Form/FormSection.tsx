import React from 'react';
import styled from 'styled-components';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import Text from '@razorpay/blade/src/atoms/Text';
import Card from '../../../../components/Card';

const StyledView = styled(View)`
  opacity: ${({ disabled }) => (disabled ? '0.3' : '1')};
  pointer-events: ${({ disabled }) => (disabled ? 'none' : 'all')};
`;

interface FormSectionProps {
  title: string;
  subtitle?: string;
  last?: boolean;
  hasError?: boolean;
  disabled?: boolean;
}

const FormSection: React.FC<FormSectionProps> = ({
  title,
  subtitle,
  children,
  hasError = false,
  last = false,
  disabled = false,
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
    <StyledView disabled={disabled}>{children}</StyledView>
  </Card>
);

export default FormSection;
