import styled from 'styled-components';

export const ConfigItemWrapper = styled.div(
  ({ theme }) => `
  width: 100%;
  background-color: ${theme.colors.surface.background.gray.intense};
  padding: ${theme.spacing[4]}px;
  border: 1px solid ${theme.colors.surface.border.gray.muted};
  border-radius: ${theme.border.radius.small}px;

  .seperator {
    margin: 0 5px;
  }

  &:not(:last-child) {
    margin-bottom: ${theme.spacing[5]}px
  }

  svg > path {
    fill: ${theme.colors.surface.background.primary.intense}
  }
 
`,
);

export const SummaryItemWrapper = styled.div(
  ({ theme }) => `
  width: 100%;
  background-color: ${theme.colors.surface.background.gray.intense};
  padding: ${theme.spacing[3]}px;
  border: 1px solid ${theme.colors.surface.border.gray.muted};
  border-radius: ${theme.border.radius.small}px;
`,
);

export const FormWrapper = styled.div`
  &&& {
    .Input {
      margin: 0;
    }
    .shipping-slabs-select {
      width: 175px;
      .Input-elWrapper {
        width: 175px;
      }
    }
  }
`;

export const IconButton = styled.div`
  cursor: pointer;
`;

export const ShippingMethodTableWrapper = styled.div(
  ({ theme }) => `
  padding: ${theme.spacing[3]}px ${theme.spacing[4]}px;
  border: 1px solid ${theme.colors.surface.border.gray.muted};
  border-radius: ${theme.border.radius.small}px;
  margin-top: ${theme.spacing[3]}px;
  background-color: ${theme.colors.surface.background.gray.intense};
`,
);

export const DeleteIconWrapper = styled.div(
  ({ theme }) => `
  svg > path {
    fill: ${theme.colors.feedback.icon.negative.intense};
  }

`,
);
