import styled from 'styled-components';
import { IconWrapper } from 'merchant/views/OnboardingConfigs/components/styled';

export const Logo = styled.img`
  max-width: 100%;
  height: auto;
  display: inline-block;
  width: auto;
  height: 28px;
`;

export const Topbar = styled.div`
  height: 5rem;
  margin: 0 1rem;
  padding: 0.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
`;

export const MerchantIconWrapper = styled(IconWrapper)`
  margin: 0 1rem;
`;
