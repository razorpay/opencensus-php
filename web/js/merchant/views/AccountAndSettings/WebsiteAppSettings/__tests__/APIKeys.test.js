import React from 'react';
import APIKeysTab from 'merchant/views/AccountAndSettings/WebsiteAppSettings/Tabs/ApiKeys';
import { render, screen, waitFor, server, userEvent } from 'test-utils';
import * as profileActions from 'merchant/reducers/profile';
import { fetchAddWebsiteWorkflowStatusHandler } from './mocks/handlers';

jest.mock('merchant/views/Settings/Keys/List', () => ({
  __esModule: true,
  default: ({ onWebsiteAdd, isWebsiteInWorkflow }) => (
    <div className="table-responsive">
      <button data-testid="add-website" onClick={onWebsiteAdd}>
        Add website
      </button>
      <span>{!isWebsiteInWorkflow ? 'false' : 'true'}</span>
      <table className="table table-hover">
        <thead>
          <tr data-test="key-id-header-row">
            <th>Key Id</th>
            <th>Created At</th>
            <th>Expiry</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>rzp_live_HovgVggeDQ3qvw</td>
            <td>
              <time title="Fri Nov 18 2022 18:26:08 GMT+0530 (India Standard Time)">
                Nov 18th, 2022 06:26:08 PM
              </time>
            </td>
            <td>Never</td>
            <td>
              <div className="row-action">
                <button className="btn btn-xs btn-primary">
                  <i className="i i-refresh" />
                  <span data-test="regenerate-api-key">Regenerate Live Key</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  ),
}));

describe('API Keys', () => {
  const fetchAddWebsiteWorkflowStatusSpy = jest.spyOn(
    profileActions,
    'fetchAddWebsiteWorkflowStatus',
  );

  beforeEach(() => {
    fetchAddWebsiteWorkflowStatusSpy.mockClear();
    server.use(fetchAddWebsiteWorkflowStatusHandler());
  });

  const renderApp = () => {
    render(<APIKeysTab />);
  };

  test('Should render children correctly', () => {
    renderApp();
    const children = screen.queryByText('rzp_live_HovgVggeDQ3qvw');
    expect(children).toBeInTheDocument();
  });

  test('Should call fetchAddWebsiteWorkflowStatus', async () => {
    renderApp();
    await waitFor(() => {
      expect(fetchAddWebsiteWorkflowStatusSpy).toBeCalled();
    });
  });

  test('Should update workflow status', async () => {
    renderApp();
    await waitFor(() => {
      expect(fetchAddWebsiteWorkflowStatusSpy).toBeCalled();
    });
    const addWebsiteBtn = screen.getByRole('button', { name: /Add website/i });
    expect(addWebsiteBtn).toBeInTheDocument();

    await userEvent.click(addWebsiteBtn);
    const workflowStatus = screen.queryByText('true');
    expect(workflowStatus).toBeInTheDocument();
  });
});
