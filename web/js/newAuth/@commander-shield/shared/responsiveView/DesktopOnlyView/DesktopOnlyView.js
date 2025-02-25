import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';

export const DesktopOnlyView = styled(View)`
  display: none;

  @media (min-width: 769px) {
    display: block;
  }
`;

export default DesktopOnlyView;
