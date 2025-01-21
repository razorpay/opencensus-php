import React from 'react';
import { Box, BoxProps } from '@razorpay/blade/components';

import imagePlaceholder from '@apps/digital-bills/src/assets/icons/image-placeholder.svg';

type AvatarProps = {
  imageSrc: string;
  imageAlt: string;
  boxSize?: BoxProps['width'];
};
const Avatar = ({ imageSrc, imageAlt, boxSize = '75px' }: AvatarProps): React.ReactElement => {
  return (
    <Box width={boxSize} justifyContent="center" alignItems="center" display="flex">
      <img
        width="100%"
        height="100%"
        alt={imageAlt}
        src={imageSrc || imagePlaceholder}
        style={{ borderRadius: '50%' }}
      />
    </Box>
  );
};

export default Avatar;
