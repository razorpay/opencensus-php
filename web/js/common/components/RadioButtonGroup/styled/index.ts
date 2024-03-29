import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const StyledRadioGroupWrapper = styled.div`
  display: flex;
`;

export const StyledRadioButton = styled.label(
  ({
    theme,
    checked,
    disabled,
  }: {
    theme: Theme;
    checked: boolean;
    disabled: boolean | undefined;
  }) => `
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 85px;
  height: 35px;
  font-weight: ${theme.typography.fonts.weight.regular};
  padding: ${theme.spacing[3]}px ${theme.spacing[4]}px;
  margin: 0px;
  background-color:theme.colors.white ;
  color: 
    ${checked ? theme.colors.surface.text.gray.normal : theme.colors.surface.text.gray.muted};
  border: 1px solid transparent;
  border-color: 
    ${
      checked
        ? theme.colors.surface.background.primary.intense
        : theme.colors.surface.border.gray.muted
    };
  cursor: ${disabled ? 'not-allowed' : 'pointer'};
  user-select: none;

  &:hover {
    color: ${
      disabled ? theme.colors.surface.text.gray.muted : theme.colors.surface.text.gray.normal
    };
  }

  &:first-child {
    border-top-left-radius: ${theme.border.radius.small}px;
    border-bottom-left-radius: ${theme.border.radius.small}px;
  }

  &:last-child {
    border-top-right-radius: ${theme.border.radius.small}px;
    border-bottom-right-radius: ${theme.border.radius.small}px;
  }
`,
);

export const StyledRadioInput = styled.input.attrs({ type: 'radio' })`
  display: none;
`;
