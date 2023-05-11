import styled from 'styled-components';

export const DownloadsWrapper = styled.div(
  () => `
  background: #ffffff;
`,
);

export const ControlPanel = styled.div(
  ({ theme }) => `
  display: flex;
  align-items:center;
  justify-content: flex-end;
  padding: ${theme.spacing[4]}px;
  @media (max-width: ${theme.breakpoints.s}px) {
    flex-direction: column;
    & button {
      width: 100%;
    };
    & span  {
      margin-left: 0;
      width: 100%;
      margin-top: ${theme.spacing[4]}px;
    };
  }
`,
);

// TODO: remove style for label from here when
//       this (https://github.com/razorpay/blade/issues/1103)
//       is resolved and version is added.
export const DropdownWrapper = styled.span(
  ({ theme }) => `
    margin-left: ${theme.spacing[4]}px;
    min-width: 190px;
    label {
      display: none;
    }
  `,
);
