import React from 'react';
import { getAllByTestId, render, screen, userEvent } from 'test-utils';

import BaseForm from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/BaseForm';
import { SEC_REF_ID } from 'merchant/views/PaymentPages/PaymentPages/constants';

describe('BaseForm', () => {
  test('should check "Select as Secondary Reference ID" to be checked if form item name includes sec__ref__id', async () => {
    const { container } = render(
      <BaseForm
        field={{ name: `${SEC_REF_ID}_test` }}
        isBatchPaymentPages
        fieldIndexInOptions={1}
      />,
    );

    await userEvent.click(screen.getByTestId('dropdown-trigger'));

    expect(getAllByTestId(container, 'list-option-selected')).toHaveLength(1);
    expect(getAllByTestId(container, 'tick-icon-visible')).toHaveLength(1);
  });
});
