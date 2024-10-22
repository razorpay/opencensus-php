import styled from 'styled-components';
import { makeSpace } from '@razorpay/blade/utils';

import ccCartIllustration from 'assets/c360/cc-cart-illustration.svg';

export const TabContent = styled.div`
  background: ${({ theme }) => theme.colors.surface.background.gray.intense};
  display: flex;
  flex-direction: column;
  gap: ${({ theme }) => makeSpace(theme.spacing[7])};
`;

export const OnboardedMerchantTabHeader = styled.div`
  position: relative;
  padding-block: ${({ theme }) => makeSpace(theme.spacing[9])};
  padding-inline: ${({ theme }) => makeSpace(theme.spacing[8])};
  background: linear-gradient(
    89deg,
    rgba(48, 94, 255, 0.18) -3.23%,
    rgba(255, 255, 255, 0) 9.15%,
    rgba(48, 94, 255, 0.18) 87.26%
  );

  &::after {
    content: '';
    display: block;
    position: absolute;
    top: 0;
    bottom: 0;
    right: 0;
    min-width: 300px;
    background: url(${ccCartIllustration}) right center no-repeat;
  }
`;

export const C360BannerImage = styled.img`
  height: 100%;
  max-height: inherit;
`;

export const ShopifyBadge = styled.div`
  border-radius: 4px;
  padding: ${({ theme }) => makeSpace(theme.spacing[3])}
    ${({ theme }) => makeSpace(theme.spacing[4])};
  display: flex;
  align-items: center;
  position: absolute;
  right: 14px;
  bottom: 14px;
  background: ${({ theme }) => theme.colors.surface.icon.staticWhite.subtle};
`;
