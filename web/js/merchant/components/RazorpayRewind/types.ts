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

export type MilestoneDataT = {
  milestone_date_one?: string | null;
  milestone_date_label_one?: string | null;
  milestone_date_two?: string | null;
  milestone_date_label_two?: string | null;
  milestone_date_three?: string | null;
  milestone_date_label_three?: string | null;
};

export type MilestoneT = {
  date: string;
  prefix: string;
  value: string;
  suffix: string;
  upperText: string;
  lowerText: string;
};
