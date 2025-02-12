import React from 'react';
import { Routes, Route } from 'react-router-dom';
import { RouteGuard } from 'merchant/components/ShowWhen';

import B2bPaymentsList from 'merchant/views/Transactions/v1/B2bPayments/List';
import BatchPaymentsList from 'merchant/views/Transactions/v1/BatchPayments/List';
import BatchRefundsUpload from 'merchant/views/Transactions/v1/BatchRefunds/BatchUpload';
import BatchRefundsList from 'merchant/views/Transactions/v1/BatchRefunds/List';
import DisputesList from 'merchant/views/Transactions/v1/Disputes/List';
import OrdersList from 'merchant/views/Transactions/v1/Orders/List';
import SuccessRate from 'merchant/views/Transactions/v1/SuccessRate';
import UploadInvoice from 'merchant/views/Transactions/v1/UploadInvoice';
import TransactionV2Landing from 'merchant/views/Transactions/v2/Landing';
import { connect } from 'react-redux';

import PaymentsDetailsV2 from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails';

import PaymentsContainer from 'merchant/views/Transactions/v2/Payments/components/PaymentsContainer';

import TransactionsV2EntitiesOverview from 'merchant/views/Transactions/v2/EntitiesOverview';

import TransactionV2RefundsContainer from 'merchant/views/Transactions/v2/Refunds/components/RefundsContainer';

const mapStateToProps = ({ session }) => {
  return {
    user: session.user,
    mode: session.mode,
  };
};

export default connect(
  mapStateToProps,
  null,
)(() => {
  return (
    <div>
      Joel
      <Routes>
        <Route
          path="failed-payments/*"
          element={
            <RouteGuard>
              <TransactionsV2EntitiesOverview />
            </RouteGuard>
          }
        >
          <Route
            index
            element={
              <RouteGuard>
                <PaymentsContainer />
              </RouteGuard>
            }
          />
        </Route>
        <Route path="payments/*">
          <Route path="*" element={<RouteGuard>{<TransactionV2Landing />}</RouteGuard>}>
            <Route index element={<RouteGuard>{<PaymentsContainer />}</RouteGuard>} />
            <Route path="batchuploads/*">
              <Route
                index
                element={
                  <RouteGuard>
                    <BatchPaymentsList />
                  </RouteGuard>
                }
              />
              <Route
                path=":mode/*"
                element={
                  <RouteGuard>
                    <BatchPaymentsList />
                  </RouteGuard>
                }
              />
            </Route>
            <Route
              path="invoices/*"
              element={
                <RouteGuard>
                  <UploadInvoice />
                </RouteGuard>
              }
            />
            <Route
              path="b2b-exports/*"
              element={
                <RouteGuard>
                  <B2bPaymentsList />
                </RouteGuard>
              }
            />
          </Route>
          <Route
            path=":id"
            element={
              <RouteGuard>
                <PaymentsDetailsV2 />
              </RouteGuard>
            }
          />
        </Route>
        <Route
          path="refunds/*"
          element={<RouteGuard>{<TransactionsV2EntitiesOverview />}</RouteGuard>}
        >
          <Route path="*" element={<RouteGuard>{<TransactionV2RefundsContainer />}</RouteGuard>} />
          <Route
            path=":id"
            element={
              <RouteGuard>
                <PaymentsDetailsV2 />
              </RouteGuard>
            }
          />
          <Route
            path="batchuploads/*"
            element={
              <RouteGuard>
                <BatchRefundsList />
              </RouteGuard>
            }
          />
          <Route
            path="batchupload/*"
            element={
              <RouteGuard>
                <BatchRefundsUpload />
              </RouteGuard>
            }
          />
        </Route>
        <Route path="orders/*" element={<RouteGuard>{<TransactionV2Landing />}</RouteGuard>}>
          <Route
            index
            element={
              <RouteGuard>
                <OrdersList />
              </RouteGuard>
            }
          />
        </Route>
        <Route
          path="disputes/*"
          element={<RouteGuard>{<TransactionsV2EntitiesOverview />}</RouteGuard>}
        >
          <Route
            index
            element={
              <RouteGuard>
                <DisputesList />
              </RouteGuard>
            }
          />
        </Route>
        <Route
          path="success-rate/*"
          element={<RouteGuard>{<TransactionsV2EntitiesOverview />}</RouteGuard>}
        >
          <Route
            index
            element={
              <RouteGuard>
                <SuccessRate />
              </RouteGuard>
            }
          />
        </Route>
      </Routes>
    </div>
  );
});
