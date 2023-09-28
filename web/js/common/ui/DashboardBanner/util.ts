import { BannerButton, CTA } from './TypesDeclare/DashboardBannerTypes';
import { getClickHandler } from './handlers';
import { externalURLTest } from './data';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';

const getCTAArray = (
  buttons: Array<BannerButton>,
  history: RouteComponentProps['history'],
  tracking_id: string,
): Array<CTA> | undefined => {
  return buttons?.map(({ id, type, label, style, url, sub_asset, handler }) => {
    let isExternal = true;
    const params = {
      id,
      type: sub_asset?.type,
      variant: sub_asset?.variant,
      handler,
      history,
      tracking_id,
    };
    if (typeof url === 'string') isExternal = externalURLTest.test(url);
    return {
      clickHandler: getClickHandler(params),
      url,
      isExternal,
      type,
      label,
      style,
    };
  });
};

const getCTAClassName = (type = ''): string => {
  switch (type) {
    case 'transparent':
      return 'banner-text-link';
    case 'secondary':
      return 'Button--secondary Button scheduled-btn-act btn-border';
    case 'primary':
    default:
      return 'Button--primary Button scheduled-btn-act btn-border';
  }
};

export { getCTAArray, getCTAClassName };
