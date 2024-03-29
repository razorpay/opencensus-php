import styled from 'styled-components';

export const Icon = styled.div<{ isDisabled?: boolean }>`
  display: inline-flex;
  padding: ${({ theme }) => theme.spacing[3]}px;
  border-radius: 50%;
  background-color: ${({ theme, isDisabled }) =>
    isDisabled
      ? theme.colors.surface.text.gray.subtle
      : theme.colors.feedback.background.notice.subtle};
`;
