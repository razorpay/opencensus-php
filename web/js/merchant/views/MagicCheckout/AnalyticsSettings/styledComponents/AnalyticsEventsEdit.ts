import styled from 'styled-components';

export const EventsToggleContainer = styled.div`
  margin: 22px 0 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
  width: 360px;
`;
export const ToggleWrapper = styled.div`
  display: flex;
  justify-content: space-between;

  .setting-toggle {
    align-items: center;
    gap: 10px;
  }

  .setting-label > i {
    position: relative;
    top: 1px;
    left: 8px;
  }
`;
export const SaveEventsCtaContainer = styled.div`
  text-align: right;

  button:disabled {
    cursor: not-allowed;
  }
`;
