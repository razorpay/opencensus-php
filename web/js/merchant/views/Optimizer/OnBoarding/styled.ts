import styled from 'styled-components';

export const StateIconBackground = styled.div<{ status: string }>`
  height: ${({ theme }) => theme.spacing[5]}px;
  width: ${({ theme }) => theme.spacing[5]}px;
  border-radius: ${({ theme }) => theme.border.radius.round};
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: ${({ theme }) => theme.colors.feedback.background.positive.intense};
`;

export const SaveGatewayDiv = styled.div`
  margin: ${({ theme }) => `${theme.spacing[5]}px ${theme.spacing[3]}px`};
  padding: ${({ theme }) => theme.spacing[8]}px;
  background-color: ${({ theme }) => theme.colors.surface.background.gray.intense};
  border-radius: ${({ theme }) => theme.border.radius.large}px;
`;

export const SaveGatewayFooter = styled.div`
  position: fixed;
  bottom: 70px;
  width: 100%;
  background-color: ${({ theme }) => theme.colors.surface.background.gray.intense};
`;
