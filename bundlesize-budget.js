// Use size limit in KB

module.exports = [
  {
    name: 'Merchant',
    path: 'js/merchant/merchant.*.js',
    limit: '500 KB',
    gzip: true,
  },
  {
    name: 'Runtime',
    path: 'js/merchant/runtime.*.js',
    limit: '10 KB',
    gzip: true,
  },
  {
    name: 'Merc. Vendor',
    path: 'js/merchant/vendors~merchant.*.js',
    limit: '540 KB',
    gzip: true,
  },
  {
    name: 'FECare Vendor',
    path: 'js/merchant/vendors~frontend-care.*.js',
    limit: '60 KB',
    gzip: true,
  },
  {
    name: 'Drp',
    path: 'js/merchant/Drp.*.js',
    limit: '2 KB',
    gzip: true,
  },
  {
    name: 'Treemap Vendor',
    path: 'js/merchant/vendors~Treemap.*.js',
    limit: '60 KB',
    gzip: true,
  },
  {
    name: 'Treemap',
    path: 'js/merchant/Treemap.*.js',
    limit: '5 KB',
    gzip: true,
  },
  {
    name: 'Merc. Mobile',
    path: 'js/merchant/merchantMobile.*.js',
    limit: '130 KB',
    gzip: true,
  },
  {
    name: 'Merc. Desktop',
    path: 'js/merchant/merchantDesktop.*.js',
    limit: '120 KB',
    gzip: true,
  },
  {
    name: 'Optimizer Dashboard',
    path: 'js/merchant/Navigator.*.js',
    limit: '2 KB',
    gzip: true,
  },
  {
    name: 'Optimizer OnBoarding',
    path: 'js/merchant/OnBoarding.*.js',
    limit: '3 KB',
    gzip: true,
  },
  {
    name: 'Optimizer AddProvider',
    path: 'js/merchant/AddProvider.*.js',
    limit: '20 KB',
    gzip: true,
  },
  {
    name: 'Optimizer CreateRule',
    path: 'js/merchant/CreateRule.*.js',
    limit: '20 KB',
    gzip: true,
  },
  {
    name: 'Optimizer RuleList',
    path: 'js/merchant/RuleList.*.js',
    limit: '7 KB',
    gzip: true,
  },
];
