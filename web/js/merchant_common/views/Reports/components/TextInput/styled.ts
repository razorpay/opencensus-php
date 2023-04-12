import styled from 'styled-components';
import { FULL_BASE_FIELD_STYLE } from 'merchant_common/views/Reports/components/styled';
import { BaseValidationStyledProps } from 'merchant_common/views/Reports/components/types';

export const Input = styled.input<BaseValidationStyledProps>`
  ${FULL_BASE_FIELD_STYLE}
  transition: border 0.2s ease;
`;
