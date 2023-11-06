import React from 'react';
import styled from 'styled-components';
import { Spinner, Text, Theme } from '@razorpay/blade/components';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import { ProductsWrapper } from './Storefront/styled';

const _Modal = ({ className = '', ...restProps }) => (
  <Modal unmodifiedClassName={className} {...restProps} />
);

export const TemplateSelectionModal = styled(_Modal)`
  z-index: 9999;
  width: 100vw;
  height: 100vh;
  overflow: scroll;
  left: 0px;
  top: 0px;
  transform: none;
`;

export const TemplateSelectionModalContent = styled(ModalContent)(
  ({ theme }: { theme: Theme }) => `
  .pp-template-section-carousel {
    height: 350px;
    box-shadow: 0px 9.45917px 17.0265px rgba(21, 45, 75, 0.1),
      0px 0px 0.945917px rgba(21, 45, 75, 0.2);
      border-radius:4px;

    .carousel, .slider-wrapper{
      height:100%;
      border-radius:4px;
    }

    img, .slider{
      height:100%;
    }

    .control-dots {
      .dot {
        width: 64px !important;
        height: 4px !important;
        border-radius: 32px !important;
        background: rgba(121, 135, 156, 0.09);
        @media screen and (max-width: 600px) {
          width: 45px !important;
        }
        @media screen and (max-width: 450px) {
          width: 40px !important;
        }
        &.selected,
        &:hover {
          background: ${theme.colors.brand.gray[500].lowContrast};
        }
      }
    }
  }
`,
);

export const PurpleBackground = styled.div(
  ({ theme }) => `
  background: linear-gradient(268.89deg, #2A86F3 0%, #1566F1 19.72%, #0649ED 47.06%, #0033E5 75.09%, #0649ED 100%);
  height: 200px;
  position: relative;

  .pp-title-wrapper {
    position: absolute;
    top: 25%;
    left: 50%;
    transform: translate(-50%, 0%);
    display: flex;
    align-items: center;
    height: ${theme.spacing[10]}px;

    h3 {
      padding-left: ${theme.spacing[3]}px;
      font-size: 1.375rem; // blade's size
    }
  }
  .close-icon {
    position: absolute;
    top: ${theme.spacing[3]}px;
    right: ${theme.spacing[7]}px;
    cursor: pointer;
    @media screen and (max-width: 1000px){
      top: ${theme.spacing[4]}px;
    }
  }
  @media screen and (max-width: 1000px){
    height: ${theme.spacing[10]}px;
    .pp-title-wrapper {
      position: relative;
      justify-content: center;
      transform: none;
      top: 0%;
      left: 0%;
    }
   }
`,
);

export const TemplateSelectionWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: space-around;
  position: relative;
  top: -50px;

  .image-wrapper {
    position: relative;
    height:350px;
  }

  .left-container,
  .right-container {
    width: 600px;
    @media screen and (max-width: 1500px) {
      width: 500px;
    }
    @media screen and (max-width: 1200px) {
      max-width: 500px;
      width: 100%;
    }
  }

  img {
    box-shadow: 0px 9.45917px 17.0265px rgba(21, 45, 75, 0.1),
      0px 0px 0.945917px rgba(21, 45, 75, 0.2);
    border-radius: ${theme.border.radius.medium}px;
    max-width: 600px;
  }

  .right-container img {
    width: 100%;
    height:100%;
  }

  h4 {
    margin-top: ${theme.spacing[10]}px;
    margin-bottom: ${theme.spacing[3]}px;
    @media screen and (max-width: 1000px) {
      margin-top: ${theme.spacing[3]}px;
    }
  }

  button {
    margin-top: ${theme.spacing[6]}px;
    @media screen and (max-width: 1000px) {
      margin-top: ${theme.spacing[3]}px;
      margin-bottom: ${theme.spacing[8]}px;
    }
  }

  @media screen and (max-width: 1000px) {
    flex-direction: column;
    align-items: center;
    top: 0px;
    padding: ${theme.spacing[7]}px;
  }
`,
);

export const IconWrapper = styled.span`
  background: rgba(121, 135, 156, 0.32);
  mix-blend-mode: normal;
  border-radius: 20px;
  display: flex;
  position: relative;
  &.template {
    padding: 4px;
    padding-bottom: 2px;
    color: white;
  }
  &.storefront-options {
    background: none;
    padding: 4px;
    border: 1px solid #dfe3e9;
    border-radius: 2px;
    cursor: pointer;
  }
`;

export const InlineWrapper = styled.span`
  display: inline-flex;
  align-items: center;
  & > span {
    padding-left: 4px;
    padding-right: 14px;
  }
