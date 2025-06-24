import { RouteComponentProps } from '@libs/web-nexus/common/deprecated/RouteComponentProps';

export interface Features {
  icon: React.ReactNode;
  textMobile: string;
  textDesktop: string;
}
export interface PageConfigType {
  id: string;
  title: string;
  subtitle: string;
  features: Features[];
  carouselImages: Array<{
    src: string;
    alt: string;
  }>;
  previewGif: string;
  analyticsData: {
    product_template: string;
    [key: string]: any;
  };
  backgroundColor: string;
}

export interface FeatureListProps {
  features: Features[];
  isMobile?: boolean;
}

export interface PageCardProps {
  config: PageConfigType;
  onCreateClick: () => void;
  type: string;
  isMobile: boolean;
}

export interface TemplateSelectionProps extends RouteComponentProps {
  handlePageType: (val: string) => void;
  isMobile: boolean;
}
