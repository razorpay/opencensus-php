/**
 * w: width of the image
 * h: height of the image
 * range: range of container width for which the image size is applicable
 */
export const IMAGE_SCALE_MAP = {
  sm: { w: 400, h: 400, range: [0, 480] },
  md: { w: 900, h: 180, range: [481, 960] },
  lg: { w: 1200, h: 180, range: [961, 1200] },
};

/**
 * calculates the height based on the predefined aspect-ratio for a given width
 */
export function calHeightOnAspectRatio(width: number) {
  const scale = IMAGE_SCALE_MAP[calcScaleFromWidth(width)];
  return (scale.h * width) / scale.w;
}

export function calcScaleFromWidth(width: number): keyof typeof IMAGE_SCALE_MAP {
  return (
    (Object.keys(IMAGE_SCALE_MAP).find(
      (key) => width >= IMAGE_SCALE_MAP[key].range[0] && width <= IMAGE_SCALE_MAP[key].range[1],
    ) as keyof typeof IMAGE_SCALE_MAP | undefined) || 'lg'
  );
}
