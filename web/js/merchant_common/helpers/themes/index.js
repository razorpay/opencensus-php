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
    navBg: '#2e3345',
    primary: '#528ff0',
    secondary: '#FFFFFF',
    backgroundShade: '#fff8f8',
    link: '#528ff0',
  },
};

export const LOGOS = {
  axis: 'https://cdn.razorpay.com/static/assets/hostedpages/axis_logo.svg',
};

// TODO: revert changes later
// axis: {
//   navBg: '#2e3345',
//   primary: '#528ff0',
//   sidebarLinkActive: '#528ff0',
//   sidebarLinkActiveBg: '#252939',
//   brandBg: 'transparent',
//   borderColor: '#528ff0',
//   actStatusBg: '#78103D',
//   actText: 'white',
// }

const makeTheme = (it, org) => `
#react-root.bob .brand-logo img {
  width: 100%;
  height: 100%;
}

.${org} .btn-primary,
.${org} .btn-primary:active,
.${org} .btn-primary:active:hover,
.${org} .btn.btn-primary,
.${org} .btn-primary,
.${org} .OnBoarding .Button-Container .Button.Forward-Button,
.${org} .SliderDots .SliderDots-dot.SliderDots-dot--active,
.${org} .Button--primary.Button,
.${org} .Button--primary,
.${org} :checked + .Input-checkbox,
.${org} .btn-primary[disabled]:hover,
.${org} .OnBoarding .Button-Container .Button.Forward-Button,
.${org} .Button--primary:not(:disabled):hover  {
  background-color: ${it.primary};
  border-color: ${it.primary};
}

.${org} .Button--primary--invert,
.${org} .Input:not(.Input--disabled) .Input-el:focus,
.${org} .PowerSelect--focused,
.${org} .rc-calendar-today .rc-calendar-date
{
  border-color: ${it.primary} !important;
}

.${org} .btn-secondary,
.${org} .btn-secondary:active,
.${org} .btn-secondary:active:hover,
.${org} .btn.btn-secondary,
.${org} .btn-secondary,
.${org} .Button--secondary.Button,
.${org} .Button--secondary,
.${org} .btn-secondary[disabled]:hover,
.${org} .Button--secondary:not(:disabled):hover  {
  background-color: transparent;
  color: ${it.primary};
  border-color: ${it.primary};
}

.${org} .btn-primary:hover,
.${org} .btn-primary:active,
.${org} .btn-primary:focus {
  background-color: ${it.primary};
}

.${org} .btn-outline,
.${org} .btn-outline:hover {
  color: ${it.primary};
  border: 1px solid ${it.primary};
}

.${org} tabbed-container header a.active,
.${org} .Button--primary--invert,
.${org} .Button--transparent,
.${org} .Button--transparent:not(:disabled):hover {
  border-color: ${it.primary};
  color: ${it.primary};
}

.${org} tabbed-container header a:hover {
  color: ${it.primary};
}

.${org} div.panel.recent-activity-cont .panel-topbar tabbed-container .row a.active {
  border-color: ${it.primary};
  color: ${it.primary};
}

.${org} div.panel.recent-activity-cont .panel-topbar tabbed-container .row a:hover {
  color: ${it.primary};
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
  color: ${it.tertiary};
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

.${org} .SliderDots .SliderDots-dot {
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

.${org} .Wizard aside li.active {
  background-color: ${it.primary};
}

.${org} .btn-link,
.${org} a.breadcrumb__backNav--link,
${/* .${org} a:not(.btn-primary):not(.NavLink):not(.Button):not(.btn) */ ''},
.${org} .Button--Link,
.${org} .OnBoarding--Features .Header .Header-external-links,
.${org} .link,
.${org} .TemplateCard .link,
.${org} .OnBoarding .SliderDots .Button{
  color: ${it.primary};
}

.${org} a.btn-primary {
  color: #fff;
}

// HINT: don't have info on the light primary color value, to make UI good we are adding this
.${org} .btn-link:hover,
.${org} .Button--primary--invert:hover,
${/* .${org} a:hover:not(.btn-primary):not(.NavLink):not(.Button):not(.btn) */ ''} {
  color: ${it.primary};
  opacity: 0.85;
}

.${org} .payment-capture-panel .panel-content .is-active,
.${org} .refund-panel .refund-panel-col.active {
  border: 1px solid ${it.primary};
  background-color: ${it.backgroundShade}
}

.${org} .navbar-default .navbar-nav > li > a {
  color: ${it.link};
}

.${org} .keymetrics > .nav.nav-tabs li.active > a > div,
.${org} .keymetrics > .nav.nav-tabs li > a:hover > div,
.${org} .keymetrics > .nav.nav-tabs li > a > div > div.mini-chart.no-data.active .min-chart-content,
.${org} .panel.dasboard-home-panel div.panel-actions .panel-action-item.btn-group .btn-default.active {
  border-color: ${it.primary};
}

.${org} .keymetrics > .nav.nav-tabs li.active > a > div:after {
  border-top-color: ${it.primary};
}

`;

export const applyTheme = (org) => {
  const style = document.createElement('style');
  style.type = 'text/css';
  const rules = makeTheme(
    Object.assign(
      base,
      org.merchant_styles
        ? { ...THEMES[org.custom_code], ...org.merchant_styles }
        : THEMES[org.custom_code],
    ),
    org.custom_code,
  );
  if (style.styleSheet) {
    style.styleSheet.cssText = rules;
  } else {
    style.appendChild(document.createTextNode(rules));
  }
  document.getElementsByTagName('head')[0].appendChild(style);
};
