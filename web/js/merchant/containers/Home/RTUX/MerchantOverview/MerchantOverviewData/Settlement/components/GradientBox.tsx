import styled from 'styled-components';

const GradientBox = styled.div`
  border-radius: ${({ theme }) => `${theme.spacing[3]}px`};
  background: linear-gradient(
    90deg,
    #fee4e2 -2.86%,
    #fff5f5 46.32%,
    rgba(235, 255, 247, 0) 102.53%
  );
`;

export default GradientBox;
