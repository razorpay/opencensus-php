import styled from 'styled-components';

export const SubText = styled.span(
  ({ theme }) => `
  display: flex;
  align-items: center;
  margin-top: ${theme.spacing[2]}px;

  img {
    margin-right: ${theme.spacing[2]}px;
  }
`,
);

export const Seperator = styled.span(
  ({ theme }) => `
  margin: 0 ${theme.spacing[2]}px;
`,
);

export const ConfigItemWrapper = styled.div(
  ({ theme }) => `
  width: 100%;
  background-color: ${theme.colors.surface.background.level2.lowContrast};
  padding: ${theme.spacing[4]}px;
  border: 1px solid ${theme.colors.surface.border.normal.lowContrast};
  border-radius: ${theme.border.radius.small}px;

  .seperator {
    margin: 0 5px;
  }

  &:not(:last-child) {
    margin-bottom: ${theme.spacing[5]}px
  }

  svg > path {
    fill: ${theme.colors.brand.primary[500]}
  }
 
`,
);

export const ModalHeader = styled.div`
  padding: 25px;
  display: flex;
  justify-content: space-between;
`;

export const ModalBody = styled.div(
  ({ theme }) => `
  padding: 0 ${theme.spacing[6]}px;
  min-height: 440px;
  max-height: 440px;
  overflow-y: scroll;

  &::-webkit-scrollbar {
    width: 5px;
  }

  &::-webkit-scrollbar-track {
    background: #F6F6F7;
  }

  &::-webkit-scrollbar-thumb {
    background-color: ${theme.colors.brand.primary[500]};
  }
`,
);

export const ActionItem = styled.div<{ borderTop?: string }>(
  ({ theme, borderTop }) => `
  border-top: ${borderTop ?? `1px dashed ${theme.colors.surface.border.normal.lowContrast}`};
  display: flex;
  justify-content: space-between;
  padding-top: ${theme.spacing[6]}px;
  align-items: flex-start;

  .required {
    margin-left: ${theme.spacing[1]}px;
    color: ${theme.colors.feedback.text.negative.lowContrast}
  }

  .zones-list {
    display: flex;
    flex-wrap: wrap;
  }

`,
);

export const ActionSelector = styled.div<{ forCategory?: boolean }>(
  ({ theme, forCategory }) => `
  background-color: ${forCategory ? 'transparent' : '#FAFCFF'};
  padding: ${theme.spacing[4]}px ${theme.spacing[6]}px;
  flex: 1;

  .disabled-item {
    opacity: .3;
    pointer-events: none;
  }
`,
);

export const SelectorItem = styled.div(
  () => `
  display: flex;
  justify-content: space-between;
  transition: opacity .2s ease;
  align-items: center;
`,
);

export const Label = styled.p(
  () => `
  cursor: pointer;
  padding: 10px;
  width: 250px;
  margin-bottom: 0;
`,
);

export const ZoneLabel = styled.div(
  ({ theme }) => `
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex: 1;
  cursor: pointer;
  margin-left: ${theme.spacing[1]}px;

  .chevron-icon-up {
    transform: rotate(-180deg)
  }
`,
);
