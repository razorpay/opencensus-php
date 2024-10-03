import React from 'react';
import { Box } from '@razorpay/blade/components';

type ImagePreviewProps = {
  file?: File | null;
  src?: string;
};

const ImagePreview = ({ file, src }: ImagePreviewProps): JSX.Element | null => {
  if (!file && !src) {
    return null;
  }

  return (
    <Box
      display="inline-flex"
      borderWidth="thin"
      borderColor="surface.border.gray.muted"
      width="72px"
      height="72px"
    >
      {file && <img src={URL.createObjectURL(file)} alt="Preview" />}
      {!file && src && <img src={src} alt="Preview" />}
    </Box>
  );
};

export default ImagePreview;
