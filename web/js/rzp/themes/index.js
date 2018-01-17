import Theme from './theme';

const base = {
  transparent: 'rgba(0,0,0,0.2)',
  transparentDark: 'rgba(0,0,0,0.4)',
  textLight: 'rgba(255,255,255,0.9)',
  primaryLight: '#e1f3ff',
  primaryTransparent: 'rgba(225,243,252,0.3)',
  errorBackground: 'rgba(234,33,45,0.1)',
  secondary: '#ea212d',
  tertiary: '#ffffff',
};

const THEMES = {
  hdfc: {
    primary: '#084c8d',
  },
  bob: {
    navBg: '#FF5D27',
    primary: '#F04E00',
  },
  icici: {
    navBg: '#F07937',
    primary: '#0A3D6B',
  },
};

export const applyTheme = org => {
  var style = document.createElement('style');
  style.type = 'text/css';
  var rules = Theme.render(Object.assign(base, THEMES[org]));
  if (style.styleSheet) {
    style.styleSheet.cssText = rules;
  } else {
    style.appendChild(document.createTextNode(rules));
  }
  document.getElementsByTagName('head')[0].appendChild(style);
};
