import styled from 'styled-components';

export const DataTableWrapper = styled.div`
  .data-table > .table-responsive {
    overflow: visible;
  }
`;

export const CouponName = styled.div`
  font-size: 14px;
  font-weight: 600;
  line-height: 19px;
  color: #515978;
  overflow-wrap: break-word;
`;

export const CouponDescription = styled.div`
  font-size: 14px;
  font-weight: 400;
  line-height: 19px;
  color: #515978b2;
  overflow-wrap: break-word;
`;

export const EmptyCouponDescription = styled.div`
  font-size: 14px;
  font-weight: 600;
  line-height: 19px;
  margin-bottom: 8px;
  color: #515978;
  opacity: 0.6;
`;
