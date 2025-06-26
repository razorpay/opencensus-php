import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';
import React from 'react';

export const CardContainer = styled.div`
  position: relative;
  width: 100%;
  outline: none;
`;

export const CardInner = styled.div<{ flipped: boolean }>`
  width: 100%;
  min-height: 100%;
  text-align: center;
  transition: transform 0.8s ease-in-out;
  transform-style: preserve-3d;
  border-radius: 24px;
  position: relative;
  transform: ${({ flipped }) => (flipped ? 'rotateY(180deg)' : 'none')};
  display: flex;
`;

const StyledCardFace = styled.div<{ back?: boolean }>`
  ${({ theme }: { theme: Theme }) => `
    width: 100%;
    border-radius: ${theme.border.radius.xlarge}px; // change this
    display: flex;
    flex-direction: column;
    align-items: stretch;
    justify-content: stretch;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    background: white;
    border: 1px solid ${theme.colors.surface.border.gray.subtle};
  `}
  ${({ back, theme }: { back?: boolean; theme: Theme }) =>
    back &&
    `
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      transform: rotateY(180deg);
      justify-content: center;
      align-items: center;
      z-index: 2;
      border: 1px solid ${theme.colors.surface.border.primary.normal};
    `}
`;

// workaround to enforce required props for styled component
type CardFaceProps = {
  back?: boolean;
  children?: React.ReactNode;
};

export const CardFace: React.FC<CardFaceProps> = (props) => {
  return <StyledCardFace {...props} />;
};

export const Image = styled.img`
  width: 100%;
  border-radius: ${({ theme }: { theme: Theme }) => theme.border.radius.xlarge}px;
  background-color: ${({ backgroundColor }: { backgroundColor: string }) =>
    backgroundColor || 'transparent'};
`;

export const BackroundWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  // background: ${theme.colors.surface.background.gray.moderate};
  background: #CBD5E2;
  padding: ${theme.spacing[7]}px;
  border-radius: ${theme.border.radius.small}px;
`,
);

export const ProductNameWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: ${theme.colors.surface.background.primary.subtle};
  border-radius: ${theme.border.radius.medium}px;
  padding: ${theme.spacing[2]}px;
  min-width: 50px;
  padding-left: ${theme.spacing[3]}px;
  padding-right: ${theme.spacing[3]}px;
`,
);

export const SectionCardWrapper = styled.div<{
  backgroundColor?: string;
  marginTop?: boolean;
  marginBottom?: boolean;
  isMobile?: boolean;
}>(
  ({
    theme,
    backgroundColor,
    marginTop,
    marginBottom,
    isMobile,
  }: {
    theme: Theme;
    backgroundColor?: string;
    marginTop?: boolean;
    marginBottom?: boolean;
    isMobile?: boolean;
  }) => `
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 0 auto;
  margin-top: ${marginTop ? `${theme.spacing[6]}px` : '0'};
  margin-bottom: ${marginBottom ? `${theme.spacing[6]}px` : '0'};
	padding: ${theme.spacing[11]}px;
  padding-inline: ${isMobile ? theme.spacing[6] : theme.spacing[11]}px;
  border-radius: ${theme.border.radius.xlarge}px;
  background-color: ${backgroundColor || '#F1F5FA'};
`,
);
