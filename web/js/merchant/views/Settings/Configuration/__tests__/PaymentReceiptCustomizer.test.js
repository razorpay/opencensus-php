import { screen, waitFor, fireEvent, render } from 'test-utils';
import PaymentReceiptCustomizer from 'merchant/views/Settings/Configuration/PaymentReceiptCustomizer';
import { merchantFetch } from '@dashboards/payments/utils/merchantFetch';
import { analyticsTrack } from 'common/utils/analytics';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';

jest.mock('@dashboards/payments/utils/merchantFetch');
jest.mock('common/utils/analytics', () => ({
  analyticsTrack: jest.fn(),
  getCommonAnalyticsProperties: jest.fn().mockReturnValue({})
}));
jest.mock('common/utils/selfServeAnalytics', () => ({
  selfServeTrackInitiate: jest.fn(),
  selfServeTrackSuccess: jest.fn()
}));
jest.mock('merchant_common/reducers/notifications', () => ({
  showNotification: jest.fn((payload) => ({
    type: 'NOTIFICATION_SHOW',
    payload
  }))
}));

import { showNotification } from 'merchant_common/reducers/notifications';
import { ERROR, RECEIPT_CUSTOMIZATION_ERRORS, RECEIPT_CUSTOMIZATION_SUCCESS, SUCCESS } from '../constants';

const initialState = {
  session: {
    user: {
      id: 'user123',
      merchant: {
        brand_color: '#3395FF',
        logo_url: 'https://example.com/logo.png'
      }
    }
  }
};

const mockApiResponse = {
  data: {
    success: true,
    brand_color: '#FF5733',
    logo_url: 'https://api.example.com/logo.png'
  }
};

const mockUpdateResponse = {
  data: {
    success: true,
    updated_fields: {
      logo_url: 'https://example.com/updated-logo.png'
    }
  }
};

describe('PaymentReceiptCustomizer component', () => {
  beforeEach(() => {
    jest.clearAllMocks();    
    merchantFetch.mockResolvedValue(mockApiResponse);
  });

  test('renders with initial loading state and loads configuration', async () => {
    render(<PaymentReceiptCustomizer />, { initialState });
  
    expect(screen.getByText('Payment Receipt Customization')).toBeInTheDocument();
    expect(screen.getByText('Loading settings...')).toBeInTheDocument();
    
    expect(merchantFetch).toHaveBeenCalledWith(expect.objectContaining({
      url: 'mes/rzp.merchant_experience_service.customizable_receipt.v1.CustomizableReceiptAPI/GetReceiptConfig'
    }));
    
    await waitFor(() => {
      expect(screen.queryByText('Loading settings...')).not.toBeInTheDocument();
    });
    
    await waitFor(() => {
      const colorInput = document.getElementById('brand_color_text');
      expect(colorInput).toBeInTheDocument();
      expect(colorInput.value).toBe('#FF5733');
    });
    
    await waitFor(() => {
      const iframe = screen.getByTitle('Payment Receipt Preview');
      expect(iframe).toBeInTheDocument();
      expect(iframe.getAttribute('srcDoc')).toContain('#FF5733');
      expect(iframe.getAttribute('srcDoc')).toContain('https://api.example.com/logo.png');
    });
  });

  test('updates color and preview when color picker changes', async () => {
    render(<PaymentReceiptCustomizer />, { initialState });
    
    await waitFor(() => {
      expect(screen.queryByText('Loading settings...')).not.toBeInTheDocument();
    });
    
    const colorInput = document.getElementById('brand_color_text');
    expect(colorInput).toBeInTheDocument();
    fireEvent.change(colorInput, { target: { value: '#00FF00' } });
    
    const preview = screen.getByTitle('Payment Receipt Preview');
    expect(preview.getAttribute('srcDoc')).toContain('#00FF00');
    expect(preview.getAttribute('srcDoc')).toContain('https://api.example.com/logo.png');
  });

  test('submits form with correct data and shows success notification', async () => {
    merchantFetch
      .mockResolvedValueOnce(mockApiResponse) // For initial load
      .mockResolvedValueOnce(mockUpdateResponse); // For form submission
      
    render(<PaymentReceiptCustomizer />, { initialState });
    
    await waitFor(() => {
      expect(screen.queryByText('Loading settings...')).not.toBeInTheDocument();
    });
    
    fireEvent.click(screen.getByText('Save Customization'));
    expect(screen.getByText('Saving...')).toBeInTheDocument();
  
    await waitFor(() => {
      expect(merchantFetch).toHaveBeenCalledWith(expect.objectContaining({
        url: 'mes/rzp.merchant_experience_service.customizable_receipt.v1.CustomizableReceiptAPI/UpdateReceiptConfig',
        data: expect.objectContaining({
          brand_color: '#FF5733'
        })
      }));
    });
    
    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith({
        type: SUCCESS,
        message: RECEIPT_CUSTOMIZATION_SUCCESS,
      });
    });
    
    expect(selfServeTrackInitiate).toHaveBeenCalled();
    expect(selfServeTrackSuccess).toHaveBeenCalled();
    expect(analyticsTrack).toHaveBeenCalled();
  });

  test('shows error notification when API call fails', async () => {
    merchantFetch
      .mockResolvedValueOnce(mockApiResponse) // For initial load
      .mockRejectedValueOnce(new Error('API Error')); // For form submission
    
    render(<PaymentReceiptCustomizer />, { initialState });
    
    await waitFor(() => {
      expect(screen.queryByText('Loading settings...')).not.toBeInTheDocument();
    });
    
    fireEvent.click(screen.getByText('Save Customization'));
    
    await waitFor(() => {
      expect(showNotification).toHaveBeenCalledWith({
        type: ERROR,
        message: RECEIPT_CUSTOMIZATION_ERRORS.SAVE_ERROR,
      });
    });
  });
});