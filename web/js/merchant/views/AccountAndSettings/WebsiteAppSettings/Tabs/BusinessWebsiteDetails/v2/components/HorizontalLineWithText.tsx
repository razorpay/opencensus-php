import styled from 'styled-components';

const HorizontalLineWithText = styled.div`
  display: flex;
  align-items: center;
  text-align: center;
  width: 75%;

  &::before,
  &::after {
    content: '';
    flex: 1;
    border-top: 1px solid #b1c1d2;
  }

  &::before {
    margin-right: 6px;
  }

  &::after {
    margin-left: 6px;
  }

  span {
    padding: 0 6px;
    font-weight: 450;
    font-style: italic;
    color: #0f78ad;
  }
`;

export default HorizontalLineWithText;
