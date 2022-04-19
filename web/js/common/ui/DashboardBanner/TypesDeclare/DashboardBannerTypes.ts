import { bannerThemes, textStyle } from '../data';

interface FetchBannersProps {
  fromWhere: string;
}

interface BannerButton {
  id?: string;
  type: string;
  label: string;
  style?: string;
  url?: string;
  sub_asset?: SubAsset;
}

interface BannerContent {
  description: string;
  type?: typeof textStyle[number];
}
interface BannerTextLink {
  url: string;
  label: string;
}

interface MetaType {
  product_feature?: string;
}

interface TrackingDataType {
  campaign: string;
  campaign_description: string;
  sub_campaign?: string;
  sub_campaign_description?: string;
  campaign_id?: string;
  sub_campaign_id?: string;
  meta?: MetaType;
}

interface Banner {
  id: string;
  title: string;
  className?: string;
  theme: typeof bannerThemes[number];
  dismissible: boolean;
  override_priority: boolean;
  buttons?: Array<BannerButton> | null;
  content: BannerContent;
  text_link?: BannerTextLink;
  tracking_data: TrackingDataType;
}
interface SubAsset {
  type: string;
  variant: string;
}

interface DashboardBannerProps {
  banners: Array<Banner> | [];
  loading: boolean;
  fetchBanners: ({ fromWhere }: FetchBannersProps) => void;
}

interface CTA extends BannerButton {
  clickHandler?: () => void;
  isExternal: boolean;
}

export { DashboardBannerProps, BannerButton, CTA, Banner };
