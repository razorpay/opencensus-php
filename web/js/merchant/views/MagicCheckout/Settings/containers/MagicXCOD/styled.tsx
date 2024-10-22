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

export const LastSyncedBadge = styled.div`
  border-radius: 56px;
  background-color: ${({ theme }) => theme.colors.feedback.background.neutral.subtle};
  box-sizing: border-box;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  padding: 4px 8px;
  margin: 0 4px;
  font-size: 12px;
  font-weight: 500;
  color: #192839;
`;

export const RelativePositionContainer = styled.div`
  position: relative;
`;

export const SyncInProgressBadge = styled.div`
  position: absolute;
  top: 50%;
  left: 25%;
  box-shadow: 0px 8px 24px rgba(25, 40, 57, 0.12);
  border-radius: 56px;
  background-color: #edf4f7;
  border: 1px solid rgba(108, 132, 157, 0.18);
  box-sizing: border-box;
  width: 50%;
  display: flex;
  flex-direction: row;
  align-items: center;
  justify-content: center;
  padding: 8px 16px;
  text-align: left;
  font-size: 14px;
  font-weight: 500;
  color: #192839;
`;
