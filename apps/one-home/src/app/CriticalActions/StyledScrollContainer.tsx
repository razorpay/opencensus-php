import styled from 'styled-components';

const StyledScrollContainer = styled.div<{
  isMobile: boolean;
  isScrolledLeft: boolean;
  isScrolledRight: boolean;
}>`
  display: flex;
  overflow-x: auto;
  overflow-y: hidden;
  white-space: nowrap;
  gap: ${({ isMobile }) => (isMobile ? '8px' : '16px')};
  position: relative;
  max-width: 100%;
  min-width: 0;

  /* Hide scrollbar */
  &::-webkit-scrollbar {
    display: none;
  }

  -ms-overflow-style: none; /* Hide scrollbar for IE/Edge */

  /* Apply gradient shadows dynamically */
  mask-image: ${({ isScrolledLeft, isScrolledRight }) =>
    isScrolledLeft && isScrolledRight
      ? 'linear-gradient(to right, rgba(241, 245, 250, 0) 0%, #F1F5FA 10%, #F1F5FA 90%, rgba(241, 245, 250, 0) 100%)'
      : isScrolledLeft
      ? 'linear-gradient(to right, rgba(241, 245, 250, 0) 0%, #F1F5FA 10%, #F1F5FA 100%)'
      : isScrolledRight
      ? 'linear-gradient(to right, #F1F5FA 0%, #F1F5FA 90%, rgba(241, 245, 250, 0) 100%)'
      : 'none'};
`;

export default StyledScrollContainer;
