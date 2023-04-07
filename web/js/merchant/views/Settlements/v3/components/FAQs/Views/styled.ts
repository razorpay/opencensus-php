import styled from 'styled-components';

export const StyledFaqContent = styled.div`
  display: flex;
  flex-direction: column;
  gap: 8px;
`;

export const TextLink = styled.div`
  p {
    display: inline;
  }
`;

export const VideoPlayer = styled.iframe<any>`
  height: 170px;
  width: 300px;
  border: none;
  margin-top: 10px;
  @media screen and (max-width: 768px) {
    width: 100%;
    max-width: 300px;
  }
`;
