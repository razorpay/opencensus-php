import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import CreatorManager from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/CreatorManager';
import { FIXED_FIELDS } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/UDF/helpers/preAddedFields';

function Field({ field, openBaseForm }) {
  return <div onClick={openBaseForm}>{field.title}</div>;
}

const renderApp = (props) => {
  // eslint-disable-next-line babel/new-cap
  const App = CreatorManager(Field);

  return render(
    <App
      checkoutOptions={{ name: 'test_name', email: 'test_email' }}
      validateSameTitleExists={jest.fn()}
      field={{}}
      {...props}
    />,
  );
};

describe('CreatorManager', () => {
  test('should check, first secondary reference field contains options that it cannot be deletable and should not be made optional and should be able to add description', async () => {
    const field = { ...FIXED_FIELDS.secondaryRefId, options: [], settings: { position: '0' } };

    renderApp({ field, isBatchPaymentPages: true });

    // Click on the field to open form modal.
    await userEvent.click(screen.getByText(field.title));
    // Show options.
    await userEvent.click(screen.getByTestId('dropdown-trigger'));

    expect(screen.queryByText('Delete Field')).not.toBeInTheDocument();
    expect(screen.queryByText('Optional Field')).not.toBeInTheDocument();
    expect(screen.queryByText('Add Description')).toBeInTheDocument();
  });

  test('primary ref id should be editable', async () => {
    const field = { ...FIXED_FIELDS.primaryRefId, options: [], settings: { position: '0' } };

    renderApp({ field, isBatchPaymentPages: true });

    // Click on the field to open form modal.
    await userEvent.click(screen.getByText(field.title));

    expect(screen.getByPlaceholderText('Enter field label')).not.toHaveAttribute('disabled');
  });
});
