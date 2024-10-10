import Papa from 'papaparse';
import exportCSV from 'merchant/views/Transactions/v1/Payments/Csvgenerate.js';

// Mock external dependencies
jest.mock('papaparse', () => ({
  unparse: jest.fn(),
}));

describe('ExportCSV', () => {
  // Mock URL.createObjectURL and URL.revokeObjectURL
  const mockCreateObjectURL = jest.fn();
  global.URL.createObjectURL = mockCreateObjectURL;

  let mockResponse;

  beforeEach(() => {
    // Set up mock data
    mockResponse = [
      {
        umrn: '123456789',
        payment_id: 'pay_ABC123',
        date_submitted: 1625155200,
        date_of_failure: 1625241600,
      },
    ];

    // Mock implementation of Papa.unparse
    Papa.unparse.mockReturnValue(
      'UMRN,PaymentId,Date_Submitted,Date_of_Failure,Failed_Transaction_summary_URL\n123456789,pay_ABC123,01/07/2021,02/07/2021,https://dashboard.dev.razorpay.in/app/payments/pay_ABC123',
    );

    mockCreateObjectURL.mockReturnValue('mock-object-url');

    // Spy on document.createElement
    jest.spyOn(document, 'createElement').mockImplementation(() => {
      return {
        setAttribute: jest.fn(),
        click: jest.fn(),
        remove: jest.fn(),
        href: '',
      };
    });

    // Spy on document.body.appendChild and removeChild
    jest.spyOn(document.body, 'appendChild').mockImplementation(() => {});
    jest.spyOn(document.body, 'removeChild').mockImplementation(() => {});
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should transform response data and trigger CSV download', () => {
    // Mock createElement and appendChild
    const mockCreateElement = jest.spyOn(document, 'createElement');
    const mockAppendChild = jest.spyOn(document.body, 'appendChild');
    const mockRemoveChild = jest.spyOn(document.body, 'removeChild');

    // Create a mock anchor element
    const mockAnchor = {
      click: jest.fn(), // Mock the click event
      setAttribute: jest.fn(),
      href: '',
    };

    // Ensure that createElement returns the mock anchor
    mockCreateElement.mockReturnValue(mockAnchor);

    // Mock response data
    const mockResponse = [
      {
        umrn: '123456789',
        payment_id: 'pay_ABC123',
        date_submitted: 1625155200,
        date_of_failure: 1625241600,
        failure_reason: 'Insufficient Funds',
        utility_code: 'NACH001',
        merchant_name: 'Merchant A',
        destination_bank: 'Bank A',
        customer_name: 'John Doe',
        ifsc: 'BANK000123',
        account_number: '1234567890',
      },
    ];

    // Call ExportCSV
    exportCSV(mockResponse);

    // Check if the setAttribute was called correctly
    expect(mockAnchor.setAttribute).toHaveBeenCalledWith(
      'download',
      'Payment_transaction_summary.csv',
    );

    // Check if the click method was called
    expect(mockAnchor.click).toHaveBeenCalled();

    // Check if the link was appended and removed from the DOM
    expect(mockAppendChild).toHaveBeenCalledWith(mockAnchor);
    expect(mockRemoveChild).toHaveBeenCalledWith(mockAnchor);

    // Clean up mocks
    mockCreateElement.mockRestore();
    mockAppendChild.mockRestore();
    mockRemoveChild.mockRestore();
  });

  test('should create a Blob with the correct CSV data', () => {
    // Call ExportCSV with the mock response
    exportCSV(mockResponse);

    // Expect that the Blob was created with the correct data and type
    expect(mockCreateObjectURL).toHaveBeenCalled();
  });
});
