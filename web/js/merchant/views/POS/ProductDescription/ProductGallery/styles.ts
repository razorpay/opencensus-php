import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ThumbnailItem = styled.div(
  ({ theme }: { theme: Theme }) => `
    overflow: hidden;
    max-height: 180px;
    margin-bottom: ${theme.spacing[4]}px;
    border-radius: ${theme.border.radius.large}px;
    cursor: pointer;
    &:last-child{
        margin-bottom: ${theme.spacing[0]}px;
    }
    > img {
      height: 100%;
      width: 100%;
      object-fit: cover;
    }
  `,
);

export const SelectedImageContainer = styled.div(
  ({ theme, isCarousel = false }: { theme: Theme; isCarousel?: boolean }) => `
    overflow: hidden;
    display: flex;
    justify-content: center;
    border-radius: ${isCarousel ? '0' : theme.border.radius.large}px;
    margin-left: ${theme.spacing[1]}px;
    min-height: 300px;
    > img {
      height: 100%;
      width: 100%;
      object-fit: cover;
    }
  `,
);

export const ProductHeader = styled.section`
  display: flex;
  width: 100%;
  max-width: 1200px;
`;
