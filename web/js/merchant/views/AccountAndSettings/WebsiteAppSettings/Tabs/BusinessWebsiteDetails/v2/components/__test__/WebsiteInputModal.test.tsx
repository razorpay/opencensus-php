import React, { useState } from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';

import { User } from 'common/typings';

import { mockMainPageUrl } from '../../__test__/mocks/fixtures';
import { MainPageFormData } from '../../types';
import { mainPageFormDefaultValue } from '../../utils';
import WebsiteInputModal from '../WebsiteInputModal';

const mockOnDismiss = jest.fn();
const mockHandleMainPageSubmit = jest.fn();

jest.mock(
  'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils',
  () => {
    const originalModule = jest.requireActual(
      'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails/v2/utils',
    );
    return {
      __esModule: true,
      ...originalModule,
      getWebsiteCount: jest.fn(() => {
        return 0;
      }),
      getSuggestionStep: jest.fn(() => {
        return '';
      }),
    };
  },
);

const defaultProps = {
  isMobile: false,
  isOpen: true,
  onDismiss: mockOnDismiss,
  handleMainPageSubmit: mockHandleMainPageSubmit,
  user: {} as User,
};

const RenderWebsiteInput = (props) => {
  const [formState, setFormState] = useState<MainPageFormData>(mainPageFormDefaultValue);
  return (
    <WebsiteInputModal
      formState={formState}
      setFormState={setFormState}
      {...defaultProps}
      {...props}
    />
  );
};

const renderApp = (props = {}) => {
  const renderOutput = render(<RenderWebsiteInput {...props} />);
  return renderOutput;
};

jest.setTimeout(30000);

describe('Business website automation - WebsiteInputModal', () => {
  const testFormInputAndSubmit = async () => {
    expect(
      screen.getByRole('radiogroup', {
        name: 'Where do you want to accept payments?',
      }),
    ).toBeInTheDocument();
    const websiteRadio = screen.getByRole('radio', {
      name: 'Website',
    });
    const appRadio = screen.getByRole('radio', {
      name: 'App',
    });
    expect(websiteRadio).toBeInTheDocument();
    expect(appRadio).toBeInTheDocument();
    await userEvent.click(appRadio);
    await userEvent.click(websiteRadio);

    const urlInput = screen.getByRole('textbox', {
      name: 'Add your website link',
    });
    expect(urlInput).toBeInTheDocument();
    await userEvent.type(urlInput, mockMainPageUrl);

    expect(
      screen.getByRole('radiogroup', {
        name: 'Does your website require users to login to complete a payment?',
      }),
    ).toBeInTheDocument();
    const crdsRadio = screen.getByRole('radio', {
      name: 'Yes',
    });
    const noCrdsRadio = screen.getByRole('radio', {
      name: 'No',
    });
    expect(crdsRadio).toBeInTheDocument();
    expect(noCrdsRadio).toBeInTheDocument();
    await userEvent.click(crdsRadio);
    expect(
      screen.getByRole('textbox', {
        name: 'Add test account username/email',
      }),
    ).toBeInTheDocument();

    await userEvent.click(noCrdsRadio);
  };

  it('should show the input form and submit it', async () => {
    renderApp();
    expect(screen.getByText('Submit details for verification')).toBeInTheDocument();

    const submitButton = screen.getByRole('button', { name: 'Submit' });
    await testFormInputAndSubmit();
    expect(submitButton).toBeEnabled();
    await userEvent.click(submitButton);
    await waitFor(() => {
      expect(mockHandleMainPageSubmit).toHaveBeenCalled();
    });
  });

  it('should show the input form and submit it on mobile', async () => {
    renderApp({
      isMobile: true,
    });
    expect(screen.getByText('Submit details for verification')).toBeInTheDocument();

    const submitButton = screen.getByRole('button', { name: 'Proceed' });
    await testFormInputAndSubmit();
    expect(submitButton).toBeEnabled();
    await userEvent.click(submitButton);
    await waitFor(() => {
      expect(mockHandleMainPageSubmit).toHaveBeenCalled();
    });
  });

  it('should dismiss the modal by clicking cancel', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText('Submit details for verification')).toBeInTheDocument();
    });

    await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));

    await waitFor(() => {
      expect(mockOnDismiss).toHaveBeenCalled();
    });
  });
});
