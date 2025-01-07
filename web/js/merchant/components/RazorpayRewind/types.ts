export type CarouselSlides = {
  imgSrc?: string;
  imgOverlay: React.ReactChild | null;
  key: string;
};

export type SizeOptions = {
  mobileValue: string;
  tabletValue: string;
  defaultValue: string;
  smallMobileValue?: string;
};
