import { BannerButton, CTA } from './TypesDeclare/DashboardBannerTypes';
import { getClickHandler } from './handlers';
import { externalURLTest } from './data';

const getCTAArray = (buttons: Array<BannerButton>): Array<CTA> | undefined => {
  return buttons?.map(({ id, type, label, style, url }) => {
    let isExternal = true;
    if (typeof url === 'string') isExternal = externalURLTest.test(url);

    return {
      clickHandler: getClickHandler(id),
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
