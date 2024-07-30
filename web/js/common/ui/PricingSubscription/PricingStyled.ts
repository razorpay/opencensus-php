import { Theme } from '@razorpay/blade/components';
import RecommendedPlanBg from 'assets/pricing-bundle/focused-plan-bg.svg';
import styled from 'styled-components';

const Container = styled.div(
  ({ theme, fullView }: { theme; fullView: boolean }) => `
    border-radius: ${theme.border.radius.medium}px;
    max-height: ${fullView ? '1439px' : 'unset'};
    position: absolute;
    left: 50%;
    top: 50%;
    transform: ${fullView ? 'translate(-50%,-25%)' : 'translate(-50%,-50%)'}; 
  `,
);

const FeatureItem = styled.div(
  ({
    theme,
    addLineGradient,
    isRecommended,
    planTitle,
    showRowTitle,
  }: {
    theme: Theme;
    addLineGradient?: boolean;
    isRecommended?: boolean;
    planTitle?: string;
    showRowTitle?: string;
  }) => `
  min-height: ${theme.spacing[10]}px;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  margin: ${theme.spacing[4]}px 0;
  padding: ${theme.spacing[3]}px ${theme.spacing[6]}px;
  text-align: center;

   &::before {
    display: ${showRowTitle ? 'flex' : 'none'};
    content: "${planTitle}";
    position: absolute;
    top: 0;
    left: -100%;
    font-weight: ${theme.typography.fonts.weight.semibold};
    font-size: ${theme.typography.fonts.size[200]}px;
    width: 100%;
    text-align: left;
    height: 100%;
    align-items: center;
    justify-content: start;
  }

  &::after {
    content: '';
    position: absolute;
    height: 100%;
    width: 100%;
    top:0;
    left:0;
    background: ${
      addLineGradient ? 'linear-gradient(126deg, #DAF5E8 9.01%, #008743 98.6%)' : 'unset'
    };   
    box-shadow: ${
      isRecommended
        ? '0px 0px 12px 0px rgba(0, 0, 0, 0.06)'
        : addLineGradient
        ? '0px 0px 12px 0px rgba(0, 0, 0, 0.06)'
        : 'unset'
    };
    opacity:0.1;
  }
`,
);

const Footer = styled.div(
  ({ theme }: { theme: Theme }) => `
    position: absolute;
    padding: ${theme.spacing[6]}px;
    left: 50%;
    transform: translate(-50%);
    border-radius: ${theme.spacing[0]}px ${theme.spacing[0]}px ${theme.spacing[3]}px ${theme.spacing[3]}px;
    background-color: ${theme.colors.surface.background.gray.moderate};
    z-index: -1;
`,
);
const Header = styled.div(
  ({ theme }: { theme: Theme }) => `
    min-width: 900px;
    padding: 0 ${theme.spacing[7]}px;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: -72px;
    height: 72px;
    border-radius: ${theme.spacing[3]}px ${theme.spacing[3]}px ${theme.spacing[0]}px ${theme.spacing[0]}px;
    background-color: ${theme.colors.surface.background.gray.moderate};
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: -1;
`,
);

const StyledCloseIcon = styled.div<any>`
  cursor: pointer;
  margin-left: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
`;
const ModalClose = styled.div`
  position: absolute;
  right: 10px;
  top: 10px;
`;

const FireImage = styled.div`
  width: 32px;
`;

const CloseModalButton = styled.div`
  width: ${(props) => props.theme.spacing[10]}px;
  height: ${(props) => props.theme.spacing[10]}px;
  transform: translateX(calc(100% + 14px));
  position: absolute;
  right: 0;
  top: 0;
  background-color: white;
  border-radius: 50%;
  transform: translate(calc(100% + 14px), 7px);
  display: grid;
  place-items: center;
  cursor: pointer;
`;

