import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ConfirmationBannerContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  position: relative;
  min-height: 250px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  padding: ${theme.spacing[10]}px ${theme.spacing[5]}px;
  margin-bottom: ${theme.spacing[5]}px;
  overflow: hidden;
  background: linear-gradient(
    181deg,
    #e6f7ed 1.46%,
    rgba(230, 247, 237, 0.5) 50.36%,
    rgba(255, 255, 255, 0) 99.27%
  );
`,
);

export const BannerOverlay = styled.div`
  z-index: 1;
  position: absolute;
  height: 100%;
  top: 0px;
  bottom: 0px;
  left: 0px;
  right: 0px;
  background: linear-gradient(
    0deg,
    rgba(255, 255, 255, 1) 0%,
    rgba(255, 255, 255, 0.17550770308123254) 35%,
    rgba(255, 255, 255, 0) 100%
  );
`;
