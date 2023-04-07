import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';
import { BannerWrapper } from 'merchant/views/Settlements/components/SettlementsBannerV2';

export const StyledEmptySettlementsContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding-top: ${theme.spacing[7]}px;
  margin: 0 ${theme.spacing[4]}px;

  ${BannerWrapper}{
    padding: 0;
    margin-bottom: ${theme.spacing[9]}px;
  }
`,
);

export const StyledEmptySettlementsBox = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: #FFFFFF;
  padding: 64px ${theme.spacing[4]}px 108px;
  text-align: center;
  
  img{
    margin-bottom: ${theme.spacing[3]}px;
  }

  h5{
    margin-bottom: ${theme.spacing[4]}px;
  }

  p {
    max-width: 400px;
    margin-bottom: ${theme.spacing[5]}px;
  }

  @media (max-width: 767px) {
    padding-top: ${theme.spacing[8]}px;
    padding-bottom: ${theme.spacing[9]}px;
  }

`,
);
