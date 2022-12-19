import styled from 'styled-components';

export const ActivationContainer = styled.div`
  width: 100%;
  position: relative;
  background: #315bc1;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 12px 16px;
  gap: 2px;
`;

export const ActivationLink = styled.div`
  display: flex;
  height: 21px;
  width: 100%;
  align-items: center;
  justify-content: space-between;
`;

export const Typo = styled.span`
  color: #ffffff;
  font-size: ${({ size }) => size}px;
  font-weight: ${({ weight }) => weight}px;
`;

export const ActivationStatus = styled.div`
  display: flex;
  color: #ffffff;
  gap: 8px;
  align-items: center;
`;

export const Icon = styled.i`
  cursor: pointer;
  color: #ffffff;
  height: 12px;
`;

export const ProgressBarContainer = styled.div`
  .progress {
    height: 4px;
    border-radius: 0;
    background-color: rgba(255, 255, 255, 0.3);
    margin-bottom: 0;
    .progress-bar {
      background-color: rgba(255, 255, 255, 0.6);
    }
  }
`;
