import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';

export const Divider = styled.div`
  background: rgba(22, 47, 86, 0.24);
  width: 100%;
  height: 1px;
  opacity: 0.3;
`;

export const StyledView = styled(View)`
  opacity: ${({ disable }) => (disable ? '0.7' : '1')};
  pointer-events: ${({ disable }) => (disable ? 'none' : 'all')};
`;
