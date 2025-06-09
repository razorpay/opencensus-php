import styled from 'styled-components';

export const Star = styled.span`
  font-size: 40px;
  margin-right: 8px;
  cursor: pointer;
  color: ${({ active }) => (active ? '#fde047' : '')};

  &:hover {
    color: #fde047;
  }
`;
