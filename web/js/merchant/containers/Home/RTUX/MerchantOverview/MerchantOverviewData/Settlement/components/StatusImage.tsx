import React, { useEffect, useState } from 'react';
import styled from 'styled-components';
import { getSettlementStatusImage } from '../utils';
import { useIsSettlementHovered } from '../../store';
import { Box } from '@razorpay/blade/components';

const Image = styled.img<{ styles }>(({ styles }) => styles);

export const StatusImage = ({ status }: { status: string }) => {
  const image = getSettlementStatusImage(status);
  const [imageSource, setImageSource] = useState(`/img/rtux/${image?.name}`);
  const [isAnimating, setIsAnimating] = useState(true);
  const { isHovered } = useIsSettlementHovered();

  /**
   * To Restart gif from the beginning the image source is set to empty string
   * momentarily and then set to the actual image source
   * https://stackoverflow.com/a/48892561
   */
  useEffect(() => {
    let x;
    if (isHovered && !isAnimating) {
      setImageSource('');
      x = setTimeout(() => {
        setImageSource(`/img/rtux/${image?.name}`);
        setIsAnimating(true);
      }, 0);
    }
    return () => x && clearTimeout(x);
  }, [isHovered, isAnimating, image?.name]);

  /**
   * Everytime one of the parent containers is hovered, gif has to restart
   * it's animation. This logic helps prevent restart if gif is already
   * animating.
   */
  useEffect(() => {
    let x;
    if (isAnimating) {
      x = setTimeout(() => {
        setIsAnimating(false);
      }, image?.duration);
    }
    return () => x && clearTimeout(x);
  }, [isAnimating, image?.duration]);

  if (image === null) {
    return <Box width="108px" height="108px" />;
  }

  return <Image src={imageSource} styles={image.styles} />;
};
