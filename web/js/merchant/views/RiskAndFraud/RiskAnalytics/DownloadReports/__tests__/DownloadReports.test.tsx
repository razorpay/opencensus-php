import React from 'react';

import * as ModalActions from 'merchant_common/reducers/modals';
import { render, screen, userEvent, waitFor } from 'test-utils';

import DownloadReports from '../DownloadReports';
import { DOWNLOAD_REPORTS } from '../constants';
import { AnalyticsEntity, DownloadReportsProps } from '../types';

const openModal = jest.fn();
const closeModal = jest.fn();
const showNotification = jest.fn();

describe('RiskAnalytics - DownloadReports', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');
  const mockProps: DownloadReportsProps = {
    entity: AnalyticsEntity.FRAUD,
    availableEmails: [],
    generatedBy: '',
    openModal,
    closeModal,
    showNotification,
  };

  const renderComponent = (props = {}) => render(<DownloadReports {...mockProps} {...props} />);

  beforeEach(() => jest.clearAllMocks());

  test.each(Object.values(AnalyticsEntity))(
    'should render DownloadReports with heading and "Download List" button with icon for %s entity',
    async (entity) => {
      renderComponent({ entity });
      const { heading } = DOWNLOAD_REPORTS[entity];
      await waitFor(() => {
        expect(screen.getByText(heading)).toBeInTheDocument();
        const downloadBtn = screen.getByRole('button', { name: 'Download list' });
        expect(downloadBtn).toBeInTheDocument();
        expect(downloadBtn.querySelector('[data-blade-component="icon"]')).toBeInTheDocument();
      });
    },
  );

  test.each([AnalyticsEntity.FRAUD, AnalyticsEntity.DISPUTES])(
    'should render DownloadReports "Note" for %s entity',
    async (entity) => {
      renderComponent({ entity });
      await waitFor(() => expect(screen.getByText(/Note:/i)).toBeInTheDocument());
    },
  );

  test('should not render DownloadReports "Note" for Risk declined entity', () => {
    renderComponent({ entity: AnalyticsEntity.RISK_DECLINED });
    expect(screen.queryByText(/Note:/i)).toBeNull();
  });

  test('should assert "Download List" button click', async () => {
    renderComponent();
    expect(openModalSpy).toHaveBeenCalledTimes(0);
    await userEvent.click(screen.getByRole('button', { name: 'Download list' }));
    expect(openModalSpy).toHaveBeenCalledTimes(1);
    expect(openModalSpy).toHaveBeenCalledWith({ component: expect.any(Object) });
  });
});
