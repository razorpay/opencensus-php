import styled from 'styled-components';

export const SchedulesWrapper = styled.div(
  () => `
  background: white;
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
    }
    & span  {
      margin-left: 0px !important;
      width: 100%;
      margin-top: ${theme.spacing[4]}px;
    }
  }
`,
);

export const SchedulesTableIconControlContainer = styled.div`
  width: 100%;
  display: flex;
  justify-content: center;
  button:first-of-type {
    margin-right: 15px;
  }
  button:last-of-type {
    margin-left: 15px;
  }
`;

export const DropdownWrapper = styled.span(
  ({ theme }) => `
    margin-left: ${theme.spacing[4]}px;
    min-width: 190px;
    label {
      display: none;
    }
  `,
);
