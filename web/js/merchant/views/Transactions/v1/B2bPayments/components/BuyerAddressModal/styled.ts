import styled from 'styled-components';

export const FormHeader = styled.div`
  padding-bottom: ${({ theme }) => theme.spacing[7]}px;
  border-bottom: 1px solid ${({ theme }) => theme.colors.surface.border.gray.muted};
`;

export const SupportWarning = styled.div`
  text-align: center;
  margin: 0 ${({ theme }) => `-${theme.spacing[8] - 2}px -${theme.spacing[8] - 2}px`};
  padding: ${({ theme }) => theme.spacing[4]}px 0;
  background: ${({ theme }) => theme.colors.feedback.background.notice.subtle};
`;
