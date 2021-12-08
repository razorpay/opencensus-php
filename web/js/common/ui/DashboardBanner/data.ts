const bannerThemes = ['primary', 'warning', 'danger', 'success', 'purply', 'burgundy'] as const;

const textStyle = ['normal', 'bold', 'italics'] as const;

const externalURLTest = new RegExp('^http(s)?://');

export { bannerThemes, textStyle, externalURLTest };
