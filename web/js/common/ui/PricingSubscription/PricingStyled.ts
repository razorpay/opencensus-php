import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';

const StyledDiv = styled.div(
  ({ theme, fullView }: { theme; fullView: boolean }) => `
      
      border-radius: ${theme.border.radius.medium};
      max-height: ${fullView ? '1439px' : 'unset'};
      background-color: ${theme.colors.surface.background.gray.intense};
      position: absolute;
      left: 50%;
      top: 50%;
      transform: ${fullView ? 'translate(-50%,-25%)' : 'translate(-50%,-50%)'}; 
  `,
);
const StyledTable = styled.table(
  ({ theme, pricingPlanLength }: { theme; pricingPlanLength: number }) => `
  width: 100%;
  border-radius: ${theme.border.radius.medium};
  display: table;
  border-collapse: separate;
  border-spacing: 15px 0;
  padding: ${theme.spacing[7]}px 0 ${theme.spacing[5]}px 0;
  background-color: ${theme.colors.surface.background.gray.intense};
  & > :first-child {
    & > *::after {
      content: none;
    }
    & > :nth-child(${pricingPlanLength}) {
      box-shadow: 0px 0px 16px rgba(85, 62, 223, 0.2);
      border: 1px solid #bdb3ff;
      border-bottom: none;
    }
  }
  & > :last-child {
    & > :nth-child(${pricingPlanLength}) {
      box-shadow: 0px 0px 16px rgba(85, 62, 223, 0.2);
      border: 1px solid #bdb3ff;
      border-top: none;
      &:before,
      &:after {
        content: none;
      }
    }
  }
`,
);
const StyledTr = styled.tr<any>(
  ({ pricingPlanLength }: { pricingPlanLength: number }) => `
  text-align: center;

  & > :not(:first-child) {
    box-shadow: 0px 0px 12px rgba(0, 0, 0, 0.06);
    &:before,
    &:after {
      content: ' ';
      height: 12px;
      position: absolute;
      width: 100%;
      left: 50%;
      transform: translateX(-50%);
      background: #fff;
      z-index: 1;
    }
    &:before {
      bottom: -1px;
    }
    &:after {
      top: -1px;
    }
  }
  & > :nth-child(${pricingPlanLength}) {
    box-shadow: 0px 0px 16px rgba(85, 62, 223, 0.2);
    border-left: 1px solid #bdb3ff;
    border-right: 1px solid #bdb3ff;

    &:before,
    &:after {
      content: ' ';
      height: 12px;
      position: absolute;
      width: 100%;
      left: 50%;
      transform: translateX(-50%);
      background: #fff;
      z-index: 1;
    }
    &:before {
      bottom: -1px;
    }
    &:after {
      top: -1px;
    }
  }
`,
);
const StyledTh = styled.th<any>`
  position: relative;
  text-align: center;
  position: relative;
  border-radius: 8px 8px 0 0;
  margin-right: ${({ addRightMargin }) => (addRightMargin ? '12px' : 'unset')};
`;
const StyleHeroImage = styled.div`
  > div:first-of-type img {
    position: absolute;
    top: -15px;
    left: -43px;
  }
  > div:last-of-type img {
    margin-top: ${({ theme }) => theme.spacing[10]}px;
    max-width: unset;
  }
  @media screen and (max-width: ${({ theme }) => theme.breakpoints.xl}px) {
    > div img {
      width: 168px;
    }
    > div:first-of-type img {
      left: -39px;
    }
  }
`;

const StyledTd = styled.td<any>`
  min-height: 48px;
  position: relative;
  border-radius: ${({ lastRow }) => (lastRow ? '0 0 8px 8px' : 'unset')};
  padding: 10px 20px;
  text-align: ${({ textAlign }) => (textAlign ? 'left' : 'center')};
  color: ${({ addLineGradient }) => (addLineGradient ? '#fff' : 'unset')};
  margin-right: ${({ addRightMargin }) => (addRightMargin ? '12px' : 'unset')};
  background: ${({ addLineGradient }) =>
    addLineGradient ? 'linear-gradient(126deg,#bdb3ff40 9.01%,#7866e821 98.6%)' : 'unset'};
  > p {
    white-space: pre-line;
  }
  ${({ verticalAlign }) => verticalAlign && `vertical-align: ${verticalAlign}`}
`;

const StyledFooter = styled.div(
  ({ theme }: { theme }) => `
    position: absolute;
    padding: 16px 20px;
    padding-right:0px;
    left: 50%;
    transform: translateX(-50%);
    
    border-radius: 0px 0px 8px 8px;
    background-color: ${theme.colors.surface.background.gray.intense};
    > button {
        margin-right: 20px;
    }
`,
);
const StyledHeader = styled.div`
  min-width: 900px;
  padding: 12px 12px;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  top: -48px;
  border-radius: 8px 8px 0px 0px;
  background-color: hsla(0, 0%, 100%, 1);
  display: flex;
  justify-content: space-between;
  align-items: center;
`;
const PlanLeftSection = styled.div`
  @media screen and (max-width: ${({ theme }) => theme.breakpoints.xl}px) {
    > h6 {
      font-size: 0.8rem;
    }
  }
`;
const StyledCloseIcon = styled.div<any>`
  cursor: pointer;
  margin-left: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
`;
const StyledModalClose = styled.div`
  position: absolute;
  right: 10px;
  top: 10px;
`;
const StyledHeaderIcon = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
`;
const StyleBadgeContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
  > :first-child {
    padding: 3px 0;
  }
`;
const StyleSwitchContainer = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;
`;
const StyleMonthlyPrice = styled.div`
  margin-top: 10px;
  margin-bottom: 10px;
