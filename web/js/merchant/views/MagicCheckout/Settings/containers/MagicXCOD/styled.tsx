import styled from 'styled-components';

export const WrappedTitle = styled.p`
  white-space: normal;
  word-break: break-word;
  font-weight: ${({ theme }) => theme.typography.fonts.weight.regular};
  color: ${({ theme }) => theme.colors.interactive.text.staticBlack.normal};
  margin-top: ${({ theme }) => theme.spacing[2]};
`;

export const WrappedSubTitle = styled.p`
  white-space: normal;
  word-break: break-word;
  color: ${({ theme }) => theme.colors.interactive.text.gray.muted};
  margin-top: ${({ theme }) => theme.spacing[2]};
`;
