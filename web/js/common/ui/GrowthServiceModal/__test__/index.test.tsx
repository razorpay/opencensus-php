import { render, screen, server, waitFor } from 'test-utils';
import * as growthServiceReducer from 'merchant/reducers/growthService';
import GrowthServiceModal from 'common/ui/GrowthServiceModal/index';
import { defaultModalData, templateId } from './mock/modalData';
import { fetchGSModalHandler } from './mock/handlers';
import React from 'react';

describe('Tests for the default modal', () => {
  const fetchGSModalSpy = jest.spyOn(growthServiceReducer, 'fetchGSModal');

  beforeEach(() => {
    server.use(fetchGSModalHandler({ modalData: defaultModalData }));
    render(<GrowthServiceModal template_id={templateId} />);
  });

  test('Component should display correct data', async () => {
    await waitFor(() => {
      expect(fetchGSModalSpy).toHaveBeenCalledTimes(1);
    });
    expect(fetchGSModalSpy).toHaveBeenCalledWith({ template_id: templateId });
  });

  test('Component should display correct data with delay in loading', () => {
    server.use(fetchGSModalHandler({ delay: 2000, modalData: defaultModalData }));
    expect(screen.getByTestId('gs-modal-loader')).toBeInTheDocument();
  });

  test('Component should display correct data', async () => {
    await waitFor(() => {
      expect(screen.getByText(defaultModalData.footer_data.label)).toBeInTheDocument();
    });
    expect(screen.getByTestId('gs-modal-img')).toBeInTheDocument();
  });
});
