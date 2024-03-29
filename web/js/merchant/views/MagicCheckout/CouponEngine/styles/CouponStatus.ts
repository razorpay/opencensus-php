import styled from 'styled-components';

interface CouponStatusVaraints {
  variant: 'created' | 'active' | 'in_active' | 'expired' | 'published';
}

export const CouponStatus = styled.div<CouponStatusVaraints>`
  width: 80px;
  border-radius: 16px;
  text-align: center;
  padding: 4px 12px;
  font-size: 10px;
  font-weight: 600;

  ${(props) => {
    switch (props.variant) {
      case 'created':
        return `
          background-color: #007bff1A;
          color: #007bff;
        `;
      case 'active':
        return `
        color: #1f890e;
        background: #1f890e1a;
        `;
      case 'in_active':
        return `
          background-color: #CD82141A;
          color: #CD8214;
        `;
      case 'expired':
        return `
        background: #162F561A;
          color: #162F56DE;
          ;
        `;
      case 'published':
        return `
          background-color: #17a2b8;
          color: #fff;
        `;
      default:
        return `
          background-color: #e0e0e0;
          color: #333;
        `;
    }
  }}
`;

interface SyncStatusVariants {
  variant: 'completed' | 'in-progress';
}

export const SyncStatus = styled.span<SyncStatusVariants>`
  border-radius: 16px;
  text-align: center;
  padding: 4px 12px;
  font-size: 10px;
  font-weight: 600;

  ${(props) => {
    switch (props.variant) {
      case 'completed':
        return `
        color: #1f890e;
        background: #1f890e1a;
        `;
      case 'in-progress':
        return `
          background-color: #CD82141A;
          color: #CD8214;
        `;
      default:
        return `
          background-color: #e0e0e0;
          color: #333;
        `;
    }
  }}
`;
