import React from 'react';
import { render, userEvent, waitFor, screen } from 'test-utils';
import { AddLink } from '..';
import { Platform } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import { PLATFORM_TITLE } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';
import * as ModalActions from 'merchant_common/reducers/modals';

let openModalSpy;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({
    abExperiments: {
      show_v2_website_flow: {
        variables: {
          result: 'off',
        },
      },
    },
  }),
}));

describe('API Keys & Plugins - AddLink', () => {
  beforeAll(() => {
    openModalSpy = jest.spyOn(ModalActions, 'openModal');
  });

  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should render component without errors', () => {
    expect(() => render(<AddLink platform={Platform.WEBSITE} />)).not.toThrowError();
  });

  test('should render platform title correctly', () => {
    const { queryByText } = render(<AddLink platform={Platform.IOS} />);
    const title = queryByText(`Add your ${PLATFORM_TITLE[Platform.IOS]} link`);
    expect(title).toBeInTheDocument();
  });

  test('should open modal on Add click', async () => {
    const { getByRole } = render(<AddLink platform={Platform.IOS} />);
    const addButton = getByRole('button', { name: /add link/i });
    expect(addButton).toBeInTheDocument();
    await userEvent.click(addButton);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalledTimes(1);
    });
  });

  test('should render restrict website content', async () => {
    render(<AddLink platform={Platform.IOS} userHasKeyAccess={true} />);
    const addButton = screen.getByRole('button', { name: /add link/i });
    await userEvent.click(addButton);
    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalledTimes(1);
    });
  });
});
