import { sanitizeHTML } from 'common/utils/rzp-utils';
import React from 'react';

const createHighlighedOption = (label, searchTerm) => {
  if (searchTerm) {
    let escapedSearchTerm = searchTerm.replace(
      /([.?*+^$[\]\\(){}|-])/g,
      '\\$1'
    );
    label = label.replace(new RegExp(escapedSearchTerm, 'i'), '<b>$&</b>');
  }

  return label;
};

export default ({ option, select, optionLabelPath }) => {
  let highlightedLabel = option[optionLabelPath];

  const html = {
    __html: sanitizeHTML(
      createHighlighedOption(highlightedLabel, select.searchTerm)
    ),
  };

  return <span dangerouslySetInnerHTML={html} />;
};
