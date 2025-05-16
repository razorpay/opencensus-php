import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

export const RekycBannerImageWrapper = styled.div(({isMobile, theme}: {isMobile: boolean, theme: Theme}) => {
  if(isMobile){
    return `
      width: 100%;
      height: 130px;
      position: absolute;
      top: 0px;
      left: 0px;
      padding: 20px;
      background: #B6ECD1;
      border-top-right-radius: ${theme.spacing[3]}px;
      border-top-left-radius: ${theme.spacing[3]}px;
      text-align: center;
    `
  }

  return `
    position: absolute;
    right: 20px;
    bottom: 0px;
  `
})
