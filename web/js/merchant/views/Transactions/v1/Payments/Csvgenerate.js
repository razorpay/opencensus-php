import Papa from 'papaparse';

// Function to generate the dynamic URL based on specific criteria
const generateURL = (paymentId, url) => {
  // Logic to generate the URL dynamically, for example:
  // const trimmed = url.split('/app')[0] + '/app';
  const trimmed = url ? `${url.split('/app')[0]}/app` : '';
  return `${trimmed}/bouncememo/pay_${paymentId}`;
};

const exportCSV = (response, url) => {
  // Transform the response data to dynamically update the Failed_Transaction_summary_URL field
  const data = response.map((item) => ({
    UMRN: item.umrn,
    PaymentId: `pay_${item.payment_id}`,
    Date_Submitted: new Date(item.date_submitted * 1000).toLocaleDateString(), // Convert dateSubmitted to human-readable format
    Date_of_Failure: new Date(item.date_of_failure * 1000).toLocaleDateString(),
    Failed_Transaction_summary_URL: generateURL(item.payment_id, url), // Update dynamically based on PaymentID or other logic
  }));

  // Convert data to CSV format with headers
  const csvData = Papa.unparse(data, {
    header: true, // Include headers in the CSV
  });

  // Create a Blob from the CSV data
  const blob = new Blob([csvData], { type: 'text/csv;charset=utf-8;' });

  // Create a link element to trigger download
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.setAttribute('download', 'Payment_transaction_summary.csv');
  document.body.appendChild(link);

  link.click(); // Programmatically click the link to trigger the download
  document.body.removeChild(link); // Remove the link element after download
};

export default exportCSV;
