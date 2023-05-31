import { render, screen, server, waitFor } from 'test-utils';
import * as growthServiceReducer from 'merchant/reducers/growthService';
import ThankYouModal from 'common/ui/GrowthServiceModal/ThankYouModal';
import { thankYouModalData, templateId } from './mock/modalData';
import { fetchGSModalHandler } from './mock/handlers';
import React from 'react';

describe('Tests for the thank you modal', () => {
  const fetchGSModalSpy = jest.spyOn(growthServiceReducer, 'fetchGSModal');

  beforeEach(() => {
    server.use(fetchGSModalHandler({ modalData: thankYouModalData }));
    render(<ThankYouModal template_id={templateId} />);
  });

  test('Modal component should render with Props', async () => {
    await waitFor(() => {
      expect(fetchGSModalSpy).toHaveBeenCalledTimes(1);
    });
    expect(fetchGSModalSpy).toHaveBeenCalledWith({ template_id: templateId });
  });

  test('Modal component should render footer text', async () => {
    await waitFor(() => {
      expect(screen.getByText(thankYouModalData.footer_data.label)).toBeInTheDocument();
    });
  });
});
