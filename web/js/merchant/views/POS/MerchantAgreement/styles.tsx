import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledHandshake = styled.img`
  width: 100%;
  height: auto;
`;
export const StyledAgreementContainer = styled.div(
  ({ theme, padding }: { theme: Theme; padding: string }) => `
    background-color: #050844;
    min-height:80dvh;
    display:flex;
    flex-direction:column;
    padding: ${padding || '0px'};
    @media screen and (min-width:${theme.breakpoints.s}px) {
      padding:${theme.spacing[7]}px ${theme.spacing[6]}px;
      background-color:${theme.colors.surface.background.gray.intense};
      border-radius:${theme.spacing[5]}px;
      max-width:650px;
      margin:auto
    }
    `,
);

export const StyledBanner = styled.div(
  ({ theme, displayStyle = 'block' }: { theme: Theme; displayStyle: string }) => `
  padding:50px 34px 0 34px;
  background-color:#050844;
  @media screen and (min-width:${theme.breakpoints.s}px){
    border-radius: ${theme.spacing[3]}px;
    padding:${theme.spacing[8]}px 0 0 ${theme.spacing[9]}px;
    display:${displayStyle}
  }
  `,
);

export const StyledPricingTemplateContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-top:72px;
  padding:${theme.spacing[8]}px;
  table {
    width: 50%;
    border-collapse: collapse;
    margin: ${theme.spacing[6]}px 0;
  }
  table .textBold{
    font-weight:bold;
  }
  ul,ol {
    padding-inline-start:0;
    padding:0 ${theme.spacing[3]}px
  }
  th, td {
    border: 1px solid #000;
    padding: ${theme.spacing[3]}px;
    text-align: left;
  }
  th {
    background-color: #d9d9d9;
  }
  #overall-rental-fee td:not(:first-child):not(:last-child){
    border-right:1px solid transparent
  }
  .center-heading{
    text-align:center
  }
  .noListStyle{
    list-style-type:none
  }
  .scroll{
    overflow:scroll;
  }
  .mb-40{
    margin-bottom:${theme.spacing[7]}px
  }
  .mb-24{
    margin-bottom:${theme.spacing[7]}px
  }
  @media screen and (max-width:${theme.breakpoints.m}px){
    margin-top:50px;
    padding:${theme.spacing[5]}px;
    padding-top:${theme.spacing[8]}px;
    table{
      width:100%
    }
  }
  `,
);
