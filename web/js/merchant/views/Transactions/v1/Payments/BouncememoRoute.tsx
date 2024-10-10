import React, { useState, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import createPdfTable from 'merchant/views/Transactions/v1/Payments/components/PdfCreation';
import { fetchBouncememo } from './BounceMemo.types';
import { connect } from 'react-redux';
import Spinner from 'common/ui/Spinner';
import { Box } from '@razorpay/blade/components';
export interface User {
  id: string; // Assuming the user object has an id field, adjust this as needed
}

export interface PDFPageProps {
  user: User;
}

const PDFPage: React.FC<PDFPageProps> = ({ user }) => {
  const [isLoading, setLoading] = useState(true);
  const { id: merchantId } = user;
  const location = useLocation();
  const pathname = location.pathname;
  // Extract the ID from the pathname
  const paymentID = pathname.split('/').pop();
  const [pdfBase64, setPdfBase64] = useState(null);

  const handleGeneratePDF = async (paymentID) => {
    setLoading(true);
    const params = {
      paymentID,
      merchantId,
    };
    try {
      const response = await fetchBouncememo(params);
      const base64String = await createPdfTable(response.data.data, merchantId, 'routePage');
      setPdfBase64(base64String); // Store the base64 string for rendering
    } finally {
      setLoading(false);
    }
  };

  // Call the function immediately after component mounts
  useEffect(() => {
    handleGeneratePDF(paymentID);
  }, [paymentID]); // Empty dependency array ensures this runs only once when the component mounts

  return (
    <Box>
      {isLoading ? (
        <Spinner center={true} />
      ) : (
        <div>
          {pdfBase64 && (
            <div>
              {/* Embed PDF in iframe */}
              <iframe src={pdfBase64} title="PDF Preview" width="100%" height="1000px" />
            </div>
          )}
        </div>
      )}
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(PDFPage);
