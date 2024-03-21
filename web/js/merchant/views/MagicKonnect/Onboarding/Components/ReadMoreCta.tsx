import React from 'react';

import { Button } from '@razorpay/blade/components';

const ReadMoreCta = ({ setSlideState }) => {
  const onReadMoreClick = () => {
    setSlideState({
      activeSlide: 'banner-card',
      activeSlideNo: 1,
    });
  };
  return (
    <Button variant="secondary" onClick={onReadMoreClick}>
      Read more
    </Button>
  );
};

export default ReadMoreCta;
