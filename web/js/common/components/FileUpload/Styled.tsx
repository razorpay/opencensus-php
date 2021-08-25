import styled from 'styled-components';
import { colors, spacings } from '@razorpay/blade-old/src/tokens';
export const DashedButton = styled.label`
  input[type='file'] {
    display: none;
  }
  display: flex;
  width: 100%;
  border: 1px dashed ${colors.grey[500]};
  background: inherit;
  box-sizing: border-box;
  align-items: center;
  justify-content: center;
  &:hover {
    cursor: pointer;
  }
`;
export const UploadedBox = styled.div`
  border-radius: ${spacings.xsmall};
  border: 1px solid ${colors.cloud[960]};
  box-sizing: border-box;
  opacity: ${({ disabled }) => (disabled ? '0.3' : '1')};
  pointer-events: ${({ disabled }) => (disabled ? 'none' : 'all')};
`;
export const FileNameContainer = styled.div`
  overflow: hidden;
`;
