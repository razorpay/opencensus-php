import styled from 'styled-components';

export const ProductHeading = styled.div`
  margin: 0 16px 2px;
  height: 22px;
  font-weight: 600;
  font-size: 10px;
  line-height: 22px;
  text-transform: uppercase;
  color: #dfe0e2;
`;

export const Toggler = styled.button`
  display: flex;
  align-items: center;
  color: #80a5ff;
  font-weight: 600;
  font-size: 10px;
  height: 32px;
  margin-left: 40px;
  cursor: pointer;
  text-transform: uppercase;
  background-color: transparent;
  border: none;
  padding: 0;
`;

export const Items = styled.div`
  display: flex;
  flex-direction: column;
  gap: 2px;
`;

export const ShowMoreWrapper = styled.div(
  ({ theme }) => `
  cursor: pointer;
  text-transform: uppercase;
  padding-left: ${theme.spacing[10]}px;
  padding-top: ${theme.spacing[2]}px;
`,
);
