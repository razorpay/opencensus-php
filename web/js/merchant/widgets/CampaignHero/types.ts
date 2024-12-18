export interface CampaignHeroWidgetProps {
  type: string;
  title: string;
  data?: {
    campaign_hero_card_data?: {
      assetData?: AssetDataProps[];
    };
  };
}

export interface AssetDataProps {
  templates: {
    data: {
      id: string;
      rtux_ucs_campaigns: CampaignDataProps;
    };
  }[];
  trackingData: {
    [key: string]: any;
  };
}

export interface CampaignDataProps {
  alt_text: string;
  cta_link: string;
  image: {
    sm: string;
    md: string;
    lg: string;
  };
}
