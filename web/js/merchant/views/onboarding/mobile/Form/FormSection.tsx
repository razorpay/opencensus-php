import React from 'react';
import styled from 'styled-components';
import Space from '@razorpay/blade-old/src/atoms/Space';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Card from 'common/components/Card';

const StyledView = styled(View)`
  opacity: ${({ disabled }) => (disabled ? '0.3' : '1')};
  pointer-events: ${({ disabled }) => (disabled ? 'none' : 'all')};
`;

type size = 'xxsmall' | 'xsmall' | 'small' | 'medium' | 'large';
interface FormSectionProps {
  title: string;
  subtitle?: string;
  titleFontSize?: size;
  subtitleFontSize?: size;
  last?: boolean;
  hasError?: boolean;
  visible?: boolean;
  disabled?: boolean;
  isSuccess?: boolean;
  padding?: number[] | string[] | number | string;
}

const FormSection: React.FC<FormSectionProps> = ({
  title,
  subtitle,
  titleFontSize = 'xsmall',
  subtitleFontSize = 'xxsmall',
  children,
  hasError = false,
  last = false,
  visible = true,
  disabled = false,
  isSuccess = false,
  padding = [2],
}) => {
  if (!visible) {
    return null;
  }
  return (
    <Card padding={padding} margin={last ? 0 : [0, 0, 2, 0]}>
      <Space margin={[0, 0, 3, 0]}>
        <View>
          <Text size={titleFontSize} weight="bold">
            {title}
          </Text>
          {subtitle ? (
            <Text
              size={subtitleFontSize}
              color={hasError ? 'negative.900' : isSuccess ? 'positive.900' : 'shade.950'}
            >
              {subtitle}
            </Text>
          ) : null}
        </View>
      </Space>
      <StyledView disabled={disabled}>{children}</StyledView>
    </Card>
  );
};

export default FormSection;
