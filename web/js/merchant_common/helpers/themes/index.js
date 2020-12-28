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
  icic: {
    navBg: '#163D6B',
    primary: '#0A3D6B',
    sidebarLinkActive: '#5697fc',
    brandBg: 'transparent',
    borderColor: '#fff',
  },
  axis: {
    navBg: '#97144d',
    primary: '#97144d',
    sidebarLinkActive: 'pink',
    sidebarLinkActiveBg: '#78103D',
    brandBg: 'transparent',
    borderColor: 'pink',
    actStatusBg: '#78103D',
    actText: 'white',
  },
};

export const applyTheme = (org) => {
  var style = document.createElement('style');
  style.type = 'text/css';
  var rules = makeTheme(Object.assign(base, THEMES[org]), org);
  if (style.styleSheet) {
    style.styleSheet.cssText = rules;
  } else {
    style.appendChild(document.createTextNode(rules));
  }
  document.getElementsByTagName('head')[0].appendChild(style);
};

const makeTheme = (it, org) => `
#react-root.bob .brand-logo img {
  width: 100%;
  height: 100%;
}

.${org} .btn-primary,
.${org} .btn-primary:active,
.${org} .btn-primary:active:hover,
.${org} .btn-primary[disabled]:hover {
  background-color: ${it.primary};
  border-color: ${it.primary};
}

.${org} tabbed-container header a.active {
  border-color: ${it.primary};
  color: ${it.primary};
}

.${org} .btn-primary:hover,
.${org} .btn-primary:active,
.${org} .btn-primary:focus, {
  background-color: ${it.transparent};
}

${it.actStatus ? `.${org} .activation-status{color:${it.actStatus}};` : ''}
${
  it.actStatusBg
    ? `.${org} .sidebar nav div.activation-status{background-color:${it.actStatusBg}};`
    : ''
}
${it.actText ? `.${org} .activation-bar-text{color:${it.actText}};` : ''}
${it.brandBg ? `.${org} .brand-logo{background:${it.brandBg};}` : ''}

.${org} .sidebar {
  background-color: ${it.navBg || it.primary};
}

.${org} .sidebar .brand-logo::after {
  border-color: ${it.borderColor || it.transparent};
}

.${org} .sidebar .nav > a {
  color: ${it.sideBarColor || it.textLight};
}

.${org} .sidebar .nav > a:hover {
  background-color: ${it.transparent};
  ${it.sideBarColor ? `color:${it.sideBarColor};` : ''}
}

.${org} .sidebar .nav > a:focus,
.${org} .sidebar .nav > a.active {
  background-color: ${it.sidebarLinkActiveBg || it.transparentDark};
  border-color: ${it.sidebarLinkActive || it.transparent};
}

${
  it.sideBarIcon
    ? `
    .${org} .sidebar .nav > a:focus,
    .${org} .sidebar .nav > a >i{
color:${it.sideBarIcon};
}`
    : ``
}

${
  it.sideBarIconActive
    ? `
    .${org} .sidebar .nav > a:focus,
    .${org}.sidebar .nav > a.active>i{
color:${it.sideBarIconActive};
}`
    : ``
}

.${org} .table-striped > tbody > tr:nth-child(odd) > td,
.${org} .table-striped > tbody > tr:nth-child(odd) > th {
  background-color: ${it.primaryTransparent};
}
.${org} .table-striped > tbody > tr:nth-child(even) > td,
.${org} .table-striped > tbody > tr:nth-child(even) > th {
  background-color: #ffffff;
}

.${org} .panel-default .panel-heading {
  background-color: ${it.primaryTransparent};
}

.${org} .alert-danger {
  background-color: ${it.errorBackground};
  color: ${it.secondary};
}


.${org} .bg-dark .text-warning-lter,
.${org} .bg-dark .nav > li > a,
.${org} .bg-dark .text-info,
.${org} .bg-dark .text-muted {
  color: ${it.textLight} !important;
}

.${org} .bg-dark .line {
  background-color: ${it.transparent};
}

.${org} .btn.btn-primary {
  background-color: ${it.primary};
  border-color: ${it.primary}
}

.${org} .bg-light.lter, .${org} .bg-light .lter {
  background-color: ${it.primaryTransparent} !important;
}

.${org} .form-control[disabled],
.${org} .form-control[readonly],
.${org} fieldset[disabled] .form-control {
  background-color: ${it.primaryTransparent};
}
`;
