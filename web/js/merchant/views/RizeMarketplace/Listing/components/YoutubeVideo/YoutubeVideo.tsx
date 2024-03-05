import React from 'react';
import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

import { YoutubeVideoProps } from './types';

const StyledIframe = styled.iframe(
  ({ theme }: { theme: Theme }) => `
  aspect-ratio: 16 / 9;
  border-radius: ${theme.border.radius.large}px;
  border: none;
`,
);

const extractYoutubeVideoId = (url: string): string | null => {
  const youtubeUrl = new URL(url);
  if (youtubeUrl.host === 'youtu.be') return youtubeUrl.pathname.slice(1);
  return youtubeUrl.searchParams.get('v');
};

const YoutubeVideo = ({ src, title, className }: YoutubeVideoProps): JSX.Element | null => {
  const videoId = extractYoutubeVideoId(src);
  if (!videoId) return null;

  return (
    <StyledIframe
      className={className}
      src={`https://www.youtube.com/embed/${videoId}`}
      title={title}
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
      allowFullScreen
    />
  );
};

export default YoutubeVideo;
