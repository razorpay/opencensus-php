/* eslint-disable */
import { jsPDF as JSPDF } from 'jspdf';
import autoTable from 'jspdf-autotable';
import {
  ADDRESS,
  MAIL,
  WEBSITE,
  INFO,
  TRANSACTIONSUMMARY,
  MERCHANTTITLE,
  NOTE,
  NOTEBODY,
  RazorpayLogo,
  RazorpayLogoBg,
  Blueline,
} from '../constants';
import moment from 'moment';

// Function to add the logo and background
const addLogoAndBackground = (Doc, pageWidth, pageHeight, callback) => {
  const imageHeight = 113.03; // 25% of the page height
  const imageYPosition = pageHeight - imageHeight;
  try {
    // Load background image as Base64
    const backgroundImgBase64 = RazorpayLogoBg;
    // Add background image directly
    Doc.addImage(backgroundImgBase64, 'PNG', 0, imageYPosition, pageWidth, imageHeight);

    // Load logo image as Base64
    const logoImgBase64 = RazorpayLogo;
    const logoWidth = 55.118;
    const logoHeight = 11.684;
    const logoXPosition = 10;
    const logoYContact = 20;

    // Add logo image directly
    Doc.addImage(logoImgBase64, 'PNG', logoXPosition, logoYContact, logoWidth, logoHeight);

    // Load line image as Base64
    const logoImgLineBase64 = Blueline;
    const lineWidth = 186.69;
    const lineHeight = 1.27;
    const lineY = 40;
    const lineX = 10;

    // Add line image directly
    Doc.addImage(logoImgLineBase64, 'PNG', lineX, lineY, lineWidth, lineHeight);

    callback();
  } catch (error) {
    console.error('Error loading images:', error);
  }
};

// Function to add text content
const addTextContent = (Doc, pageWidth, merchantId) => {
  const rightMargin = 80;
  const yStart = 10;

  // Add right-aligned content
  Doc.setFontSize(11);
  Doc.setFont('Arial');
  Doc.text(ADDRESS, pageWidth - rightMargin, yStart);
  Doc.text(WEBSITE, pageWidth - rightMargin, yStart + 10);
  Doc.text(MAIL, pageWidth - rightMargin, yStart + 20);

  // Centered info text
  const infoWidth = Doc.getTextWidth(INFO);
  const center = pageWidth / 2 - infoWidth / 2;
  Doc.setFont('Arial', 'italic');
  Doc.text(INFO, center, yStart + 40);

  // Add Merchant title and body
  Doc.setFont('Arial', 'normal');
  const transactionSummaryWidth = Doc.getTextWidth(TRANSACTIONSUMMARY);
  const transactionSummaryCenter = pageWidth / 2 - transactionSummaryWidth / 2;
  Doc.text(TRANSACTIONSUMMARY, transactionSummaryCenter, yStart + 50);

  const leftMargin = 15;
  const merchantBody = `As requested by you, please find below the details of the bounced transactions on your MID - ${merchantId}`;
  Doc.text(MERCHANTTITLE, leftMargin, yStart + 60);
  Doc.text(merchantBody, leftMargin, yStart + 70);
};

// Function to generate the table
const generateTable = (Doc, data, startY, pageWidth) => {
  const displayValue = (value) => (value ? value : 'NA');
  const tableWidth = pageWidth * 0.85; // 85% of page width
  const columnWidth = tableWidth / 2;

  autoTable(Doc, {
    startY,
    head: [['Attribute', 'Value']],
    body: data.flatMap((item) => [
      ['Unique Mandate Reference Number (UMRN)', displayValue(item.umrn)],
      ['Customer Ref number/Mandate ID', displayValue(item.mandate_id)],
      ['Amount', displayValue(item.amount)],
      [
        'Date Submitted',
        displayValue(moment.unix(Number(item.date_submitted)).format('DD/MM/YYYY')),
      ],
      [
        'Date of Failure',
        displayValue(moment.unix(Number(item.date_of_failure)).format('DD/MM/YYYY')),
      ],
      ['Failure Reason', displayValue(item.failure_reason)],
      ['NACH Utility Code', displayValue(item.utility_code)],
      ['Creditor/Merchant', displayValue(item.merchant_name)],
      ['Destination Bank', displayValue(item.destination_bank)],
      ['Name of the customer / Name of the Account Holder', displayValue(item.customer_name)],
      ['Bank Account IFSC', displayValue(item.ifsc)],
      ['Bank Account Number', displayValue(item.account_number)],
    ]),
    columnStyles: {
      0: { cellWidth: columnWidth, lineWidth: 0.05, lineColor: [0, 0, 0] },
      1: { cellWidth: columnWidth, lineWidth: 0.05, lineColor: [0, 0, 0] },
    },
    headStyles: {
      fillColor: 'white',
      textColor: 'black',
      fontSize: 11,
      fontStyle: 'normal',
      halign: 'center',
      lineWidth: 0.05,
      lineColor: [0, 0, 0],
      cellPadding: { top: 0.5, right: 1, bottom: 0.5, left: 1 },
    },
    bodyStyles: {
      fillColor: 'white',
      textColor: 'black',
      fontSize: 11,
      fontStyle: 'normal',
      halign: 'left',
      cellPadding: { top: 0.5, right: 1, bottom: 0.5, left: 1 },
    },
    theme: 'grid',
  });
};

// Function to add the note section
const addNote = (Doc, finalY) => {
  Doc.setFont('Arial', 'bolditalic');
  Doc.setFontSize(7.5);
  Doc.text(NOTE, 15, finalY);
  Doc.setFont('Arial', 'italic');
  Doc.text(NOTEBODY, 25, finalY);
};

// Main createPdfTable function

function createPdfTable(response, merchantId, pdfPage) {
  const generatePdfContent = (resolve) => {
    const Doc = new JSPDF();
    const pageWidth = Doc.internal.pageSize.width;
    const pageHeight = Doc.internal.pageSize.height;

    // Add logo and background, then continue with the other content
    addLogoAndBackground(Doc, pageWidth, pageHeight, () => {
      // Add text content
      addTextContent(Doc, pageWidth, merchantId);

      // Generate table
      const tableStartY = 90; // Adjust as needed
      generateTable(Doc, response, tableStartY, pageWidth);

      // Add note
      const finalY = Doc.lastAutoTable.finalY + 10;
      addNote(Doc, finalY);

      // Check which pdfPage to process
      if (pdfPage === 'singlePage') {
        // Save PDF for single page
        Doc.save('Payment_transaction_summary.pdf');
      } else if (pdfPage === 'routePage') {
        // Generate base64 string for route page
        const base64String = Doc.output('datauristring'); // Generate base64 data URL
        resolve(base64String); // Return the base64 string
      }
    });
  };

  if (pdfPage === 'singlePage') {
    generatePdfContent(); // Call without resolve
  } else if (pdfPage === 'routePage') {
    return new Promise((resolve) => {
      generatePdfContent(resolve); // Call with resolve
    });
  }
}

export default createPdfTable;
