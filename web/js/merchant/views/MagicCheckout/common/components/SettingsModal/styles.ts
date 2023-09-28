import styled, { css } from 'styled-components';
import line from 'assets/line.svg';

export const ModalItem = styled.div<{ isDisabled: boolean }>`
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 60px;
  border-bottom: ${({ theme }) => `1px dashed ${theme.colors.surface.border.normal.lowContrast};`}
    ${({ isDisabled }) =>
      isDisabled &&
      css`
        opacity: 0.5;
        pointer-events: none;
        color: #171a3c;
      `};
  .in-entity-text {
    font-size: 12px;
    margin-right: 15px;
  }

  label {
    padding: 15px;
    width: 100%;
    cursor: pointer;
    margin-bottom: 0;
  }
`;

export const ModalItems = styled.div(
  ({ theme }) => `
    border: 1px solid ${theme.colors.surface.border.normal.lowContrast};
    padding-left: 15px;
    height: 340px;
    margin-top ${theme.spacing[2]}px;
    overflow-y: auto;
`,
);

export const CheckboxWrapper = styled.div(
  ({ theme }) => `
  cursor: pointer;
	display: flex;
	align-items: center;
	flex-grow: 1;
  .modal-checkbox {
    appearance: none;
    cursor: pointer;
    min-width: 20px;
    margin: 0;
    min-height: 20px;
    border: 1px solid ${theme.colors.surface.border.normal.lowContrast};
    border-radius: 2px;
    &::after {
      font-family: merchant-icons;
      content: "\f125";
      color: ${theme.colors.surface.background.level2.lowContrast};
      display: none;
      position: relative;
      top: 2px;
      left: 2px;
      font-size: 14px;
    }
    &:checked {
      border-color: ${theme.colors.brand.primary[500]};
      background: ${theme.colors.brand.primary[500]};
      &::after {
        display: block;
      }
    }
    &:indeterminate {
      border-color: ${theme.colors.brand.primary[500]};
      background: ${theme.colors.brand.primary[500]};
      &::after {
        left: 4px;
        top: 0;
        content: "";
        display: block;
        background-image: url(${line});
        width: 10px;
        height: 10px;
      }
    }
  }
  
`,
);
