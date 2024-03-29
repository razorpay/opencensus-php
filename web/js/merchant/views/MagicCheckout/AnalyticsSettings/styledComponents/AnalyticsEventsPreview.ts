import styled from 'styled-components';

export const EventsPreviewContainer = styled.div`
  width: 360px;
  padding: 16px;
  border-radius: 4px;
  border: 1px solid #e0e8f4;
  background: #fff;
  margin-top: 22px;
`;
export const PreviewHeader = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
`;
export const EditIconContainer = styled.div`
  color: #2b83ea;
  font-weight: 600;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 6px;
`;
export const PreviewContentContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: 10px;
`;
export const Seperator = styled.hr`
  margin: 10px 0;
`;
export const PreviewContent = styled.div`
  display: flex;
  flex-direction: column;
`;

export const PreviewContentTitle = styled.p`
  color: #5a6870;
  font-weight: 500;
`;
export const PreviewContentValue = styled.p`
  color: #000;
  font-weight: 600;
  text-transform: capitalize;
`;