`;

export const FeatureWrapper = styled.span`
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
`;

export const Tag = styled.div`
  font-weight: 700;
  font-size: 9px;
  line-height: 20px;
  letter-spacing: 0.02em;
  color: #ffffff;
  text-transform: uppercase;

  position: absolute;
  bottom: 5px;
  right: 5px;
  background: #a3afbf;
  border-radius: 3px;
  padding: 2px 6px;
  display: flex;
  align-items: center;
  height: 18px;
  @media screen and (max-width: 500px) {
    font-size: 7px;
  }
  .mt-2 {
    margin-top: 2px;
  }
  .mr-2 {
    margin-right: 2px;
  }
`;

export const NewLabel = styled.span<{ marginLeft?: string }>(
  ({ theme, marginLeft }) => `
  background: #01b358;
  border-radius: 3px;
  padding: 2px 6px;
  font-weight: ${theme.typography.fonts.weight.bold};
  font-size: 11px;
  line-height: 20px;
  letter-spacing: 0.02em;
  color: white;
  text-transform: uppercase;
  margin-left: ${marginLeft ? marginLeft : 'unset'};
  display: inline-flex;
  align-items: center;
  height: 18px;
  span {
    padding-left: 2.5px;
  }
  .mt-2 {
    margin-top: 2px;
  }
  .mr-2 {
    margin-right: 2px;
  }
`,
);

export const Shimmer = styled.div(
  ({
    theme,
    height,
    width,
    maxWidth,
  }: {
    theme: Theme;
    height?: string;
    width?: string;
    maxWidth?: string;
  }) => `
  height: ${height ? height : `${theme.spacing[5]}px`};
  width: ${width ? width : `${theme.spacing[5]}px`};
  max-width: ${maxWidth ? maxWidth : 'unset'};
  background: rgba(255, 255, 255, 0.3);
  background-image: linear-gradient(
    90deg,
    rgba(204, 204, 204, 0.1) 0%,
    #cccccc 50%,
    /* #cccccc 54%, */ rgba(204, 204, 204, 0.1) 100%
  );
  opacity: 0.3;
  border-radius: ${theme.border.radius.small}px;
  background-size: 800px 100px;
  animation: placeHolderShimmer 1.2s forwards infinite linear;

  @keyframes placeHolderShimmer {
    0% {
      background-position: -400px 0;
    }
    100% {
      background-position: 400px 0;
    }
  }
`,
);

const ShimmerGroup = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  flex-direction: column;
  row-gap: ${theme.spacing[5]}px;
  padding-top: ${theme.spacing[5]}px;
  padding-bottom: ${theme.spacing[3]}px;
`,
);

const ShimmerItem = styled.div(
  ({ theme }) => `
  display: flex;
  gap: ${theme.spacing[3]}px;
  align-items: center;
  justify-content: space-between;
`,
);

const ShimmerWrapper = styled.div<{ justifyContent?: string }>(
  ({ theme, justifyContent }) => `
  display: flex;
  gap: ${theme.spacing[3]}px;
  align-items: center;
  flex: 1;
  justify-content: ${justifyContent ? justifyContent : 'initial'};
`,
);

const LoadingTextWrapper = styled.div(
  ({ theme }) => `
  display: flex;
  gap: ${theme.spacing[2]}px;
`,
);

const array = Array(4).fill(1);
export const ProductsSkeleton = (): React.ReactElement => {
  return (
    <ProductsWrapper className="product-section">
      <LoadingTextWrapper>
        <Spinner accessibilityLabel="Loading products" />
        <Text type="normal" variant="body" size="medium" weight="regular" contrast="low">
          Please wait while we load your products...
        </Text>
      </LoadingTextWrapper>
      <ShimmerGroup>
        {array.map((item, i) => (
          <ShimmerItem key={i}>
            <ShimmerWrapper>
              <Shimmer height="28px" width="28px" />
              <Shimmer height="16px" width="100%" maxWidth="200px" />
            </ShimmerWrapper>
            <ShimmerWrapper justifyContent="flex-end">
              <Shimmer height="12px" width="100%" maxWidth="44px" />
              <Shimmer height="28px" width="28px" />
            </ShimmerWrapper>
          </ShimmerItem>
        ))}
        <Shimmer height="36px" width="140px" />
      </ShimmerGroup>
    </ProductsWrapper>
  );
};

export const SelectProductSkeleton = (): React.ReactElement => {
  return (
    <ShimmerGroup data-testid="select-product-skeleton">
      {array.map((item, i) => (
        <ShimmerItem key={i}>
          <ShimmerWrapper>
            <Shimmer height="28px" width="28px" />
            <Shimmer height="16px" width="100%" />
          </ShimmerWrapper>
        </ShimmerItem>
      ))}
      <Shimmer height="36px" width="140px" />
    </ShimmerGroup>
  );
};

export const TemplateSelectionHeading = styled.h4(
  ({ theme }: { theme: Theme }) => `
  color: hsla(217,56%,17%,1);
  font-family: Lato,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";
  font-size: 1.25rem;
  font-weight: ${theme.typography.fonts.weight.bold};
  font-style: normal;
  -webkit-text-decoration-line: none;
  text-decoration-line: none;
  line-height: 1.75rem;
  margin: 0;
  padding: 0;
`,
);
