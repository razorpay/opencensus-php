import { FilterXSS, escapeAttrValue } from 'xss';

// Customize the handler function for attributes not in the whitelist
const onIgnoreTagAttr = (_: string, name: string, value: string): string | undefined => {
  // Filter attributes starting with data-, class, id
  if (name.slice(0, 5) === 'data-' || ['id', 'class'].includes(name)) {
    // Escape its value using built-in escapeAttrValue function
    return `${name}="${escapeAttrValue(value)}"`;
  }
  return undefined;
};

type CustomWhiteList = Record<string, string[]>;

export const customWhiteList: CustomWhiteList = {
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

/**
 * Sanitizes HTML input with optional extra whitelisting.
 *
 * @param {string} html - The HTML string to sanitize.
 * @param {Record<string, string[]>} [extraOptions] - Additional options for whitelisting.
 * @returns {string} - The sanitized HTML string.
 */
export const sanitizer = (html: string, extraOptions?: Record<string, string[]>): string => {
  const customXssFilter = new FilterXSS({
    css: true,
    stripIgnoreTagBody: true,
    allowCommentTag: false, // Remove comments
    whiteList: {
      ...customWhiteList,
      ...(Boolean(extraOptions && Object.keys(extraOptions).length) ? extraOptions : {}),
    },
    onIgnoreTagAttr,
  });

  return customXssFilter.process(html);
};