`;
const StylePlanIcon = styled.div`
  margin-top: 16px;
`;
const StyleFireImage = styled.div`
  width: 32px;
`;
const StyleStrikePrice = styled.div(
  ({ theme, isMobile }: { theme: Theme; isMobile?: boolean }) => `
  margin-top: 10px;
  margin-bottom: 10px;
  display:flex;
  justify-content: ${isMobile ? 'flex-start' : 'center'};
  align-items: center;
  > p:first-of-type {
    text-decoration: line-through;
    padding-right: 8px;
  }
  > p:last-of-type {
    padding-right: 0px;
    color:${theme.colors.surface.text.onSea.onSubtle};
  }
`,
);
const StylePlanName = styled.div`
  width: 208px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: stretch;
  padding: 0 16px;
  @media screen and (max-width: ${({ theme }) => theme.breakpoints.xl}px) {
    width: 168px;
  }
`;

const StyleWrapper = styled.div`
  padding-bottom: 20px;
`;

const StylePlanWrapper = styled.div`
  padding-bottom: 20px;
  display: flex;
  justify-content: center;
  align-items: center;
  color: #8d7def;
  & > h6 {
    color: #8d7def;
  }
`;
const StylePercentageColor = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: inline-block;
  padding-left: 5px;
  > p {
  color: ${theme.colors.surface.text.onSea.onSubtle};
  }
`,
);

const Label = styled.label`
  display: flex;
  align-items: center;
  gap: 10px;
  cursor: pointer;
  margin-bottom: 0;
  margin-left: 12px;
`;

const Switch = styled.div`
  position: relative;
  width: 48px;
  height: 24px;
  background: #b3b3b3;
  border-radius: 32px;
  padding: 4px;
  transition: 300ms all;

  &:before {
    transition: 300ms all;
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    border-radius: 35px;
    top: 50%;
    left: 4px;
    background: white;
    transform: translate(0, -50%);
  }
`;

const Input = styled.input`
  opacity: 0;
  position: absolute;

  &:checked + ${Switch} {
    background: #2a86f3;

    &:before {
      transform: translate(22px, -50%);
    }
  }
`;
const StyleToastLink = styled.div`
  margin-right: 5px;
  > p {
    color: #fff;
    font-size: 14px;
    > button {
      margin-left: 5px;
    }
  }
`;
const StyleInfo = styled.div(
  ({ theme, isMobile }: { theme: Theme; isMobile: boolean }) => `
  display: flex;
  flex-direction: ${isMobile ? 'column' : 'row'}; 
  justify-content: ${isMobile ? 'flex-start' : 'center'}; 
  align-items: ${isMobile ? 'flex-start' : 'center'};
  margin-bottom: ${theme.spacing[6]}px;
  > p {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    color: ${theme.colors.surface.text.gray.muted};
    margin-bottom : ${isMobile ? theme.spacing[3] : theme.spacing[0]}px;

    ${isMobile ? '&::before' : '&:not(:first-of-type)::before'} {
      display: inline-block;
      content: "";
      width: 8px;
      height: 8px;
      border-radius: ${theme.border.radius.round};
      background: ${theme.colors.interactive.icon.gray.normal};
      margin-right:${theme.spacing[4]}px;
      margin-left: ${isMobile ? theme.spacing[0] : theme.spacing[4]}px;
    }
  } 
  > div:before {
    display: inline-block;
    content: "";
    width: 8px;
    height: 8px;
    border-radius: ${theme.border.radius.round};
    background: ${theme.colors.interactive.icon.gray.normal};
    margin-right:${theme.spacing[4]}px;
    margin-left: ${isMobile ? theme.spacing[0] : theme.spacing[4]}px;
  }
  

`,
);
export {
  StyledDiv,
  StyledTable,
  StyledTr,
  StyledTd,
  StyledTh,
  StyledFooter,
  StyledHeader,
  StyledCloseIcon,
  StyleStrikePrice,
  StylePlanIcon,
  StyleMonthlyPrice,
  StylePlanName,
  StyleWrapper,
  StylePercentageColor,
  Label,
  Input,
  Switch,
  StyledHeaderIcon,
  StyleToastLink,
  StyleSwitchContainer,
  StyleBadgeContainer,
  StyleHeroImage,
  PlanLeftSection,
  StyleFireImage,
  StyledModalClose,
  StyleInfo,
  StylePlanWrapper,
};
