import styled from 'styled-components';

interface StyledProviderItemProps {
  isClickable?: boolean;
}

export const StyledProviderItem = styled.div<StyledProviderItemProps>`
  display: flex;
  align-items: center;
  gap: ${({ theme }) => `${theme.spacing[4]}px`};
  min-width: 200px;
  height: 52px;
  padding: 7px;
  background-color: ${({ theme }) => theme.colors.surface.background.gray.moderate};
  border: 1px solid ${({ theme }) => theme.colors.surface.border.gray.subtle};
  border-radius: ${({ theme }) => `${theme.spacing[2]}px`};
  cursor: ${({ isClickable }) => (isClickable ? 'pointer' : 'default')};
`;

export const StyledLogoWrapper = styled.div(
  ({ theme }) => `
  width: 34px;
  height: 34px;
  padding: ${theme.spacing[2]}px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #FAFCFF;
  border-radius: ${theme.border.radius.round};
`,
);

export const StyledProviderLogo = styled.img`
  display: inline-block;
  max-width: 100%;
  height: auto;
  object-fit: contain;
`;

export const StyledDivider = styled.hr(
  ({ theme }) => `
  border: 0;
  height: 1px;
  background-color: ${theme.colors.surface.border.gray.muted};
  margin: ${theme.spacing[5]}px 0;
`,
);

export const IconBackground = styled.div<{ status: string }>`
  height: 20px;
  width: 20px;
  border-radius: 50%;
  display: inline-flex;
  vertical-align: text-bottom;
  background-color: ${({ status, theme }) =>
    status === 'positive'
      ? theme.colors.feedback.background.positive.subtle
      : theme.colors.feedback.background.negative.subtle};
`;

export const StepWrapper = styled.div<{ active: boolean }>`
  border-radius: 4px;
  background-color: ${({ active, theme }) =>
    active ? theme.colors.surface.background.primary.subtle : 'transparent'};
  
  #-actionlist {
    div:nth-child(1) {
      padding: 0;
    }
  }
}
`;
