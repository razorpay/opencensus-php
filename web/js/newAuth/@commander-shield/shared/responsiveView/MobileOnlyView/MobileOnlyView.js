import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';

export const MobileOnlyView = styled(View)`
  display: none;

  @media (max-width: 768px) {
    display: block;
  }
`;

export default MobileOnlyView;
