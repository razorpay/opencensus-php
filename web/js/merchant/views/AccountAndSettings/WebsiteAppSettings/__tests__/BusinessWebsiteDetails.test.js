import React from 'react';
import BusinessWebsiteDetails from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/BusinessWebsiteDetails';
import { userEvent, render, screen } from 'test-utils';
import * as ModalActions from 'merchant_common/reducers/modals';
import {
  user,
  initialState,
  initialStateForWorkflows,
} from 'merchant/views/AccountAndSettings/WebsiteAppSettings/__tests__/mocks/fixtures/BusinessWebsiteDetails';

jest.mock('merchant/views/Account/Profile/components/WorkflowRequests/WorkflowStatus', () => ({
  __esModule: true,
  default: ({ reviewStatus, onReplyClick }) => (
    <div>
      <span role="">{reviewStatus}</span>
      <button data-testid="nc-flow-btn" onClick={onReplyClick}>
        Add Reply
      </button>
    </div>
  ),
}));

describe('Business website details', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  beforeEach(() => {
    openModalSpy.mockClear();
  });

  const renderApp = (props) => {
    render(<BusinessWebsiteDetails {...props} user={user} />, { initialState });
  };

  test('should render the section titles', async () => {
    await renderApp();
    const businessWebsite = screen.queryByText('Business Website/App details');
    const additionalWebsite = screen.queryByText('Additional Business Website/App');
    expect(businessWebsite).toBeInTheDocument();
    expect(additionalWebsite).toBeInTheDocument();
  });

  test('should render business website name', async () => {
    await renderApp();
    const businessWebsiteName = screen.queryByText(user.business_website);
    expect(businessWebsiteName).toBeInTheDocument();
  });

  test(`should render edit websites CTAs`, async () => {
    await renderApp();
    const businessWebsiteCTA = screen.getByTestId('business-website-edit');
    const additionalWebsiteCTA = screen.getByTestId('additional-business-website-edit');
    expect(businessWebsiteCTA).toBeInTheDocument();
    expect(additionalWebsiteCTA).toBeInTheDocument();
  });

  test(`should open edit flow modal on business website CTA click`, async () => {
    await renderApp();
    const businessWebsiteCTA = screen.getByTestId('business-website-edit');
    expect(businessWebsiteCTA).toBeInTheDocument();
    await userEvent.click(businessWebsiteCTA);

    expect(openModalSpy).toBeCalled();
  });

  test(`should open edit flow modal on additional business website CTA click`, async () => {
    await renderApp();
    const additionalBusinessWebsiteCTA = screen.getByTestId('additional-business-website-edit');
    expect(additionalBusinessWebsiteCTA).toBeInTheDocument();
    await userEvent.click(additionalBusinessWebsiteCTA);

    expect(openModalSpy).toBeCalled();
  });

  describe(`Merchant doesn't have key access`, () => {
    const renderApp = (props) => {
      const updatedUser = { ...user, has_key_access: false };
      const updatedInitialState = { ...initialState };
      updatedInitialState.session.user = updatedUser;
      render(<BusinessWebsiteDetails {...props} user={updatedUser} />, {
        initialState: updatedInitialState,
      });
    };

    test(`should open edit flow modal on business website CTA click`, async () => {
      await renderApp();
      const businessWebsiteCTA = screen.getByTestId('business-website-edit');
      expect(businessWebsiteCTA).toBeInTheDocument();
      await userEvent.click(businessWebsiteCTA);
      expect(openModalSpy).toBeCalled();
    });
  });

  describe('Workflows are present', () => {
    const renderApp = (props) => {
      render(<BusinessWebsiteDetails {...props} user={user} />, {
        initialState: initialStateForWorkflows,
      });
    };

    test('should render workflow status[nc flow]', async () => {
      await renderApp();
      const businessWebsite = screen.queryByText(
        'Your request to update the website is under review.',
      );
      const addReplyBtn = screen.queryAllByRole('button', { name: 'Add Reply' })[0];
      expect(businessWebsite).toBeInTheDocument();
      expect(addReplyBtn).toBeInTheDocument();
    });

    test('should open nc modal on business website CTA click - workflow status[nc flow]', async () => {
      await renderApp();
      const addReplyBtn = screen.queryAllByRole('button', { name: 'Add Reply' })[0];
      expect(addReplyBtn).toBeInTheDocument();
      await userEvent.click(addReplyBtn);

      expect(openModalSpy).toBeCalled();
    });

    test('should open nc modal on additional business website CTA click - workflow status[nc flow]', async () => {
      await renderApp();
      const addReplyBtn = screen.queryAllByRole('button', { name: 'Add Reply' })[1];
      expect(addReplyBtn).toBeInTheDocument();
      await userEvent.click(addReplyBtn);

      expect(openModalSpy).toBeCalled();
    });
  });
});
