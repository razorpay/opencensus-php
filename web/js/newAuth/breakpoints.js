const size = {
  smallMobile: '320px',
  mobile: '415px',
  tab: '769px',
  tabBig: '900px',
  desktop: '992px',
  mobileTabMax: '1170px',
};

export const media = {
  smallMobile: `(min-width: ${size.smallMobile})`,
  mobile: `(min-width: ${size.mobile})`,
  tab: `(min-width: ${size.tab})`,
  tabBig: `(min-width: ${size.tab}) and (max-width: ${size.tabBig})`,
  desktop: `(min-width: ${size.desktop})`,
  mobileTabMax: `(max-width: ${size.mobileTabMax})`,
};
