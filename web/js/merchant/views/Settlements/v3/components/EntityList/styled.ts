import styled from 'styled-components';

export const StyledTd = styled.td`
  p {
    display: inline-block;
  }
  .ClipboardCustom {
    margin-left: ${({ theme }) => `${theme.spacing[2]}px`};
    cursor: pointer;
  }
`;

export const StyledSpinner = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;

  P {
    margin-bottom: ${({ theme }) => `${theme.spacing[4]}px`};
  }
`;

export const ColumnHeader = styled.th`
  background-color: #f8f9fb;
  border: none;
`;
