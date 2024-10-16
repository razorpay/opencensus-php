import { Text } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledText = styled(Text)`
  color: ${({ theme }) => theme.colors.surface.text.gray.normal};
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex-grow: 1;
`;
