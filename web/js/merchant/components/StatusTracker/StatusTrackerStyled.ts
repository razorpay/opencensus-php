import styled from 'styled-components';

const BackgroundImage = styled.div<any>`
  background-image: ${({ rightIllustration }) => `url(${rightIllustration})`};
  background-repeat: no-repeat;
  width: 400px;
  background-position: right top;
`;
const TimelineStatus = styled.div<any>`
  border-left: ${({ index, stepsLength }) =>
    index === stepsLength - 1 ? 0 : '1px solid #162F561A'};
`;
const TimelineIcon = styled.div<any>`
  background-color: ${({ colorStatus }) => colorStatus};
`;
const H5 = styled.h5<any>`
  font-weight: ${({ isActive, stepsLength }) => (isActive || stepsLength === 1 ? 'bolder' : '')};
  color: ${({ isActive, stepsLength }) => (isActive || stepsLength === 1 ? '#000' : '#162F568A')};
`;

export { BackgroundImage, TimelineStatus, TimelineIcon, H5 };
