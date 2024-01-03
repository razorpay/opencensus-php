import React from 'react';
import { Box } from '@razorpay/blade/components';

import { ThumbnailItem } from './styles';

type Thumbnails = {
  thumbnails: string[];
  onClick: (index: number) => void;
};

const Thumbnails = ({ thumbnails, onClick }: Thumbnails): JSX.Element => {
  return (
    <Box
      maxWidth="max-content"
      marginRight="spacing.4"
      display="flex"
      flexDirection="column"
      flex="0 0 25%"
    >
      {thumbnails.map((image, index) => (
        <ThumbnailItem key={`${image}-${index}`} onClick={() => onClick(index)}>
          <img src={image} alt="product thumbnail image" />
        </ThumbnailItem>
      ))}
    </Box>
  );
};

export default Thumbnails;
