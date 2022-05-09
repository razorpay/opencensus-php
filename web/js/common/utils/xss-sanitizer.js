import xss from 'xss';

// Customize the handler function for attributes not in the whitelist
const onIgnoreTagAttr = (_, name, value) => {
  // filter attributes starting with data-, class, style
  if (name.slice(0, 5) === 'data-' || name.slice(0, 5) === 'id') {
    // escape its value using built-in escapeAttrValue function
    return `${name}="${xss.escapeAttrValue(value)}"`;
  }
  return null;
};

const customWhiteList = {
  a: ['target', 'href', 'title', 'aria-label'],
  abbr: ['title'],
  article: [],
  b: [],
  br: [],
  caption: [],
  col: ['align', 'valign', 'span', 'width'],
  colgroup: ['align', 'valign', 'span', 'width'],
  div: [],
  h1: [],
  h2: [],
  h3: [],
  h4: [],
  h5: [],
  h6: [],
  i: [],
  img: ['src', 'alt', 'title', 'width', 'height'],
  li: [],
  ol: [],
  p: [],
  pre: [],
  s: [],
  section: [],
  small: [],
  span: [],
  strong: [],
  svg: ['viewBox', 'stroke', 'fill', 'height', 'width', 'xmlns', 'xmlns:xlink'],
  table: ['width', 'border', 'align', 'valign'],
  tbody: ['align', 'valign'],
  td: ['width', 'rowspan', 'colspan', 'align', 'valign'],
  tfoot: ['align', 'valign'],
  th: ['width', 'rowspan', 'colspan', 'align', 'valign'],
  thead: ['align', 'valign'],
  tr: ['rowspan', 'align', 'valign'],
  tt: [],
  u: [],
  ul: [],
};

const customXssFillter = new xss.FilterXSS({
  css: true,
  stripIgnoreTagBody: true,
  allowCommentTag: false, // remove comments
  whiteList: customWhiteList,
  onIgnoreTagAttr,
});

const sanitizer = (html, extraOptions) => {
  if (extraOptions && (Object.keys(extraOptions) || []).length > 0) {
    customXssFillter.options.whiteList = {
      ...customXssFillter.options.whiteList,
      ...extraOptions,
    };
  } else {
    customXssFillter.options.whiteList = customWhiteList;
  }

  return customXssFillter.process(html);
};

export { customWhiteList };
export default sanitizer;
