import styled from 'styled-components';

export const FilterContainer = styled.form(({ theme }) => ({
  display: 'flex',
  marginBottom: theme.spacing[6],
  flexWrap: 'wrap',
}));

export const InputContainer = styled.div(({ theme }) => ({
  marginTop: theme.spacing[2],
  marginBottom: theme.spacing[2],
  marginRight: theme.spacing[6],
  marginLeft: theme.spacing[0],
  minWidth: '180px',
}));

export const ButtonContainer = styled.div(({ theme }) => ({
  marginTop: theme.spacing[2],
  marginBottom: theme.spacing[2],
  marginRight: theme.spacing[6],
  marginLeft: theme.spacing[0],
  display: 'flex',
  alignItems: 'flex-end',
}));

export const AmountContainer = styled.div`
  display: flex;
  align-items: center;
`;

export const ErrorText = styled.div(({ theme }) => ({
  color: '#f05050',
  marginTop: theme.spacing[2],
}));

export const SpinnerContainer = styled.div(() => ({
  minHeight: '300px',
  display: 'flex',
  justifyContent: 'center',
  alignItems: 'center',
}));

export const DetailsSpinnerContainer = styled.div(() => ({
  minHeight: '100%',
  display: 'flex',
  justifyContent: 'center',
  alignItems: 'center',
}));

export const IconContainer = styled.span(() => ({
  display: 'flex',
  alignItems: 'center',
}));

export const ContentBox = styled.div`
  display: block;
  background-color: #f9fafb;
  border: 1px solid #e2e8ea;
  border-top: 0;
`;
