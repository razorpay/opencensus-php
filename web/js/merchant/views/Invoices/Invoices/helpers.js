import copyToClipboard from 'common/utils/copyToClipboard';

export function removeTaxForNonINRItems(props, invoiceCurrency) {
  const updatedProps = { ...props };

  if (invoiceCurrency !== 'INR') {
    updatedProps.currency = invoiceCurrency;

    updatedProps.line_items = updatedProps.line_items.map((item) => {
      delete item.taxes;
      delete item.tax_ids;
      delete item.tax_inclusive;
      delete item.tax_rate;

      return item;
    });
  }

  updatedProps.line_items = updatedProps.line_items.map((item) => {
    const currency = (item.selectedItem && item.selectedItem.currency) || item.currency;

    if (invoiceCurrency !== currency) {
      delete item.item_id;

      return {
        ...item,
        currency: invoiceCurrency,
        deleteTaxId: true,
        addName: true,
      };
    }
    return item;
  });

  return updatedProps;
}

export const shareURL = (url, title) => {
  if (navigator.share) {
    navigator
      .share({
        title,
        url,
      })
      .catch((e) => {
        if (window.APP_ENV !== 'production') console.error(e);
      });
  } else {
    // fallback
    copyToClipboard(url);
  }
};

export const TAX_DIVISOR = 10000;
export const TAX_PERCENTAGE_DIVISOR = 10000.0;
