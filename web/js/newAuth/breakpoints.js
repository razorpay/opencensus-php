const size = {
  mobile: '415px',
  tab: '769px',
  tabBig: '900px',
};

export const media = {
  mobile: `(min-width: ${size.mobile})`,
  tab: `(min-width: ${size.tab})`,
  tabBig: `(min-width: ${size.tab}) and (max-width: ${size.tabBig})`,
};