const StrikePrice = styled.div(
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

const PercentageColor = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: inline-block;
  padding-left: 5px;
  > p {
  color: ${theme.colors.surface.text.onSea.onSubtle};
  }
`,
);

const ToggleLabel = styled.label`
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
const PlansTncSection = styled.div(
  ({ theme, isMobile }: { theme: Theme; isMobile: boolean }) => `
  display: ${isMobile ? 'grid' : 'flex'};
  place-items: ${isMobile ? 'center' : 'unset'};
  place-content: ${isMobile ? 'center' : 'unset'};
  flex-direction: ${isMobile ? 'column' : 'row'}; 
  justify-content: center;
  align-items: ${isMobile ? 'flex-start' : 'center'};
  margin-top: ${isMobile ? theme.spacing[0] : theme.spacing[5]}px;
  > p {
    margin-bottom : ${isMobile ? theme.spacing[2] : theme.spacing[0]}px;

    ${!isMobile && '&:not(:first-of-type)::before'} {
      display: inline-block;
      content: "";
      width: 6px;
      height: 6px;
      border-radius: ${theme.border.radius.round};
      background: ${theme.colors.interactive.icon.gray.normal};
      margin-right:${theme.spacing[4]}px;
      margin-left: ${isMobile ? theme.spacing[0] : theme.spacing[4]}px;
    }
  } 

   

  > div:before {
    ${!isMobile} {
      display: inline-block;
      content: "";
      width: 6px;
      height: 6px;
      border-radius: ${theme.border.radius.round};
      background: ${theme.colors.interactive.icon.gray.normal};
      margin-right:${theme.spacing[4]}px;
      margin-left: ${isMobile ? theme.spacing[0] : theme.spacing[4]}px;
    }
  }
`,
);

const PlanSection = styled.div(
  ({ theme }: { theme }) => `
  border-radius: ${theme.spacing[3]}px;
  padding: ${theme.spacing[7]}px ${theme.spacing[7]}px ${theme.spacing[5]}px ${theme.spacing[7]}px;
  background-color: ${theme.colors.surface.background.gray.intense};
  box-shadow: 0px 0px 12px 0px #0000000F;
`,
);

const PlanRow = styled.div(
  ({ theme, noBorder, isRecommended }: { theme; noBorder?: boolean; isRecommended?: boolean }) => `
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  text-align: center;
  margin:  0px ${theme.spacing[3]}px  0px ${theme.spacing[3]}px;
  box-shadow: ${
    noBorder
      ? 'none'
      : isRecommended
      ? '0px 0px 16px 0px rgba(0, 135, 67, 0.20) inset;'
      : '0px 0px 12px 0px #0000000f'
  };
  border: ${noBorder ? 'none' : `1px solid ${theme.colors.feedback.border.positive.subtle}`};
  border-radius: ${theme.spacing[3]}px;
  padding: ${theme.spacing[5]}px ${theme.spacing[6]}px;
  width: 208px;
  position: relative;
  cursor: pointer;

  &:hover {
    box-shadow: 0px 0px 16px 0px rgba(0, 135, 67, 0.20) inset;;
  }

  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: ${isRecommended ? `url(${RecommendedPlanBg})` : 'unset'};
    background-size: cover;
  }

  @media screen and (max-width: ${({ theme }) => theme.breakpoints.xl}px) {
    width: 168px;
  }
`,
);

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

const StyledButton = styled.button`
  ${(props) => props.isLoading && 'cursor: wait;'}
  ${(props) => props.isDisabled && 'cursor: not-allowed;'}
`;
export {
  CloseModalButton,
  PlanSection,
  Input,
  ToggleLabel,
  PlanRow,
  PlansTncSection,
  FeatureItem,
  StyledButton,
  StyledCloseIcon,
  Container,
  Footer,
  Header,
  ModalClose,
  FireImage,
  StyleHeroImage,
  PercentageColor,
  StrikePrice,
  StyleToastLink,
  Switch,
};
