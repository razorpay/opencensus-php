import React from 'react';
import { StyledText } from 'merchant/views/PaymentHandle/style';
import { TextWrapperPropTypes } from 'merchant/views/PaymentHandle/typings';

const Text: React.FC<TextWrapperPropTypes> = ({
  text,
  color,
  fontSize,
  className,
  fontWeight,
  mobileFontSize,
  mobileFontWeight,
}) => {
  return (
    <StyledText
      color={color}
      fontSize={fontSize}
      className={className}
      fontWeight={fontWeight}
      mobileFontSize={mobileFontSize}
      mobileFontWeight={mobileFontWeight}
    >
      {text}
    </StyledText>
  );
};

export default Text;
