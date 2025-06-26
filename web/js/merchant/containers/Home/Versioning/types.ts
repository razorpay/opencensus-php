export interface BentoCardProps {
  id: string;
  title: string;
  productName: string;
  tags: string[];
  image: string;
  flipped: boolean;
  onFlip: () => void;
  onClose: () => void;
  isMobile: boolean;
  boxType: 'large' | 'small';
  backText: string;
  ctaText?: string;
  ctaLink?: string;
  imageBgColor?: string;
  cardSetType?: string;
}

export interface BentoCardData {
  id: string;
  title: string;
  productName: string;
  tags: string[];
  imgLarge: string;
  imgSmall: string;
  backText: string;
  ctaText?: string;
  ctaLink?: string;
  imageBgColor?: string;
}

export interface RenderedBentoCard extends BentoCardData {
  boxType: 'large' | 'small';
  span: number;
  key: string;
  spanFullTablet?: boolean;
}

// Updated flexible heading system (renamed from HeadingSegment)
export type HeadingSegment = {
  type: 'text' | 'svg' | 'break';
  content?: string; // Text content OR SVG filename (e.g., 'arrow.svg', 'star.svg')
  textSize?: 'small' | 'medium' | 'large';
  imgSize?: {
    width: number;
    height: number;
  };
  marginLeft?: 'auto' | 'none';
  marginRight?: 'auto' | 'none';
  paddingLeft?: any;
  paddingRight?: any;
};

export type DeviceVersioningConfig = {
  logo?: {
    src: React.ComponentType | string;
    size?: {
      width: number;
      height: number;
    };
  }; // Can be React component or image URL
  heading: HeadingSegment[];
  body: {
    text: string;
    size: 'small' | 'medium' | 'large';
  };
  backgroundColor?: string;
};

export type VersioningConfig = {
  mobile: DeviceVersioningConfig;
  desktop: DeviceVersioningConfig;
};
