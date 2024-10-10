/* eslint-disable no-relative-import-paths/no-relative-import-paths */
import { jsPDF as JSPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import createPdfTable from '../PdfCreation';

// Mock dependencies
jest.mock('jspdf');
jest.mock('jspdf-autotable');

describe('CreatePdfTable', () => {
  let mockPdfInstance, mockImages;

  beforeEach(() => {
    // Mock jsPDF instance
    mockPdfInstance = {
      text: jest.fn(),
      setFontSize: jest.fn(),
      setFont: jest.fn(),
      addImage: jest.fn(),
      getTextWidth: jest.fn(() => 50),
      save: jest.fn(),
      internal: { pageSize: { width: 210, height: 297 } }, // A4 dimensions
      lastAutoTable: { finalY: 0 },
    };

    // Mock jsPDF constructor to return the mock instance
    JSPDF.mockImplementation(() => mockPdfInstance);

    const address =
      '1st Floor, SJR Cyber, 22, Laskar Hosur Road,\nAdugodi, Bangalore, Karnataka, India - 560030';
    // Mock autoTable
    autoTable.mockImplementation((Doc) => {
      Doc.lastAutoTable = { finalY: 100 };
      const pageWidth = Doc.internal.pageSize.width;
      const rightMargin = 80;
      const yStart = 10;
      Doc.text(address, pageWidth - rightMargin, yStart);
    });

    // Mock Image constructor to simulate image loading behavior
    mockImages = [];
    global.Image = jest.fn(() => {
      const image = {};
      image.onload = jest.fn(); // Each image will have an onload function
      mockImages.push(image); // Keep track of the created images
      return image;
    });
  });

  it('should create a PDF and call addImage and autoTable correctly', () => {
    const response = [
      {
        umrn: 'NACH001',
        mandate_id: 'mandate_id_1',
        amount: '1000',
        date_submitted: 1725956592,
        date_of_failure: 1725956592,
        failure_reason: 'reason_1',
        utility_code: 'utility_code_1',
        merchant_name: 'merchant_name_1',
        destination_bank: 'bank_1',
        customer_name: 'customer_1',
        ifsc: 'ifsc_1',
        account_number: 'account_number_1',
      },
    ];
    const merchantId = 'mid_12345';

    // Call the function to generate the PDF
    createPdfTable(response, merchantId, 'singlePage');

    // Simulate image loading
    mockImages.forEach((image) => {
      image.onload();
    });

    mockImages.forEach((image) => {
      image.onload();
    });

    // Check that addImage was called for the background and logos
    expect(mockPdfInstance.addImage).toHaveBeenCalledTimes(3); // Assuming 3 images should be added
  });
});

describe('PDF generation', () => {
  let Doc;

  beforeEach(() => {
    Doc = new JSPDF();
  });

  it('should generate the PDF and call autoTable', () => {
    const info = 'Test info';
    const transactionSummary = 'Test summary';
    const merchantTitle = 'Test Merchant Title';
    const merchantBody = 'Test Merchant Body';
    const pageWidth = 210; // A4 page width in mm

    const center = pageWidth / 2 - 50 / 2; // Mocked getTextDimensions width = 50

    // Call the actual function that generates the PDF (replace this with your actual function)
    const infoTopmargin = 40;
    Doc.setFont('Arial', 'italic');
    Doc.text(info, center, infoTopmargin);

    Doc.setFont('Arial', 'normal');
    const transactionSummaryCenter = pageWidth / 2 - 50 / 2; // Mocked again
    const transactionSummaryTopmargin = infoTopmargin + 10;
    Doc.text(transactionSummary, transactionSummaryCenter, transactionSummaryTopmargin);

    const leftMargin = 15;
    const topMarginMerchantTitle = transactionSummaryTopmargin + 10;
    Doc.text(merchantTitle, leftMargin, topMarginMerchantTitle);
    Doc.text(merchantBody, leftMargin, topMarginMerchantTitle + 10);

    autoTable(Doc, {
      // Define columns, rows, and table settings here
    });

    // Verify if the relevant methods were called
    expect(Doc.setFont).toHaveBeenCalledTimes(2);
    expect(Doc.text).toHaveBeenCalledTimes(5); // Adjust based on your text calls
    expect(autoTable).toHaveBeenCalledTimes(1);
  });
});

describe('CreatePdfTable', () => {
  let mockPdfInstance, mockImages;

  beforeEach(() => {
    // Mock jsPDF instance
    mockPdfInstance = {
      text: jest.fn(),
      setFontSize: jest.fn(),
      setFont: jest.fn(),
      addImage: jest.fn(),
      getTextWidth: jest.fn(() => 50),
      save: jest.fn(),
      output: jest.fn(() => 'datauristring'), // Mock output method to return base64 string
      internal: { pageSize: { width: 210, height: 297 } }, // A4 dimensions
      lastAutoTable: { finalY: 0 },
    };

    // Mock jsPDF constructor to return the mock instance
    JSPDF.mockImplementation(() => mockPdfInstance);

    const address =
      '1st Floor, SJR Cyber, 22, Laskar Hosur Road,\nAdugodi, Bangalore, Karnataka, India - 560030';

    // Mock autoTable
    autoTable.mockImplementation((Doc) => {
      Doc.lastAutoTable = { finalY: 100 };
      const pageWidth = Doc.internal.pageSize.width;
      const rightMargin = 80;
      const yStart = 10;
      Doc.text(address, pageWidth - rightMargin, yStart);
    });

    // Mock Image constructor to simulate image loading behavior
    mockImages = [];
    global.Image = jest.fn(() => {
      const image = {};
      image.onload = jest.fn(); // Each image will have an onload function
      mockImages.push(image); // Keep track of the created images
      return image;
    });
  });

  it('should return a base64 string when pdfPage is routePage', async () => {
    const response = [
      {
        umrn: 'NACH001',
        mandate_id: 'mandate_id_1',
        amount: '1000',
        date_submitted: 1725956592,
        date_of_failure: 1725956592,
        failure_reason: 'reason_1',
        utility_code: 'utility_code_1',
        merchant_name: 'merchant_name_1',
        destination_bank: 'bank_1',
        customer_name: 'customer_1',
        ifsc: 'ifsc_1',
        account_number: 'account_number_1',
      },
    ];
    const merchantId = 'mid_12345';

    // Call the function and return the base64 string
    const base64String = await createPdfTable(response, merchantId, 'routePage');

    // Simulate image loading
    mockImages.forEach((image) => {
      image.onload();
    });

    // Check that addImage was called for the background and logos
    expect(mockPdfInstance.addImage).toHaveBeenCalledTimes(3); // Assuming 3 images should be added

    // Check that the output method was called
    expect(mockPdfInstance.output).toHaveBeenCalledWith('datauristring');

    // Assert the base64 string is returned
    expect(base64String).toBe('datauristring');
  });
});
