import styled from 'styled-components';

export const ContainerWrapper = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${({ theme }) => theme.spacing[7]}px;

  @media screen and (max-width: ${({ theme }) => theme.breakpoints.m}) {
    gap: ${({ theme }) => theme.spacing[5]}px;
  }
`;

export const OnboardingWrapper = styled.div(
  ({ theme }) => `
  background-color: ${theme.colors.surface.background.gray.intense};
  display: flex;
  flex-direction: column;
  gap: ${theme.spacing[6]}px;
  padding: ${theme.spacing[7]}px;

  @media screen and (max-width: ${theme.breakpoints.m}) {
    padding: ${theme.spacing[7]}px ${theme.spacing[5]}px;
  }
`,
);

export const OnboardingCardContainer = styled.div`
  display: grid;
  grid-gap: ${({ theme }) => theme.spacing[5]}px;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 352px), 1fr));
`;

export const OnboardingCard = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${({ theme }) => theme.spacing[3]}px;
  background-color: ${({ theme }) => theme.colors.surface.background.primary.subtle};
  padding: ${({ theme }) =>
    `${theme.spacing[5]}px ${theme.spacing[7]}px ${theme.spacing[5]}px ${theme.spacing[7]}px`};
  border-radius: ${({ theme }) => theme.border.radius.large}px;
`;

export const StyledImage = styled.img`
  width: 100%;
  height: auto;
`;
