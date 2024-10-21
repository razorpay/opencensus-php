import React from 'react';

import { Link, Theme } from '@razorpay/blade/components';

import styled from 'styled-components';

type StyledProps = { theme: Theme };

export const HeadingWrapper = styled.div`
  display: flex;
  width: 100%;
  padding: ${({ theme }: { theme: Theme }) => theme.spacing[6]}px;
  padding-bottom: 0px;
  align-items: flex-start;
  flex-direction: column;
  align-items: flex-start
  align-self: stretch;
  marging-bottom: ${({ theme }: { theme: Theme }) => theme.spacing[6]}px;
`;

export const EditModalHeadingWrapper = styled.div`
  display: flex;
  width: 100%;
  padding: ${({ theme }: { theme: Theme }) => theme.spacing[6]}px;
  padding-bottom: 0px;
  align-items: center;
  gap: ${({ theme }: { theme: Theme }) => theme.spacing[3]}px;
  align-self: stretch;
  marging-bottom: ${({ theme }: { theme: Theme }) => theme.spacing[6]}px;
`;

export const ContentWrapper = styled.div`
  display: flex;
  padding: ${({ theme }: { theme: Theme }) => theme.spacing[6]}px;
  justify-content: center;
  align-items: center;
  gap: ${({ theme }: { theme: Theme }) => theme.spacing[7]}px;
`;

export const SingleContentWrapper = styled.div(
  ({ theme, isSelected }: { theme: Theme; isSelected: boolean }) => `
  cursor: pointer;
  border: ${isSelected ? theme.border.width.thicker : theme.border.width.thin}px solid ${
    isSelected
      ? theme.colors.interactive.border.positive.default
      : theme.colors.surface.border.gray.subtle
  };
  border-radius: ${theme.border.radius.large}px;
  width: 272px;
  height: 243px; 
  padding:  ${theme.spacing[7]}px 0px 0px ${theme.spacing[8]}px;
  position: relative;
`,
);

export const ImageWrapper = styled.img`
  width: 238px;
  height: 150px;
  border-bottom-right-radius: 8px;
  margin-top: 24px;
`;

export const FooterWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
    display: flex;
    padding: 0px ${theme.spacing[6]}px ${theme.spacing[6]}px ${theme.spacing[6]}px;
    justify-content: flex-end;
    align-items: center;
    gap: ${theme.spacing[5]}px;
    align-self: stretch;
`,
);

export const LeftContentWrapper = styled.div`
  display: flex;
  width: 412px;
  padding: 29.5px 26px;
  justify-content: flex-start;
  align-items: center;
  flex-direction: column;
  gap: 32px;
`;

export const RightContentWrapper = styled.div`
  display: flex;
  padding: 39.5px 0px 0px 41px;
  justify-content: flex-end;
  align-items: center;
  position: relative;
  border-radius: 8px;
  border: 1px solid ${({ theme }: { theme: Theme }) => theme.colors.surface.border.gray.muted};
  width: 412px;
  height: 283px;
  overflow: hidden;
 background: ${({ theme }: StyledProps) => theme.colors.surface.background.gray.moderate};
}
`;

export const IframeWrapper = styled.div`
  position: absolute;
  top: 330px;
  height: 470px;
  width: 470px;
  left: 330px;
  transform: translateY(-35%) scale(2.2, 2.2);
}
`;

export const ImageSelectorWrapper = styled.div(
  ({ theme }) => `
  border: 1px dashed ${theme.colors.interactive.border.gray.default};
  border-radius: ${theme.border.radius.small}px;
  height: 64px;
  padding: ${theme.spacing[3]}px ${theme.spacing[5]}px;
  display: flex;
  align-items: center;
  cursor: pointer;
`,
);

export const ImageContent = styled.div(
  ({ theme }) => `
    display: flex;
    justify-content: center;
    align-items: center;
    padding-left: ${theme.spacing[4]}px;
    gap: ${theme.spacing[3]}px;
  }
`,
);

export const ImageSelector = ({ onClick }: { onClick: () => void }): React.ReactElement => {
  return (
    <ImageSelectorWrapper onClick={onClick}>
      <ImageContent>
        Drag files here or
        <Link variant="button">Upload</Link>
      </ImageContent>
    </ImageSelectorWrapper>
  );
};

export const ImageSelectedWrapper = styled.div(
  ({ theme }) => `
  border: 1px solid ${theme.colors.interactive.border.neutral.faded};
  border-radius: ${theme.border.radius.small}px;
  height: 54px;
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 8px;
  justify-content: space-between;
  background-color: ${theme.colors.interactive.background.gray.faded}
`,
);

export const TitleFrame = styled.iframe<{
  bgColor?: string;
  isDesktopPreview: boolean;
  shouldScaleToFit: boolean;
}>`
  width: ${(props) => (props.isDesktopPreview ? '1000px' : '260px')};
  height: 100%;
  pointer-events: none;
  border: 0;
  transform: ${(props) =>
    props.isDesktopPreview && props.shouldScaleToFit ? 'translate(-25%, -25%) scale(0.5)' : ''};
  background: ${(props) => props.bgColor || 'none'};
`;
