import React from 'react';

import { screen, within } from '@testing-library/react';
import { render } from 'test-utils';

import UPISelector, { UPI_APPS_SELECT_OPTIONS } from '../UPISelector';
import { UPI_APP_PROVIDERS } from '../../../constants';

const DEFAULT_PROPS = {
  onChangeHandler: jest.fn(),
  selectedValues: [],
  errorText: '',
};

function renderWrapper(Component) {
  return (props = {}, options = {}) => {
    return render(<Component {...props} />, options);
  };
}

describe('Component: UPISelector', () => {
  let props = null;
  const renderer = renderWrapper(UPISelector);

  beforeEach(() => {
    props = DEFAULT_PROPS;
  });

  it('Shows the incoming list of selected values in select input', async () => {
    const selectedValues = [UPI_APP_PROVIDERS.GPAY, UPI_APP_PROVIDERS.CRED];
    props = {
      ...props,
      selectedValues,
    };

    renderer(props);
    const upiSelector = await screen.findByTestId('upi-selector');
    const upiSelectorWrapper = within(upiSelector);
    expect(upiSelector).toBeInTheDocument();

    expect(
      await upiSelectorWrapper.findByText(`${selectedValues.length} Selected`),
    ).toBeInTheDocument();
  });
});
