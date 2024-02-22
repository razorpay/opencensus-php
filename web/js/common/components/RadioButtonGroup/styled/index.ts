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
    ${
      checked
        ? theme.colors.surface.text.normal.lowContrast
        : theme.colors.surface.text.subdued.lowContrast
    };
  border: 1px solid transparent;
  border-color: 
    ${checked ? theme.colors.brand.primary[500] : theme.colors.surface.border.normal.lowContrast};
  cursor: ${disabled ? 'not-allowed' : 'pointer'};
  user-select: none;

  &:hover {
    color: ${
      disabled
        ? theme.colors.surface.text.subdued.lowContrast
        : theme.colors.surface.text.normal.lowContrast
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
