import React from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import { makeSize } from '@razorpay/blade/utils';
import styled from 'styled-components';

const StyledLink = styled.a`
  position: relative;
  border: 0;
  outline: 0;
  margin: -1px 0 -1px -1px;
  padding: 0;
  width: ${makeSize(285)};
  min-height: ${makeSize(178)};
  border-radius: ${({ theme }) => makeSize(theme.border.radius.medium)};
  overflow: hidden;
`;

const StyledThumbnail = styled.div`
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
    url('${(props) => props.src}') no-repeat center center/cover;
  width: 285px;
  height: 100%;
  min-height: 178px;
`;

const PlayIcon = styled.i`
  position: absolute;
  top: 50%;
  left: 50%;
  font-size: 400%;
  margin: -24px 0 0 -24px;
  color: #fff;
  opacity: 0.7;
  width: 64px;
  height: 64px;
`;

type Props = {
  heading: string;
  thumbnail: string;
  video: string;
  title: string;
  description: string;
};
export const SetupGuide: React.FC<Props> = ({ heading, title, description, thumbnail, video }) => {
  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      <Heading size="medium">{heading}</Heading>
      <Box
        display="flex"
        gap="spacing.3"
        borderColor="surface.border.gray.muted"
        borderRadius="medium"
        borderWidth="thin"
        backgroundColor="surface.background.gray.moderate"
      >
        <StyledLink
          data-test-id="video-play-btn"
          aria-label="Go to COD setup guide video"
          href={video}
          target="_blank"
        >
          <StyledThumbnail src={thumbnail} role="presentation" />
          <PlayIcon className="i i-play-filled-circle" />
        </StyledLink>
        <Box
          display="flex"
          flexDirection="column"
          gap="spacing.3"
          maxWidth="555px"
          padding="spacing.7"
          paddingLeft="spacing.8"
        >
          <Heading size="medium" weight="semibold">
            {title}
          </Heading>
          <Text color="surface.text.gray.subtle" weight="regular">
            {description}
          </Text>
        </Box>
      </Box>
    </Box>
  );
};
