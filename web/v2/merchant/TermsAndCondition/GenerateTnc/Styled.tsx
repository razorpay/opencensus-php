import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Text from '@razorpay/blade-old/src/atoms/Text';

export const StyledContent = styled(View)`
  min-height: 100vh;
  background-color: ${({ theme }) => theme.colors.background[400]};
  border-top: 1px solid rgba(224, 228, 249, 0.54);
`;

export const StyledHeader = styled(View)`
  background-color: ${({ theme }) => theme.colors.background[200]};
`;

export const StyledFooter = styled(View)`
  box-sizing: border-box;
  width: 100%;
  position: fixed;
  bottom: 0;
  left: 0;
  padding: 16px;
  background-color: ${({ theme }) => theme.colors.background['200']};
  border-top: 1px solid rgba(22, 47, 86, 0.1);
`;

export const LinkText = styled(Text)`
  border-top: 1px solid #162f561a;
`;

export const Image = styled.img`
  width: 100%;
`;

export const StyledText = styled(View)`
  background: #fcf8e5;
`;
