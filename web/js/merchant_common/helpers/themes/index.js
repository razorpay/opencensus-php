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
  var rules = makeTheme(Object.assign(base, THEMES[org]));
  if (style.styleSheet) {
    style.styleSheet.cssText = rules;
  } else {
    style.appendChild(document.createTextNode(rules));
  }
  document.getElementsByTagName('head')[0].appendChild(style);
};

const makeTheme = it => `
#react-root.bob .brand-logo img {
  width: 100%;
  height: 100%;
}

.btn-primary,
.btn-primary:active,
.btn-primary:active:hover,
.btn-primary[disabled]:hover {
  background-color: ${it.primary};
  border-color: ${it.primary};
}

tabbed-container header a.active {
  border-color: ${it.primary};
  color: ${it.primary};
}

.btn-primary:hover,
.btn-primary:active,
.btn-primary:focus, {
  background-color: ${it.transparent};
}

.sidebar {
  background-color: ${it.navBg || it.primary};
}

.sidebar .brand-logo::after {
  border-color: ${it.transparent};
}

.sidebar .nav > a {
  color: ${it.textLight};
}

.sidebar .nav > a:hover {
  background-color: ${it.transparent};
}

.sidebar .nav > a:focus,
.sidebar .nav > a.active {
  background-color: ${it.transparentDark};
  border-color: ${it.transparent};
}

.table-striped > tbody > tr:nth-child(odd) > td,
.table-striped > tbody > tr:nth-child(odd) > th {
  background-color: ${it.primaryTransparent};
}
.table-striped > tbody > tr:nth-child(even) > td,
.table-striped > tbody > tr:nth-child(even) > th {
  background-color: #ffffff;
}

.panel-default .panel-heading {
  background-color: ${it.primaryTransparent};
}

.alert-danger {
  background-color: ${it.errorBackground};
  color: ${it.secondary};
}


.bg-dark .text-warning-lter,
.bg-dark .nav > li > a,
.bg-dark .text-info,
.bg-dark .text-muted {
  color: ${it.textLight} !important;
}

.bg-dark .line {
  background-color: ${it.transparent};
}

.btn.btn-primary {
  background-color: ${it.primary};
  border-color: ${it.primary}
}

.bg-light.lter, .bg-light .lter {
  background-color: ${it.primaryTransparent} !important;
}

.form-control[disabled],
.form-control[readonly],
fieldset[disabled] .form-control {
  background-color: ${it.primaryTransparent};
}
`;
